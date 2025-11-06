<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use App\Services\CouponService;
use Filament\Resources\Pages\CreateRecord;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate coupon code if not provided
        if (empty($data['code'])) {
            $couponService = app(CouponService::class);
            $data['code'] = $couponService->generateUniqueCouponCode();
        }

        // Set created_by to current admin user
        if (empty($data['created_by'])) {
            $data['created_by'] = auth()->user()->name ?? 'System';
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
