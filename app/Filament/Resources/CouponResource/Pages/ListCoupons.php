<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCoupons extends ListRecords
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Coupons'),

            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->active()),

            'expired' => Tab::make('Expired')
                ->modifyQueryUsing(fn (Builder $query) => $query->expired())
                ->badge(fn () => \App\Models\Coupon::expired()->count()),

            'exhausted' => Tab::make('Exhausted')
                ->modifyQueryUsing(fn (Builder $query) => $query->exhausted())
                ->badge(fn () => \App\Models\Coupon::exhausted()->count()),

            'inactive' => Tab::make('Inactive')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
        ];
    }
}
