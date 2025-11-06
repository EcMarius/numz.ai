<?php

namespace App\Numz\Services;

use App\Models\ReportSchedule;
use App\Models\CustomReport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportSchedulerService
{
    protected ReportGenerationService $reportGenerationService;

    public function __construct(ReportGenerationService $reportGenerationService)
    {
        $this->reportGenerationService = $reportGenerationService;
    }

    /**
     * Process all due schedules
     */
    public function processDueSchedules(): int
    {
        $schedules = ReportSchedule::getDueSchedules();

        $processed = 0;

        foreach ($schedules as $schedule) {
            try {
                $this->processSchedule($schedule);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Schedule processing failed', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Schedules processed', ['count' => $processed]);

        return $processed;
    }

    /**
     * Process a single schedule
     */
    public function processSchedule(ReportSchedule $schedule): void
    {
        Log::info('Processing schedule', [
            'schedule_id' => $schedule->id,
            'report_id' => $schedule->custom_report_id,
        ]);

        // Generate report
        $execution = $this->reportGenerationService->executeReport($schedule->report);

        // Export report in requested format
        $export = $this->reportGenerationService->exportReport(
            $schedule->report,
            $execution,
            $schedule->export_format
        );

        // Deliver report based on delivery method
        match ($schedule->delivery_method) {
            'email' => $this->deliverViaEmail($schedule, $export),
            'slack' => $this->deliverViaSlack($schedule, $export),
            'webhook' => $this->deliverViaWebhook($schedule, $export),
            'storage' => $this->deliverViaStorage($schedule, $export),
            default => null,
        };

        // Mark schedule as executed
        $schedule->markAsExecuted();

        Log::info('Schedule processed successfully', [
            'schedule_id' => $schedule->id,
            'execution_id' => $execution->id,
            'export_id' => $export->id,
        ]);
    }

    /**
     * Deliver report via email
     */
    protected function deliverViaEmail(ReportSchedule $schedule, $export): void
    {
        $recipients = $schedule->recipients ?? [];

        if (empty($recipients)) {
            Log::warning('No recipients for email delivery', ['schedule_id' => $schedule->id]);
            return;
        }

        foreach ($recipients as $recipient) {
            try {
                Mail::send('emails.scheduled-report', [
                    'reportName' => $schedule->report->name,
                    'scheduleName' => $schedule->name,
                    'generatedAt' => now()->format('Y-m-d H:i:s'),
                ], function ($message) use ($recipient, $schedule, $export) {
                    $message->to($recipient)
                        ->subject('Scheduled Report: ' . $schedule->report->name)
                        ->attach(Storage::path($export->file_path), [
                            'as' => basename($export->file_path),
                            'mime' => $this->getMimeType($export->export_format),
                        ]);
                });

                Log::info('Report emailed', [
                    'schedule_id' => $schedule->id,
                    'recipient' => $recipient,
                ]);
            } catch (\Exception $e) {
                Log::error('Email delivery failed', [
                    'schedule_id' => $schedule->id,
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Deliver report via Slack
     */
    protected function deliverViaSlack(ReportSchedule $schedule, $export): void
    {
        $webhookUrl = config('services.slack.webhook_url');

        if (!$webhookUrl) {
            Log::warning('Slack webhook URL not configured');
            return;
        }

        $downloadUrl = url('/reports/download/' . $export->id);

        $message = [
            'text' => 'Scheduled Report: ' . $schedule->report->name,
            'attachments' => [
                [
                    'title' => $schedule->name,
                    'text' => 'Your scheduled report is ready.',
                    'fields' => [
                        [
                            'title' => 'Report',
                            'value' => $schedule->report->name,
                            'short' => true,
                        ],
                        [
                            'title' => 'Format',
                            'value' => strtoupper($export->export_format),
                            'short' => true,
                        ],
                        [
                            'title' => 'Generated',
                            'value' => now()->format('Y-m-d H:i:s'),
                            'short' => true,
                        ],
                    ],
                    'actions' => [
                        [
                            'type' => 'button',
                            'text' => 'Download Report',
                            'url' => $downloadUrl,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_exec($ch);
            curl_close($ch);

            Log::info('Report delivered to Slack', ['schedule_id' => $schedule->id]);
        } catch (\Exception $e) {
            Log::error('Slack delivery failed', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deliver report via webhook
     */
    protected function deliverViaWebhook(ReportSchedule $schedule, $export): void
    {
        $webhookUrl = $schedule->schedule_config['webhook_url'] ?? null;

        if (!$webhookUrl) {
            Log::warning('Webhook URL not configured', ['schedule_id' => $schedule->id]);
            return;
        }

        $payload = [
            'schedule_id' => $schedule->id,
            'report_id' => $schedule->report->id,
            'report_name' => $schedule->report->name,
            'export_id' => $export->id,
            'export_format' => $export->export_format,
            'download_url' => url('/reports/download/' . $export->id),
            'generated_at' => now()->toIso8601String(),
        ];

        try {
            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_exec($ch);
            curl_close($ch);

            Log::info('Report delivered via webhook', ['schedule_id' => $schedule->id]);
        } catch (\Exception $e) {
            Log::error('Webhook delivery failed', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deliver report via storage (save to specific location)
     */
    protected function deliverViaStorage(ReportSchedule $schedule, $export): void
    {
        $destinationPath = $schedule->schedule_config['storage_path'] ?? 'scheduled-reports';

        try {
            $fileName = basename($export->file_path);
            $destination = $destinationPath . '/' . $fileName;

            Storage::copy($export->file_path, $destination);

            Log::info('Report saved to storage', [
                'schedule_id' => $schedule->id,
                'destination' => $destination,
            ]);
        } catch (\Exception $e) {
            Log::error('Storage delivery failed', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get MIME type for export format
     */
    protected function getMimeType(string $format): string
    {
        return match ($format) {
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'json' => 'application/json',
            default => 'application/octet-stream',
        };
    }

    /**
     * Create a new schedule
     */
    public function createSchedule(
        int $reportId,
        string $name,
        string $frequency,
        array $scheduleConfig,
        string $deliveryMethod,
        array $recipients,
        string $exportFormat = 'pdf'
    ): ReportSchedule {
        $report = CustomReport::findOrFail($reportId);

        $schedule = ReportSchedule::create([
            'custom_report_id' => $reportId,
            'name' => $name,
            'frequency' => $frequency,
            'schedule_config' => $scheduleConfig,
            'delivery_method' => $deliveryMethod,
            'recipients' => $recipients,
            'export_format' => $exportFormat,
            'is_active' => false,
            'next_run_at' => null,
        ]);

        Log::info('Schedule created', ['schedule_id' => $schedule->id]);

        return $schedule;
    }

    /**
     * Update schedule
     */
    public function updateSchedule(int $scheduleId, array $data): ReportSchedule
    {
        $schedule = ReportSchedule::findOrFail($scheduleId);
        $schedule->update($data);

        // Recalculate next run if frequency changed
        if (isset($data['frequency']) || isset($data['schedule_config'])) {
            $schedule->update([
                'next_run_at' => $schedule->calculateNextRun(),
            ]);
        }

        Log::info('Schedule updated', ['schedule_id' => $scheduleId]);

        return $schedule->fresh();
    }

    /**
     * Delete schedule
     */
    public function deleteSchedule(int $scheduleId): bool
    {
        $schedule = ReportSchedule::findOrFail($scheduleId);
        $deleted = $schedule->delete();

        Log::info('Schedule deleted', ['schedule_id' => $scheduleId]);

        return $deleted;
    }

    /**
     * Test schedule (run immediately)
     */
    public function testSchedule(int $scheduleId): void
    {
        $schedule = ReportSchedule::findOrFail($scheduleId);
        $this->processSchedule($schedule);

        Log::info('Schedule test completed', ['schedule_id' => $scheduleId]);
    }
}
