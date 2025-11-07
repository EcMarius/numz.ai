<?php

namespace App\Numz\Services;

use App\Models\CustomReport;
use App\Models\ReportExecution;
use App\Models\ReportExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportGenerationService
{
    /**
     * Whitelisted fields that can be used in reports
     */
    protected const ALLOWED_FIELDS = [
        // Invoice fields
        'invoices.id', 'invoices.user_id', 'invoices.invoice_number', 'invoices.status',
        'invoices.subtotal', 'invoices.tax', 'invoices.total', 'invoices.created_at',
        'invoices.due_date', 'invoices.paid_date', 'invoices.currency',
        // Order fields
        'orders.id', 'orders.user_id', 'orders.product_id', 'orders.order_number',
        'orders.status', 'orders.total', 'orders.created_at', 'orders.billing_cycle',
        // User fields
        'users.id', 'users.name', 'users.email', 'users.created_at',
        // Product fields
        'products.id', 'products.name', 'products.price', 'products.category',
        // Subscription fields
        'subscriptions.id', 'subscriptions.user_id', 'subscriptions.status',
        'subscriptions.amount', 'subscriptions.created_at',
        // Affiliate fields
        'affiliates.id', 'affiliates.affiliate_code', 'affiliates.total_sales',
        'affiliates.total_commission_earned', 'affiliates.status',
        // Commission fields
        'affiliate_commissions.id', 'affiliate_commissions.commission_amount',
        'affiliate_commissions.sale_amount', 'affiliate_commissions.status',
        'affiliate_commissions.earned_date',
    ];

    /**
     * Whitelisted aggregation functions
     */
    protected const ALLOWED_AGGREGATIONS = ['SUM', 'AVG', 'COUNT', 'MIN', 'MAX'];

    /**
     * Execute a report and return results
     */
    public function executeReport(CustomReport $report, ?int $userId = null): ReportExecution
    {
        // Create execution record
        $execution = ReportExecution::create([
            'custom_report_id' => $report->id,
            'executed_by' => $userId ?? auth()->id(),
            'status' => 'pending',
        ]);

        try {
            $execution->markAsStarted();

            // Build and execute query based on report configuration
            $results = $this->buildAndExecuteQuery($report);

            // Process results
            $processedData = $this->processResults($results, $report);

            $execution->markAsCompleted($processedData, count($results));

            // Update report stats
            $report->update([
                'last_generated_at' => now(),
            ]);
            $report->incrementViews();

            Log::info('Report executed successfully', [
                'report_id' => $report->id,
                'execution_id' => $execution->id,
                'row_count' => count($results),
            ]);

            return $execution;
        } catch (\Exception $e) {
            $execution->markAsFailed($e->getMessage());

            Log::error('Report execution failed', [
                'report_id' => $report->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build and execute query based on report configuration
     */
    protected function buildAndExecuteQuery(CustomReport $report): array
    {
        $dataSources = $report->data_sources ?? [];
        $columns = $report->columns ?? [];
        $filters = $report->filters ?? [];
        $grouping = $report->grouping ?? [];
        $sorting = $report->sorting ?? [];

        // Start query builder
        $query = $this->buildBaseQuery($dataSources);

        // Apply columns
        $query = $this->applyColumns($query, $columns);

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        // Apply grouping
        $query = $this->applyGrouping($query, $grouping);

        // Apply sorting
        $query = $this->applySorting($query, $sorting);

        // Execute query
        return $query->get()->toArray();
    }

    /**
     * Build base query from data sources
     */
    protected function buildBaseQuery(array $dataSources)
    {
        $primarySource = $dataSources[0] ?? 'invoices';

        // Map data sources to tables
        $query = match ($primarySource) {
            'invoices' => DB::table('invoices')->where('status', 'paid'),
            'orders' => DB::table('orders')->where('status', 'active'),
            'users' => DB::table('users')->where('role', 'customer'),
            'products' => DB::table('products')->where('is_active', true),
            'subscriptions' => DB::table('subscriptions')->where('status', 'active'),
            'affiliates' => DB::table('affiliates')->where('status', 'active'),
            'commissions' => DB::table('affiliate_commissions')->where('status', 'approved'),
            default => DB::table('invoices'),
        };

        // Apply joins for related data sources
        foreach (array_slice($dataSources, 1) as $source) {
            $query = $this->applyJoin($query, $primarySource, $source);
        }

        return $query;
    }

    /**
     * Apply join for related data source
     */
    protected function applyJoin($query, string $primarySource, string $relatedSource)
    {
        // Define join relationships
        $joins = [
            'invoices' => [
                'users' => ['invoices.user_id', '=', 'users.id'],
                'orders' => ['invoices.order_id', '=', 'orders.id'],
            ],
            'orders' => [
                'users' => ['orders.user_id', '=', 'users.id'],
                'products' => ['orders.product_id', '=', 'products.id'],
            ],
            'affiliates' => [
                'users' => ['affiliates.user_id', '=', 'users.id'],
            ],
        ];

        if (isset($joins[$primarySource][$relatedSource])) {
            $query->leftJoin($relatedSource, $joins[$primarySource][$relatedSource][0], $joins[$primarySource][$relatedSource][1], $joins[$primarySource][$relatedSource][2]);
        }

        return $query;
    }

    /**
     * Apply columns to query
     */
    protected function applyColumns($query, array $columns)
    {
        if (empty($columns)) {
            return $query->select('*');
        }

        $selectColumns = [];

        foreach ($columns as $column) {
            $field = $column['field'];
            $alias = $column['alias'] ?? $field;
            $aggregation = $column['aggregation'] ?? null;

            // Validate field
            if (!in_array($field, self::ALLOWED_FIELDS)) {
                throw new \Exception("Invalid field: {$field}. Field not whitelisted for reports.");
            }

            // Sanitize alias (alphanumeric and underscore only)
            $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);

            if ($aggregation) {
                // Validate aggregation function
                $aggregationUpper = strtoupper($aggregation);
                if (!in_array($aggregationUpper, self::ALLOWED_AGGREGATIONS)) {
                    throw new \Exception("Invalid aggregation: {$aggregation}");
                }
                $selectColumns[] = DB::raw("{$aggregationUpper}({$field}) as {$alias}");
            } else {
                $selectColumns[] = "{$field} as {$alias}";
            }
        }

        return $query->select($selectColumns);
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters)
    {
        foreach ($filters as $filter) {
            $field = $filter['field'];
            $operator = $filter['operator'];
            $value = $filter['value'] ?? null;

            // Validate field
            if (!in_array($field, self::ALLOWED_FIELDS)) {
                throw new \Exception("Invalid filter field: {$field}. Field not whitelisted for reports.");
            }

            $query = match ($operator) {
                'equals' => $query->where($field, $value),
                'not_equals' => $query->where($field, '!=', $value),
                'greater_than' => $query->where($field, '>', $value),
                'less_than' => $query->where($field, '<', $value),
                'contains' => $query->where($field, 'like', "%{$value}%"),
                'in' => $query->whereIn($field, is_array($value) ? $value : [$value]),
                'between' => $query->whereBetween($field, [
                    $value['min'] ?? throw new \Exception('Between filter missing min value'),
                    $value['max'] ?? throw new \Exception('Between filter missing max value')
                ]),
                'is_null' => $query->whereNull($field),
                'is_not_null' => $query->whereNotNull($field),
                default => $query,
            };
        }

        return $query;
    }

    /**
     * Apply grouping to query
     */
    protected function applyGrouping($query, array $grouping)
    {
        if (empty($grouping)) {
            return $query;
        }

        foreach ($grouping as $group) {
            $field = $group['field'];

            // Validate field
            if (!in_array($field, self::ALLOWED_FIELDS)) {
                throw new \Exception("Invalid grouping field: {$field}. Field not whitelisted for reports.");
            }

            $query->groupBy($field);
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting($query, array $sorting)
    {
        if (empty($sorting)) {
            return $query;
        }

        foreach ($sorting as $sort) {
            $field = $sort['field'];
            $direction = $sort['direction'] ?? 'asc';

            // Validate field
            if (!in_array($field, self::ALLOWED_FIELDS)) {
                throw new \Exception("Invalid sorting field: {$field}. Field not whitelisted for reports.");
            }

            // Validate direction
            if (!in_array(strtolower($direction), ['asc', 'desc'])) {
                throw new \Exception("Invalid sort direction: {$direction}");
            }

            $query->orderBy($field, $direction);
        }

        return $query;
    }

    /**
     * Process results with calculations
     */
    protected function processResults(array $results, CustomReport $report): array
    {
        $calculations = $report->calculations ?? [];

        if (empty($calculations)) {
            return ['data' => $results];
        }

        $processedData = [
            'data' => $results,
            'calculations' => [],
        ];

        foreach ($calculations as $calculation) {
            $type = $calculation['type'];
            $field = $calculation['field'];

            // Extract values for this field
            $values = array_column($results, $field);

            $processedData['calculations'][$calculation['label'] ?? $type] = match ($type) {
                'sum' => !empty($values) ? array_sum($values) : 0,
                'avg' => !empty($values) ? array_sum($values) / count($values) : 0,
                'min' => !empty($values) ? min($values) : 0,
                'max' => !empty($values) ? max($values) : 0,
                'count' => count($results),
                default => null,
            };
        }

        return $processedData;
    }

    /**
     * Export report to file
     */
    public function exportReport(
        CustomReport $report,
        ReportExecution $execution,
        string $format = 'csv',
        ?int $userId = null
    ): ReportExport {
        $data = $execution->result_data;
        $fileName = \Illuminate\Support\Str::slug($report->name) . '-' . now()->format('Y-m-d-His') . '.' . $format;
        $filePath = 'reports/' . $fileName;

        // Generate file based on format
        $content = match ($format) {
            'csv' => $this->generateCsv($data),
            'json' => $this->generateJson($data),
            'xlsx' => $this->generateXlsx($data),
            'pdf' => $this->generatePdf($data, $report),
            default => $this->generateCsv($data),
        };

        // Store file
        Storage::put($filePath, $content);

        // Create export record
        $export = ReportExport::create([
            'custom_report_id' => $report->id,
            'report_execution_id' => $execution->id,
            'exported_by' => $userId ?? auth()->id(),
            'export_format' => $format,
            'file_path' => $filePath,
            'file_size' => Storage::size($filePath),
            'expires_at' => now()->addDays(30),
        ]);

        Log::info('Report exported', [
            'report_id' => $report->id,
            'export_id' => $export->id,
            'format' => $format,
        ]);

        return $export;
    }

    /**
     * Generate CSV content
     */
    protected function generateCsv(array $data): string
    {
        $rows = $data['data'] ?? $data;

        if (empty($rows)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Write headers
        fputcsv($output, array_keys($rows[0]));

        // Write rows
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate JSON content
     */
    protected function generateJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Generate XLSX content
     */
    protected function generateXlsx(array $data): string
    {
        // This would require a library like PhpSpreadsheet
        // For now, return CSV as fallback
        return $this->generateCsv($data);
    }

    /**
     * Generate PDF content
     */
    protected function generatePdf(array $data, CustomReport $report): string
    {
        // This would require a library like DomPDF or mPDF
        // For now, return a simple HTML representation
        $html = '<h1>' . $report->name . '</h1>';
        $html .= '<p>' . $report->description . '</p>';
        $html .= '<table border="1"><thead><tr>';

        $rows = $data['data'] ?? $data;

        if (!empty($rows)) {
            // Headers
            foreach (array_keys($rows[0]) as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            // Rows
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        return $html;
    }

    /**
     * Get report preview (limited rows)
     */
    public function getReportPreview(CustomReport $report, int $limit = 10): array
    {
        $execution = $this->executeReport($report);
        $data = $execution->result_data;

        if (isset($data['data'])) {
            $data['data'] = array_slice($data['data'], 0, $limit);
        }

        return $data;
    }
}
