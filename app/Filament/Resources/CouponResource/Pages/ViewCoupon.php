<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use App\Services\CouponService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewCoupon extends ViewRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $couponService = app(CouponService::class);
        $stats = $couponService->getCouponStats($this->record);

        return $infolist
            ->schema([
                Components\Section::make('Coupon Information')
                    ->schema([
                        Components\TextEntry::make('code')
                            ->copyable(),
                        Components\TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'percentage' => 'success',
                                'fixed' => 'warning',
                                'credits' => 'primary',
                                default => 'gray',
                            }),
                        Components\TextEntry::make('formatted_value')
                            ->label('Value'),
                        Components\TextEntry::make('description')
                            ->columnSpanFull(),
                        Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                    ])
                    ->columns(2),

                Components\Section::make('Usage Statistics')
                    ->schema([
                        Components\TextEntry::make('total_usages')
                            ->label('Total Uses')
                            ->state($stats['total_usages']),
                        Components\TextEntry::make('unique_users')
                            ->label('Unique Users')
                            ->state($stats['unique_users']),
                        Components\TextEntry::make('total_discount')
                            ->label('Total Discount Given')
                            ->money('usd')
                            ->state($stats['total_discount']),
                        Components\TextEntry::make('average_discount')
                            ->label('Average Discount')
                            ->money('usd')
                            ->state($stats['average_discount']),
                        Components\TextEntry::make('remaining_uses')
                            ->label('Remaining Uses')
                            ->state($stats['remaining_uses'] ?? 'Unlimited'),
                    ])
                    ->columns(3),

                Components\Section::make('Restrictions')
                    ->schema([
                        Components\TextEntry::make('minimum_order_amount')
                            ->money('usd')
                            ->label('Minimum Order Amount'),
                        Components\TextEntry::make('starts_at')
                            ->dateTime(),
                        Components\TextEntry::make('expires_at')
                            ->dateTime()
                            ->badge()
                            ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success'),
                        Components\IconEntry::make('applies_to_new_orders')
                            ->boolean(),
                        Components\IconEntry::make('applies_to_renewals')
                            ->boolean(),
                        Components\IconEntry::make('is_recurring')
                            ->boolean()
                            ->label('Recurring'),
                        Components\IconEntry::make('first_order_only')
                            ->boolean(),
                        Components\IconEntry::make('can_stack')
                            ->boolean()
                            ->label('Can Stack'),
                    ])
                    ->columns(4),
            ]);
    }
}
