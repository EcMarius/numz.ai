<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReportResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Reports & Analytics';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Report';

    protected static ?string $pluralModelLabel = 'Reports';

    // Disable create, edit, delete actions
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Revenue Reports')
            ->description('View comprehensive revenue analytics and performance metrics')
            ->columns([
                Tables\Columns\TextColumn::make('period')
                    ->label('Period')
                    ->getStateUsing(fn (Order $record) => $record->created_at->format('Y-m'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('product.type')
                    ->label('Product Type')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('order_count')
                    ->label('Orders')
                    ->counts('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('USD')
                    ->getStateUsing(fn (Order $record) => $record->total)
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('USD')
                            ->label('Total Revenue'),
                    ]),

                Tables\Columns\TextColumn::make('avg_order_value')
                    ->label('Avg Order Value')
                    ->money('USD')
                    ->getStateUsing(fn (Order $record) => $record->total)
                    ->summarize([
                        Tables\Columns\Summarizers\Average::make()
                            ->money('USD')
                            ->label('Average'),
                    ]),

                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date')
                            ->default(now()->startOfMonth()),

                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date')
                            ->default(now()->endOfMonth()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'From ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Until ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),

                Tables\Filters\SelectFilter::make('period')
                    ->label('Quick Period')
                    ->options([
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'this_week' => 'This Week',
                        'last_week' => 'Last Week',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                        'this_quarter' => 'This Quarter',
                        'last_quarter' => 'Last Quarter',
                        'this_year' => 'This Year',
                        'last_year' => 'Last Year',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'today' => $query->whereDate('created_at', today()),
                            'yesterday' => $query->whereDate('created_at', today()->subDay()),
                            'this_week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                            'last_week' => $query->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]),
                            'this_month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
                            'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year),
                            'this_quarter' => $query->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()]),
                            'last_quarter' => $query->whereBetween('created_at', [now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()]),
                            'this_year' => $query->whereYear('created_at', now()->year),
                            'last_year' => $query->whereYear('created_at', now()->subYear()->year),
                            default => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('product_type')
                    ->label('Product Type')
                    ->relationship('product', 'type')
                    ->options([
                        'hosting' => 'Hosting',
                        'domain' => 'Domain',
                        'ssl' => 'SSL Certificate',
                        'addon' => 'Add-on',
                        'service' => 'Service',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'semi_annually' => 'Semi-Annually',
                        'annually' => 'Annually',
                        'biennially' => 'Biennially',
                        'triennially' => 'Triennially',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->multiple()
                    ->default(['active', 'completed']),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Order $record): string => route('filament.admin.resources.orders.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_csv')
                    ->label('Export to CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($records) {
                        // TODO: Implement CSV export
                        \Filament\Notifications\Notification::make()
                            ->title('Export started')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\BulkAction::make('export_pdf')
                    ->label('Export to PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function ($records) {
                        // TODO: Implement PDF export
                        \Filament\Notifications\Notification::make()
                            ->title('PDF export started')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'product'])
            ->whereNotNull('created_at');
    }

    /**
     * Get revenue metrics
     */
    public static function getRevenueMetrics(array $filters = []): array
    {
        $query = static::getEloquentQuery();

        // Apply filters
        if (isset($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (isset($filters['until'])) {
            $query->whereDate('created_at', '<=', $filters['until']);
        }

        $orders = $query->get();

        return [
            'total_revenue' => $orders->sum('total'),
            'total_orders' => $orders->count(),
            'avg_order_value' => $orders->avg('total'),
            'total_customers' => $orders->pluck('user_id')->unique()->count(),
        ];
    }

    /**
     * Get product performance metrics
     */
    public static function getProductPerformance(array $filters = []): array
    {
        $query = static::getEloquentQuery();

        if (isset($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (isset($filters['until'])) {
            $query->whereDate('created_at', '<=', $filters['until']);
        }

        return $query
            ->selectRaw('product_id, COUNT(*) as order_count, SUM(total) as revenue')
            ->groupBy('product_id')
            ->with('product')
            ->get()
            ->map(fn ($item) => [
                'product' => $item->product?->name ?? 'Unknown',
                'orders' => $item->order_count,
                'revenue' => $item->revenue,
            ])
            ->toArray();
    }

    /**
     * Get MRR (Monthly Recurring Revenue)
     */
    public static function getMRR(): float
    {
        return static::getEloquentQuery()
            ->where('status', 'active')
            ->whereIn('billing_cycle', ['monthly'])
            ->sum('total');
    }

    /**
     * Get ARR (Annual Recurring Revenue)
     */
    public static function getARR(): float
    {
        $mrr = static::getMRR();

        $annualRevenue = static::getEloquentQuery()
            ->where('status', 'active')
            ->where('billing_cycle', 'annually')
            ->sum('total');

        $quarterlyRevenue = static::getEloquentQuery()
            ->where('status', 'active')
            ->where('billing_cycle', 'quarterly')
            ->sum('total') * 4;

        return ($mrr * 12) + $annualRevenue + $quarterlyRevenue;
    }

    /**
     * Get churn rate
     */
    public static function getChurnRate(string $period = 'monthly'): float
    {
        $startDate = match($period) {
            'monthly' => now()->subMonth(),
            'quarterly' => now()->subQuarter(),
            'yearly' => now()->subYear(),
            default => now()->subMonth(),
        };

        $activeAtStart = static::getEloquentQuery()
            ->where('created_at', '<=', $startDate)
            ->where('status', 'active')
            ->count();

        $churned = static::getEloquentQuery()
            ->where('created_at', '<=', $startDate)
            ->whereIn('status', ['cancelled', 'terminated'])
            ->where('cancelled_at', '>=', $startDate)
            ->count();

        if ($activeAtStart === 0) {
            return 0;
        }

        return ($churned / $activeAtStart) * 100;
    }

    /**
     * Get customer acquisition report
     */
    public static function getCustomerAcquisition(array $filters = []): array
    {
        $query = User::query();

        if (isset($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (isset($filters['until'])) {
            $query->whereDate('created_at', '<=', $filters['until']);
        }

        $customers = $query->get();

        return [
            'total_customers' => $customers->count(),
            'new_customers' => $customers->count(),
            'avg_customer_value' => Order::whereIn('user_id', $customers->pluck('id'))->avg('total'),
        ];
    }

    /**
     * Get payment method statistics
     */
    public static function getPaymentMethodStats(array $filters = []): array
    {
        $query = static::getEloquentQuery();

        if (isset($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (isset($filters['until'])) {
            $query->whereDate('created_at', '<=', $filters['until']);
        }

        return $query
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as revenue')
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($item) => [
                'method' => $item->payment_method ?? 'Unknown',
                'orders' => $item->count,
                'revenue' => $item->revenue,
            ])
            ->toArray();
    }
}
