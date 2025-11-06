<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CouponService
{
    /**
     * Validate coupon code
     */
    public function validateCoupon(
        string $code,
        User $user,
        array $cartItems = [],
        float $cartTotal = 0,
        bool $isFirstOrder = false,
        string $orderType = 'new'
    ): array {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return [
                'valid' => false,
                'error' => 'Invalid coupon code.',
            ];
        }

        if (!$coupon->isValid()) {
            return [
                'valid' => false,
                'error' => 'This coupon is no longer valid.',
            ];
        }

        if (!$coupon->canBeUsedBy($user, $isFirstOrder)) {
            return [
                'valid' => false,
                'error' => 'You are not eligible to use this coupon.',
            ];
        }

        // Check order type
        if ($orderType === 'new' && !$coupon->applies_to_new_orders) {
            return [
                'valid' => false,
                'error' => 'This coupon does not apply to new orders.',
            ];
        }

        if ($orderType === 'renewal' && !$coupon->applies_to_renewals) {
            return [
                'valid' => false,
                'error' => 'This coupon does not apply to renewals.',
            ];
        }

        // Check minimum order amount
        if ($coupon->minimum_order_amount && $cartTotal < $coupon->minimum_order_amount) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'Minimum order amount of $%s required.',
                    number_format($coupon->minimum_order_amount, 2)
                ),
            ];
        }

        // Check if applies to any cart items
        if ($cartItems && $coupon->product_ids) {
            $hasApplicableProduct = false;
            foreach ($cartItems as $item) {
                if ($coupon->appliesToProduct($item['product_id'] ?? 0)) {
                    $hasApplicableProduct = true;
                    break;
                }
            }

            if (!$hasApplicableProduct) {
                return [
                    'valid' => false,
                    'error' => 'This coupon does not apply to any items in your cart.',
                ];
            }
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'message' => sprintf('Coupon "%s" applied successfully!', $code),
        ];
    }

    /**
     * Calculate discount for cart
     */
    public function calculateCartDiscount(
        Coupon $coupon,
        array $cartItems,
        float $cartSubtotal
    ): array {
        $totalDiscount = 0;
        $itemDiscounts = [];

        // If coupon has specific products, only discount those
        if ($coupon->product_ids) {
            foreach ($cartItems as $item) {
                $productId = $item['product_id'] ?? 0;
                $itemTotal = $item['price'] * $item['quantity'];

                if ($coupon->appliesToProduct($productId)) {
                    $discount = $coupon->calculateDiscount($itemTotal);
                    $totalDiscount += $discount;
                    $itemDiscounts[$productId] = $discount;
                }
            }
        } else {
            // Apply to entire cart
            $totalDiscount = $coupon->calculateDiscount($cartSubtotal);
        }

        return [
            'total_discount' => round($totalDiscount, 2),
            'item_discounts' => $itemDiscounts,
            'coupon_code' => $coupon->code,
            'coupon_id' => $coupon->id,
            'formatted_discount' => '$' . number_format($totalDiscount, 2),
        ];
    }

    /**
     * Apply multiple coupons (with stacking logic)
     */
    public function applyMultipleCoupons(
        array $couponCodes,
        User $user,
        array $cartItems,
        float $cartSubtotal,
        bool $isFirstOrder = false,
        string $orderType = 'new'
    ): array {
        $validCoupons = [];
        $errors = [];
        $totalDiscount = 0;

        foreach ($couponCodes as $code) {
            $validation = $this->validateCoupon(
                $code,
                $user,
                $cartItems,
                $cartSubtotal - $totalDiscount, // Apply to remaining amount
                $isFirstOrder,
                $orderType
            );

            if (!$validation['valid']) {
                $errors[$code] = $validation['error'];
                continue;
            }

            $coupon = $validation['coupon'];

            // Check stacking with previously applied coupons
            if (!empty($validCoupons)) {
                $canStack = true;
                foreach ($validCoupons as $appliedCoupon) {
                    if (!$coupon->canStackWith($appliedCoupon)) {
                        $errors[$code] = 'This coupon cannot be combined with other coupons.';
                        $canStack = false;
                        break;
                    }
                }

                if (!$canStack) {
                    continue;
                }
            }

            $discount = $this->calculateCartDiscount($coupon, $cartItems, $cartSubtotal - $totalDiscount);
            $totalDiscount += $discount['total_discount'];
            $validCoupons[] = $coupon;
        }

        // Ensure discount doesn't exceed cart total
        $totalDiscount = min($totalDiscount, $cartSubtotal);

        return [
            'coupons' => $validCoupons,
            'total_discount' => round($totalDiscount, 2),
            'errors' => $errors,
            'final_total' => max(0, $cartSubtotal - $totalDiscount),
        ];
    }

    /**
     * Apply coupon to invoice
     */
    public function applyCouponToInvoice(
        Coupon $coupon,
        int $invoiceId,
        User $user,
        float $invoiceSubtotal,
        string $orderType = 'new'
    ): array {
        DB::beginTransaction();

        try {
            $discount = $coupon->calculateDiscount($invoiceSubtotal);

            // Record usage
            $usage = $coupon->recordUsage($user, $invoiceId, $discount, $orderType);

            DB::commit();

            return [
                'success' => true,
                'discount' => $discount,
                'usage' => $usage,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'error' => 'Failed to apply coupon: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get user's available coupons
     */
    public function getAvailableCouponsForUser(User $user, bool $isFirstOrder = false): Collection
    {
        return Coupon::active()
            ->get()
            ->filter(function (Coupon $coupon) use ($user, $isFirstOrder) {
                return $coupon->canBeUsedBy($user, $isFirstOrder);
            });
    }

    /**
     * Get coupon usage statistics
     */
    public function getCouponStats(Coupon $coupon): array
    {
        $totalUsages = $coupon->usages()->count();
        $totalDiscount = $coupon->usages()->sum('discount_amount');
        $uniqueUsers = $coupon->usages()->distinct('user_id')->count('user_id');
        $avgDiscount = $totalUsages > 0 ? $totalDiscount / $totalUsages : 0;

        $usagesByType = $coupon->usages()
            ->select('order_type', DB::raw('COUNT(*) as count'))
            ->groupBy('order_type')
            ->pluck('count', 'order_type')
            ->toArray();

        $remainingUses = $coupon->max_uses ? max(0, $coupon->max_uses - $coupon->uses_count) : null;

        return [
            'total_usages' => $totalUsages,
            'total_discount' => round($totalDiscount, 2),
            'unique_users' => $uniqueUsers,
            'average_discount' => round($avgDiscount, 2),
            'usages_by_type' => $usagesByType,
            'remaining_uses' => $remainingUses,
            'is_exhausted' => $coupon->max_uses && $coupon->uses_count >= $coupon->max_uses,
            'is_expired' => $coupon->expires_at && $coupon->expires_at->isPast(),
        ];
    }

    /**
     * Generate unique coupon code
     */
    public function generateUniqueCouponCode(string $prefix = '', int $length = 8): string
    {
        do {
            $code = $prefix . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
