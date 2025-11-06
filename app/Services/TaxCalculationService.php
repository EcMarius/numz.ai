<?php

namespace App\Services;

use App\Models\TaxRate;
use App\Models\TaxExemption;
use App\Models\User;

class TaxCalculationService
{
    /**
     * Calculate tax for an amount
     */
    public function calculateTax(
        float $amount,
        User $user,
        string $productType = 'hosting',
        ?string $country = null,
        ?string $state = null,
        ?string $zipCode = null
    ): array {
        // Check for tax exemption
        $exemption = $this->getUserExemption($user);
        if ($exemption && $exemption->appliesToCategory($productType)) {
            return [
                'subtotal' => $amount,
                'tax' => 0,
                'total' => $amount,
                'tax_rates' => [],
                'exempt' => true,
                'exemption_reason' => $exemption->exemption_type,
            ];
        }

        // Get user's location if not provided
        if (!$country) {
            $country = $user->country ?? config('app.default_country', 'US');
        }
        if (!$state) {
            $state = $user->state ?? null;
        }
        if (!$zipCode) {
            $zipCode = $user->zip_code ?? null;
        }

        // Get applicable tax rates
        $taxRates = $this->getApplicableTaxRates($country, $state, $zipCode, $productType);

        $totalTax = 0;
        $appliedRates = [];
        $compoundBase = $amount;

        // Apply non-compound taxes first
        foreach ($taxRates->where('is_compound', false) as $rate) {
            $taxAmount = $rate->calculateTax($amount);
            $totalTax += $taxAmount;

            $appliedRates[] = [
                'name' => $rate->name,
                'rate' => $rate->formatted_rate,
                'amount' => $taxAmount,
                'is_compound' => false,
            ];
        }

        // Apply compound taxes (on subtotal + previous taxes)
        foreach ($taxRates->where('is_compound', true) as $rate) {
            $compoundBase = $amount + $totalTax;
            $taxAmount = $rate->calculateTax($compoundBase);
            $totalTax += $taxAmount;

            $appliedRates[] = [
                'name' => $rate->name,
                'rate' => $rate->formatted_rate,
                'amount' => $taxAmount,
                'is_compound' => true,
            ];
        }

        return [
            'subtotal' => $amount,
            'tax' => round($totalTax, 2),
            'total' => round($amount + $totalTax, 2),
            'tax_rates' => $appliedRates,
            'exempt' => false,
        ];
    }

    /**
     * Get applicable tax rates for location and product type
     */
    protected function getApplicableTaxRates(
        ?string $country,
        ?string $state,
        ?string $zipCode,
        string $productType
    ) {
        return TaxRate::active()
            ->forLocation($country, $state)
            ->ordered()
            ->get()
            ->filter(function ($rate) use ($country, $state, $zipCode, $productType) {
                return $rate->appliesToLocation($country, $state, $zipCode)
                    && $rate->appliesToProductType($productType);
            });
    }

    /**
     * Get user's active tax exemption
     */
    protected function getUserExemption(User $user): ?TaxExemption
    {
        return TaxExemption::where('user_id', $user->id)
            ->approved()
            ->first();
    }

    /**
     * Validate VAT number (EU)
     */
    public function validateVATNumber(string $vatNumber, string $country): array
    {
        // Basic format validation
        $vatNumber = strtoupper(str_replace([' ', '-', '.'], '', $vatNumber));

        // Check country prefix
        if (!str_starts_with($vatNumber, strtoupper($country))) {
            return [
                'valid' => false,
                'error' => 'VAT number must start with country code',
            ];
        }

        // TODO: Integrate with VIES (VAT Information Exchange System) API
        // For now, just basic validation

        return [
            'valid' => true,
            'vat_number' => $vatNumber,
            'country' => $country,
        ];
    }

    /**
     * Calculate invoice tax
     */
    public function calculateInvoiceTax($invoice, User $user): void
    {
        $subtotal = $invoice->items->sum('total');
        $discount = $invoice->discount ?? 0;

        // Get user location
        $country = $user->country ?? config('app.default_country', 'US');
        $state = $user->state ?? null;

        // Calculate tax on (subtotal - discount)
        $taxableAmount = max(0, $subtotal - $discount);

        $taxResult = $this->calculateTax(
            $taxableAmount,
            $user,
            'hosting', // Default, could be refined per item
            $country,
            $state
        );

        $invoice->update([
            'subtotal' => $subtotal,
            'tax' => $taxResult['tax'],
            'total' => $subtotal - $discount + $taxResult['tax'],
        ]);
    }
}
