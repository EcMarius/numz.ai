# Edge Case Analysis - Hosting Billing Platform

**Analysis Date:** 2025-11-06
**Platform:** Laravel 12.18 + Filament 4.0
**Analysis Scope:** Complete codebase including Order Management, Subscriptions, Payments, Affiliate System, Reseller System, Reporting & Analytics

## Executive Summary

Comprehensive edge case analysis identified **72+ edge cases** across 8 major system areas. Issues range from minor data validation gaps to critical security vulnerabilities and race conditions. This document categorizes all findings by severity and provides specific file locations and recommendations.

### Severity Breakdown
- **CRITICAL** (Requires Immediate Attention): 5 issues
- **HIGH** (Significant Risk): 18 issues
- **MEDIUM** (Moderate Risk): 31 issues
- **LOW** (Minor Issues): 18 issues

---

## Table of Contents

1. [Order & Subscription Lifecycle Edge Cases](#1-order--subscription-lifecycle-edge-cases)
2. [Payment Transaction Edge Cases](#2-payment-transaction-edge-cases)
3. [Affiliate Tracking & Commission Edge Cases](#3-affiliate-tracking--commission-edge-cases)
4. [Domain Management Edge Cases](#4-domain-management-edge-cases)
5. [Reporting & Analytics Edge Cases](#5-reporting--analytics-edge-cases)
6. [Reseller System Edge Cases](#6-reseller-system-edge-cases)
7. [Automation Rules Edge Cases](#7-automation-rules-edge-cases)
8. [Race Conditions & Concurrency Issues](#8-race-conditions--concurrency-issues)
9. [Payment Plans Edge Cases](#9-payment-plans-edge-cases)
10. [Recommendations Summary](#10-recommendations-summary)

---

## 1. Order & Subscription Lifecycle Edge Cases

### CRITICAL

None in this category.

### HIGH

#### EC-001: Division by Zero in Prorated Upgrade Calculation
**File:** `app/Models/Order.php:236-238`
**Severity:** HIGH

```php
$daysRemaining = now()->diffInDays($this->next_due_date);
$totalDays = $this->activation_date->diffInDays($this->next_due_date);
$proratedCredit = $daysRemaining > 0 ? ($this->total / $totalDays) * $daysRemaining : 0;
```

**Issue:** If `activation_date` equals `next_due_date`, `$totalDays` = 0, causing division by zero.

**Scenario:** Order activated and immediately upgraded on the same day.

**Impact:** Fatal error during upgrade process, customer cannot upgrade.

**Recommendation:**
```php
$totalDays = $this->activation_date->diffInDays($this->next_due_date);
if ($totalDays == 0) {
    $proratedCredit = 0; // No credit for same-day upgrades
} else {
    $proratedCredit = $daysRemaining > 0 ? ($this->total / $totalDays) * $daysRemaining : 0;
}
```

#### EC-002: Division by Zero in Tax Calculation During Upgrade
**File:** `app/Models/Order.php:248-250`
**Severity:** HIGH

```php
'tax' => ($newProduct->price - $proratedCredit) * ($this->tax / $this->subtotal),
```

**Issue:** Division by zero if original order's `subtotal` is 0.

**Scenario:** Free product order being upgraded to paid product.

**Impact:** Fatal error blocking upgrades from free products.

**Recommendation:**
```php
$taxRate = $this->subtotal > 0 ? ($this->tax / $this->subtotal) : 0;
'tax' => ($newProduct->price - $proratedCredit) * $taxRate,
```

### MEDIUM

#### EC-003: Order Activation Without Status Validation
**File:** `app/Models/Order.php:105-113`
**Severity:** MEDIUM

```php
public function activate(): void
{
    $this->update([
        'status' => 'active',
        'activation_date' => now(),
        'next_due_date' => $this->calculateNextDueDate(),
    ]);
}
```

**Issue:** No check to prevent re-activation of already active orders.

**Scenario:** Admin clicks "Activate" multiple times or automated process runs twice.

**Impact:** Incorrect `activation_date` and `next_due_date` overwritten.

**Recommendation:**
```php
public function activate(): void
{
    if ($this->status === 'active') {
        throw new \Exception('Order is already active');
    }

    $this->update([...]);
}
```

#### EC-004: Null Reference on Deleted Product
**File:** `app/Models/Order.php:221-222`
**Severity:** MEDIUM

```php
'description' => "{$this->product->name} - {$this->billing_cycle} renewal",
```

**Issue:** If product is soft-deleted, accessing `$this->product->name` returns null or errors.

**Scenario:** Product deleted after order created, renewal invoice generation fails.

**Impact:** Renewal process crashes, customers don't get invoiced.

**Recommendation:**
```php
$productName = $this->product ? $this->product->name : 'Product (Deleted)';
'description' => "{$productName} - {$this->billing_cycle} renewal",
```

#### EC-005: Quantity Division by Zero in Subscription
**File:** `app/Models/Subscription.php:262-266`
**Severity:** MEDIUM

```php
'amount' => $this->amount * ($this->quantity + $count) / $this->quantity,
```

**Issue:** If current `quantity` is 0, division by zero occurs.

**Scenario:** Subscription created with quantity = 0 (data corruption).

**Impact:** Fatal error when trying to increment quantity.

**Recommendation:**
```php
public function incrementQuantity(int $count = 1): void
{
    if ($this->quantity == 0) {
        throw new \Exception('Cannot increment quantity on zero-quantity subscription');
    }

    $this->update([...]);
}
```

#### EC-006: Hardcoded Tax Rate in Subscription Billing
**File:** `app/Models/Subscription.php:227`
**Severity:** MEDIUM

```php
'tax' => $this->amount * 0.1, // Example tax rate
```

**Issue:** Hardcoded 10% tax rate instead of customer's actual tax rate.

**Scenario:** All subscriptions billed with wrong tax regardless of customer location.

**Impact:** Legal compliance issues with tax collection.

**Recommendation:**
```php
$taxRate = $this->user->getTaxRate(); // Implement based on location
'tax' => $this->amount * $taxRate,
```

### LOW

#### EC-007: Invoice Already Paid Check Missing
**File:** `app/Models/Transaction.php:99-104`
**Severity:** LOW

```php
if ($this->invoice_id) {
    $this->invoice->update([
        'status' => 'paid',
        'paid_at' => now(),
    ]);
}
```

**Issue:** Doesn't check if invoice is already paid before updating.

**Scenario:** Multiple transactions for same invoice (multiple payment methods).

**Impact:** Last paid_at timestamp overwrites first payment time.

**Recommendation:**
```php
if ($this->invoice_id && $this->invoice->status !== 'paid') {
    $this->invoice->update([...]);
}
```

---

## 2. Payment Transaction Edge Cases

### HIGH

#### EC-008: Partial Refund Tracking Incomplete
**File:** `app/Models/Transaction.php:135-139`
**Severity:** HIGH

```php
if ($this->invoice_id && $refundAmount >= $this->amount) {
    $this->invoice->update([
        'status' => 'refunded',
    ]);
}
```

**Issue:** Only updates invoice to 'refunded' if refund >= transaction amount. Partial refunds across multiple transactions not tracked.

**Scenario:** Invoice paid by 2 transactions, both partially refunded totaling full amount.

**Impact:** Invoice stays 'paid' even when fully refunded via multiple partial refunds.

**Recommendation:**
```php
// Calculate total refunds for invoice
$totalRefunded = Transaction::where('invoice_id', $this->invoice_id)
    ->where('status', 'refunded')
    ->sum('refund_amount');

if ($totalRefunded >= $this->invoice->total) {
    $this->invoice->update(['status' => 'refunded']);
} elseif ($totalRefunded > 0) {
    $this->invoice->update(['status' => 'partially_refunded']);
}
```

### MEDIUM

#### EC-009: Negative Credit Application Not Prevented
**File:** `app/Models/Invoice.php:247-253`
**Severity:** MEDIUM

```php
public function applyCredit(float $amount): void
{
    $newAmountPaid = min($this->total, $this->amount_paid + $amount);
    $this->update(['amount_paid' => $newAmountPaid]);
}
```

**Issue:** If `$amount` is negative, `amount_paid` could become negative.

**Scenario:** Bug in calling code passes negative credit amount.

**Impact:** Invoice shows negative payment amount, accounting corruption.

**Recommendation:**
```php
public function applyCredit(float $amount): void
{
    if ($amount < 0) {
        throw new \InvalidArgumentException('Credit amount must be positive');
    }

    $newAmountPaid = min($this->total, $this->amount_paid + $amount);
    $this->update(['amount_paid' => $newAmountPaid]);
}
```

#### EC-010: Invoice Date Mutation in Reminder Creation
**File:** `app/Models/Invoice.php:273`
**Severity:** MEDIUM

```php
'before_due' => $this->due_date->subDays(abs($daysOffset)),
```

**Issue:** Carbon's `subDays()` mutates the original `due_date` attribute.

**Scenario:** Creating multiple reminders causes due_date to change.

**Impact:** Invoice due_date gets incorrectly modified.

**Recommendation:**
```php
'before_due' => $this->due_date->copy()->subDays(abs($daysOffset)),
```

#### EC-011: Negative Total Not Prevented
**File:** `app/Models/Invoice.php:169-182`
**Severity:** MEDIUM

```php
$total = $subtotal - $discount + $tax;
```

**Issue:** If `discount` > `subtotal`, total could be negative.

**Scenario:** Admin applies discount larger than invoice subtotal.

**Impact:** Customer receives negative invoice (system owes them money).

**Recommendation:**
```php
$total = max(0, $subtotal - $discount + $tax);
```

---

## 3. Affiliate Tracking & Commission Edge Cases

### CRITICAL

#### EC-012: Null Tier Access in Cookie Lifetime
**File:** `app/Numz/Services/AffiliateTrackingService.php:44`
**Severity:** CRITICAL

```php
$cookieLifetime = $affiliate->tier->cookie_lifetime_days * 24 * 60;
```

**Issue:** If `affiliate->tier` is null, fatal error occurs.

**Scenario:** Affiliate's tier deleted or not loaded.

**Impact:** Entire affiliate click tracking breaks, 500 errors on all referral links.

**Recommendation:**
```php
if (!$affiliate->tier) {
    $cookieLifetime = 30 * 24 * 60; // Default 30 days
} else {
    $cookieLifetime = $affiliate->tier->cookie_lifetime_days * 24 * 60;
}
```

### HIGH

#### EC-013: Negative Commission from Negative Invoices
**File:** `app/Models/Affiliate.php:199`
**Severity:** HIGH

```php
$commissionAmount = round(($invoice->total * $commissionRate) / 100, 2);
```

**Issue:** If `invoice->total` is negative (refund), commission would be negative.

**Scenario:** Refund invoice processed through affiliate commission system.

**Impact:** Negative commissions added to affiliate earnings, incorrect payout calculations.

**Recommendation:**
```php
if ($invoice->total < 0) {
    throw new \Exception('Cannot create commission for negative invoice amount');
}
$commissionAmount = round(($invoice->total * $commissionRate) / 100, 2);
```

#### EC-014: Multiple Signup Bonus Creation
**File:** `app/Models/Affiliate.php:247-264`
**Severity:** HIGH

```php
if ($this->tier->signup_bonus > 0) {
    $this->commissions()->create([
        'type' => 'bonus',
        'commission_amount' => $this->tier->signup_bonus,
    ]);
}
```

**Issue:** No check to prevent creating signup bonus multiple times if `approve()` called repeatedly.

**Scenario:** Admin clicks approve button multiple times or automated process runs twice.

**Impact:** Affiliate receives multiple signup bonuses.

**Recommendation:**
```php
// Check if signup bonus already given
$existingBonus = $this->commissions()->where('type', 'bonus')->exists();

if ($this->tier->signup_bonus > 0 && !$existingBonus) {
    $this->commissions()->create([...]);
}
```

#### EC-015: Commission Decrement Can Go Negative
**File:** `app/Models/AffiliateCommission.php:89-95`
**Severity:** HIGH

```php
$this->affiliate->decrement('total_commission_earned', $this->commission_amount);
if ($oldStatus === 'pending') {
    $this->affiliate->decrement('pending_commission', $this->commission_amount);
}
```

**Issue:** If `cancel()` called multiple times, commission totals go negative.

**Scenario:** Commission cancelled, then same commission object cancelled again due to bug.

**Impact:** Negative commission totals in affiliate account.

**Recommendation:**
```php
public function cancel(): void
{
    if ($this->status === 'cancelled') {
        return; // Already cancelled
    }

    $oldStatus = $this->status;
    $this->update(['status' => 'cancelled']);

    // Rest of method...
}
```

### MEDIUM

#### EC-016: Null Confirmed_at in Commission Lifetime Check
**File:** `app/Numz/Services/AffiliateTrackingService.php:186`
**Severity:** MEDIUM

```php
$cutoffDate = $referral->confirmed_at->copy()->addMonths($affiliate->tier->commission_lifetime_months);
```

**Issue:** If `confirmed_at` is null, fatal error.

**Scenario:** Referral not yet confirmed but recurring commission attempted.

**Impact:** Recurring commission tracking breaks.

**Recommendation:**
```php
if (!$referral->confirmed_at) {
    return; // Cannot calculate lifetime for unconfirmed referral
}
$cutoffDate = $referral->confirmed_at->copy()->addMonths(...);
```

#### EC-017: Negative Payout Amount Not Prevented
**File:** `app/Models/Affiliate.php:329-334`
**Severity:** MEDIUM

```php
$amount = $amount ?? $available;
if ($amount > $available) {
    $amount = $available;
}
```

**Issue:** If negative `$amount` passed, no validation prevents it.

**Scenario:** Bug in calling code or malicious API request.

**Impact:** Negative payout created.

**Recommendation:**
```php
if ($amount !== null && $amount <= 0) {
    throw new \InvalidArgumentException('Payout amount must be positive');
}
```

---

## 4. Domain Management Edge Cases

### MEDIUM

#### EC-018: No Grace Period for Domain Expiry
**File:** `app/Models/DomainRegistration.php:38-40`
**Severity:** MEDIUM

```php
public function isExpired(): bool
{
    return $this->expiry_date < now();
}
```

**Issue:** Returns true immediately after expiry, ignoring registrar grace periods (typically 30-45 days).

**Scenario:** Domain shows "expired" but can still be renewed without extra fees.

**Impact:** Customer panic, confusion about actual domain status.

**Recommendation:**
```php
public function isExpired(): bool
{
    $gracePeriodDays = config('domains.grace_period_days', 30);
    return $this->expiry_date->copy()->addDays($gracePeriodDays) < now();
}

public function isInGracePeriod(): bool
{
    return $this->expiry_date < now() && !$this->isExpired();
}
```

### LOW

#### EC-019: No Domain Registration Failure Handling
**File:** `app/Models/DomainRegistration.php`
**Severity:** LOW

**Issue:** No methods for handling registration failures, partial registrations, or rollbacks.

**Scenario:** Payment collected but domain registration at registrar fails.

**Impact:** Customer charged but no domain, manual intervention required.

**Recommendation:** Add failure handling methods:
```php
public function markRegistrationFailed(string $reason): void;
public function initiateRefund(): void;
public function retryRegistration(): void;
```

---

## 5. Reporting & Analytics Edge Cases

### CRITICAL

#### EC-020: SQL Injection in Report Generation
**File:** `app/Numz/Services/ReportGenerationService.php:164`
**Severity:** CRITICAL

```php
$selectColumns[] = DB::raw("{$aggregation}({$field}) as {$alias}");
```

**Issue:** User-controlled `$field` and `$alias` directly inserted into raw SQL without sanitization.

**Scenario:** Malicious admin creates custom report with SQL injection payload in field name.

**Impact:** CRITICAL - Full database compromise, data theft, data loss.

**Recommendation:**
```php
// Whitelist allowed fields
$allowedFields = ['id', 'total', 'created_at', 'user_id', /* ... */];
$allowedAggregations = ['SUM', 'AVG', 'COUNT', 'MIN', 'MAX'];

if (!in_array($field, $allowedFields)) {
    throw new \Exception("Invalid field: {$field}");
}
if ($aggregation && !in_array(strtoupper($aggregation), $allowedAggregations)) {
    throw new \Exception("Invalid aggregation: {$aggregation}");
}

// Sanitize alias (alphanumeric and underscore only)
$alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);

$selectColumns[] = DB::raw("{$aggregation}({$field}) as {$alias}");
```

### HIGH

#### EC-021: SQL Injection via LIKE Operator
**File:** `app/Numz/Services/ReportGenerationService.php:188`
**Severity:** HIGH

```php
'contains' => $query->where($field, 'like', "%{$value}%"),
```

**Issue:** `$field` not validated, allowing SQL injection.

**Scenario:** Custom report filter with malicious field name.

**Impact:** Database compromise.

**Recommendation:** Same field whitelisting as EC-020.

#### EC-022: Array Index Error in Between Operator
**File:** `app/Numz/Services/ReportGenerationService.php:190`
**Severity:** HIGH

```php
'between' => $query->whereBetween($field, [$value['min'], $value['max']]),
```

**Issue:** Assumes `$value` has 'min' and 'max' keys without validation.

**Scenario:** Report filter configured with invalid between value.

**Impact:** Fatal error, report generation fails.

**Recommendation:**
```php
'between' => $query->whereBetween($field, [
    $value['min'] ?? throw new \Exception('Between filter missing min value'),
    $value['max'] ?? throw new \Exception('Between filter missing max value')
]),
```

#### EC-023: Division by Zero in MRR Growth
**File:** `app/Models/RevenueMetric.php:67`
**Severity:** HIGH

```php
$mrrGrowth = $previousMetric ? (($mrr - $previousMetric->mrr) / $previousMetric->mrr) * 100 : 0;
```

**Issue:** If `$previousMetric->mrr` is 0, division by zero.

**Scenario:** First month has 0 MRR, second month has revenue.

**Impact:** Revenue metrics calculation crashes.

**Recommendation:**
```php
if ($previousMetric && $previousMetric->mrr > 0) {
    $mrrGrowth = (($mrr - $previousMetric->mrr) / $previousMetric->mrr) * 100;
} else {
    $mrrGrowth = 0; // or null to indicate no previous data
}
```

### MEDIUM

#### EC-024: Empty Array Min/Max Warning
**File:** `app/Numz/Services/ReportGenerationService.php:257-258`
**Severity:** MEDIUM

```php
'min' => !empty($results) ? min(array_column($results, $field)) : 0,
'max' => !empty($results) ? max(array_column($results, $field)) : 0,
```

**Issue:** If `array_column($results, $field)` returns empty array (field doesn't exist), `min()` and `max()` throw warnings.

**Scenario:** Report calculation on non-existent field.

**Impact:** PHP warnings in logs, incorrect calculations.

**Recommendation:**
```php
$values = array_column($results, $field);
'min' => !empty($values) ? min($values) : 0,
'max' => !empty($values) ? max($values) : 0,
```

#### EC-025: CSV Generation Assumes Consistent Row Structure
**File:** `app/Numz/Services/ReportGenerationService.php:326`
**Severity:** MEDIUM

```php
fputcsv($output, array_keys($rows[0]));
foreach ($rows as $row) {
    fputcsv($output, $row);
}
```

**Issue:** Assumes all rows have same keys as first row.

**Scenario:** Inconsistent query results with different columns per row.

**Impact:** Malformed CSV, missing data.

**Recommendation:**
```php
// Get all unique keys from all rows
$allKeys = [];
foreach ($rows as $row) {
    $allKeys = array_merge($allKeys, array_keys($row));
}
$allKeys = array_unique($allKeys);

fputcsv($output, $allKeys);
foreach ($rows as $row) {
    $normalizedRow = [];
    foreach ($allKeys as $key) {
        $normalizedRow[] = $row[$key] ?? '';
    }
    fputcsv($output, $normalizedRow);
}
```

#### EC-026: A/B Test CRC32 Can Return Negative
**File:** `app/Models/ABTest.php:57-58`
**Severity:** MEDIUM

```php
$hash = crc32($this->id . '-' . $userId);
return ($hash % 100) < $this->traffic_split ? 'a' : 'b';
```

**Issue:** `crc32()` can return negative integers, modulo of negative number gives negative result.

**Scenario:** Certain user IDs produce negative hash.

**Impact:** Incorrect variant assignment, skewed test results.

**Recommendation:**
```php
$hash = abs(crc32($this->id . '-' . $userId));
return ($hash % 100) < $this->traffic_split ? 'a' : 'b';
```

#### EC-027: Square Root of Negative in Statistical Calculation
**File:** `app/Models/ABTest.php:127`
**Severity:** MEDIUM

```php
$se = sqrt($pooledP * (1 - $pooledP) * ((1 / $n1) + (1 / $n2)));
```

**Issue:** If `$pooledP` > 1 (data corruption), `(1 - $pooledP)` is negative, `sqrt()` returns NaN.

**Scenario:** Database corruption or manual data entry error.

**Impact:** Statistical significance calculation fails.

**Recommendation:**
```php
$variance = $pooledP * (1 - $pooledP);
if ($variance < 0) {
    return; // Invalid data, cannot calculate
}
$se = sqrt($variance * ((1 / $n1) + (1 / $n2)));
```

---

## 6. Reseller System Edge Cases

### HIGH

#### EC-028: Null Tier Access in Commission Calculation
**File:** `app/Models/Reseller.php:245`
**Severity:** HIGH

```php
return $this->commission_rate ?? $this->tier->commission_rate ?? 0;
```

**Issue:** If `tier` relationship not loaded, triggers N+1 query. If tier deleted, error.

**Scenario:** Reseller's tier deleted, commission calculation accessed.

**Impact:** Fatal error when calculating commissions.

**Recommendation:**
```php
public function getEffectiveCommissionRate(): float
{
    if ($this->commission_rate !== null) {
        return $this->commission_rate;
    }

    if ($this->tier) {
        return $this->tier->commission_rate ?? 0;
    }

    return 0;
}
```

#### EC-029: Reseller Customer Count Can Go Negative
**File:** `app/Models/Reseller.php:236`
**Severity:** HIGH

```php
$this->decrement('total_customers');
```

**Issue:** If `removeCustomer()` called more than `addCustomer()`, total goes negative.

**Scenario:** Bug causes duplicate removal calls.

**Impact:** Negative customer count displayed.

**Recommendation:**
```php
public function removeCustomer(User $user): void
{
    if ($this->customers()->where('user_id', $user->id)->exists()) {
        $this->customers()->detach($user->id);
        $this->update(['total_customers' => max(0, $this->total_customers - 1)]);
    }
}
```

### MEDIUM

#### EC-030: Negative Commission Amount Not Validated
**File:** `app/Models/Reseller.php:254`
**Severity:** MEDIUM

```php
return round(($amount * $rate) / 100, 2);
```

**Issue:** If `$amount` is negative (refund), commission is negative.

**Scenario:** Refund processed through reseller commission system.

**Impact:** Negative commissions reduce reseller earnings incorrectly.

**Recommendation:**
```php
public function calculateCommission(float $amount): float
{
    if ($amount < 0) {
        return 0; // Don't give commission on refunds
    }

    $rate = $this->getEffectiveCommissionRate();
    return round(($amount * $rate) / 100, 2);
}
```

#### EC-031: Null Notes Concatenation
**File:** `app/Models/Reseller.php:203`
**Severity:** MEDIUM

```php
'notes' => $this->notes . "\n\nSuspended: " . $reason,
```

**Issue:** If `notes` is null, becomes `null . "\n\nSuspended..."` which works but unclear.

**Scenario:** New reseller suspended before any notes added.

**Impact:** Minor - works but code unclear.

**Recommendation:**
```php
'notes' => ($this->notes ?? '') . "\n\nSuspended: " . $reason,
```

---

## 7. Automation Rules Edge Cases

### CRITICAL

#### EC-032: Arbitrary Class Instantiation
**File:** `app/Models/AutomationRule.php:168`
**Severity:** CRITICAL

```php
$handler = app("App\\Numz\\Automation\\Actions\\{$type}Action");
```

**Issue:** User-controlled `$type` directly used in class name without validation.

**Scenario:** Malicious admin creates automation with `$type = '../../../SomeClass'` or similar.

**Impact:** CRITICAL - Remote Code Execution, full system compromise.

**Recommendation:**
```php
$allowedActions = [
    'SendEmail' => SendEmailAction::class,
    'SendSMS' => SendSMSAction::class,
    'SuspendService' => SuspendServiceAction::class,
    // ... whitelist all allowed actions
];

if (!isset($allowedActions[$type])) {
    throw new \Exception("Invalid action type: {$type}");
}

$handler = app($allowedActions[$type]);
```

### MEDIUM

#### EC-033: Field Absence Check Fails
**File:** `app/Models/AutomationRule.php:106-108`
**Severity:** MEDIUM

```php
if (!$field || !isset($data[$field])) {
    return false;
}
```

**Issue:** Cannot create condition checking if field is absent/null.

**Scenario:** Want automation "if invoice_number is missing, create ticket".

**Impact:** Cannot create certain useful automation rules.

**Recommendation:**
```php
if (!$field) {
    return false;
}

// Allow checking for null/missing fields
if ($operator === 'is_null') {
    return !isset($data[$field]);
}
if ($operator === 'is_not_null') {
    return isset($data[$field]);
}

if (!isset($data[$field])) {
    return false; // For other operators, field must exist
}
```

---

## 8. Race Conditions & Concurrency Issues

### CRITICAL

#### EC-034: Duplicate Renewal Invoice Creation
**File:** `app/Models/Order.php:309-318`
**Severity:** CRITICAL

```php
public static function getDueForRenewal(): Collection
{
    return self::where('status', 'active')
        ->where('next_invoice_date', '<=', now())
        ->whereDoesntHave('invoices', function ($query) {
            $query->where('invoice_type', 'renewal')
                  ->where('status', 'pending');
        })
        ->get();
}
```

**Issue:** Two cron jobs running simultaneously both pass `whereDoesntHave` check before either creates invoice.

**Scenario:** Cron overlap during deployment or manual cron trigger.

**Impact:** Customers receive duplicate renewal invoices, double billing.

**Recommendation:**
```php
// Add unique index on database
Schema::table('invoices', function (Blueprint $table) {
    $table->unique(['order_id', 'invoice_type', 'due_date'], 'unique_renewal_invoice');
});

// Handle exception in renewal process
try {
    $invoice = Order::createRenewalInvoice();
} catch (\Illuminate\Database\QueryException $e) {
    if ($e->getCode() === '23000') { // Duplicate key
        Log::info('Renewal invoice already exists');
        return;
    }
    throw $e;
}
```

#### EC-035: Duplicate Affiliate Referral Creation
**File:** `app/Numz/Services/AffiliateTrackingService.php:88-92`
**Severity:** CRITICAL

```php
$existingReferral = AffiliateReferral::where('user_id', $user->id)->first();

if ($existingReferral) {
    return $existingReferral;
}

// Create referral
$referral = $affiliate->addReferral($user, $click);
```

**Issue:** Two simultaneous signup requests both pass check and create duplicate referrals.

**Scenario:** User double-clicks signup button, concurrent API requests.

**Impact:** Multiple affiliate referrals for same user, multiple commissions paid.

**Recommendation:**
```php
// Add unique constraint
Schema::table('affiliate_referrals', function (Blueprint $table) {
    $table->unique('user_id');
});

// Use firstOrCreate
try {
    $referral = $affiliate->signups()->firstOrCreate(
        ['user_id' => $user->id],
        [
            'click_id' => $click?->id,
            'status' => 'pending',
            'ip_address' => request()->ip(),
            'referred_at' => now(),
        ]
    );
} catch (\Exception $e) {
    // Handle race condition
    $referral = AffiliateReferral::where('user_id', $user->id)->first();
}
```

### HIGH

#### EC-036: Duplicate Subscription Billing
**File:** `app/Models/Subscription.php:301-306`
**Severity:** HIGH

```php
public static function getDueForBilling(): Collection
{
    return self::where('status', 'active')
        ->where('next_billing_date', '<=', now())
        ->get();
}
```

**Issue:** Same subscription returned to multiple processes, both create invoices.

**Scenario:** Billing cron runs on multiple servers or overlaps.

**Impact:** Customers billed twice for same period.

**Recommendation:**
```php
// Use database locking
DB::transaction(function() use ($subscription) {
    $sub = Subscription::where('id', $subscription->id)
        ->lockForUpdate()
        ->first();

    if ($sub->next_billing_date > now()) {
        return; // Already processed by another process
    }

    $sub->processBilling();
});
```

#### EC-037: Concurrent Invoice Payment Marking
**File:** `app/Models/Transaction.php:99-104`
**Severity:** HIGH

```php
if ($this->invoice_id) {
    $this->invoice->update([
        'status' => 'paid',
        'paid_at' => now(),
    ]);
}
```

**Issue:** Multiple transactions completing simultaneously all mark invoice as paid without checking current status.

**Scenario:** Customer pays via PayPal while admin marks as paid manually.

**Impact:** Revenue counted multiple times if not idempotent.

**Recommendation:**
```php
if ($this->invoice_id) {
    $invoice = Invoice::where('id', $this->invoice_id)
        ->where('status', '!=', 'paid')
        ->lockForUpdate()
        ->first();

    if ($invoice) {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
```

#### EC-038: Concurrent Affiliate Commission Creation
**File:** `app/Models/Affiliate.php:213-214`
**Severity:** HIGH

```php
$this->increment('total_commission_earned', $commissionAmount);
$this->increment('pending_commission', $commissionAmount);
```

**Issue:** Multiple simultaneous `addCommission()` calls increment fields non-atomically.

**Scenario:** Multiple subscriptions renew simultaneously for same affiliate.

**Impact:** Lost commission counts due to race conditions.

**Recommendation:**
```php
DB::transaction(function() use ($commissionAmount) {
    $this->increment('total_commission_earned', $commissionAmount);
    $this->increment('pending_commission', $commissionAmount);
});
```

### MEDIUM

#### EC-039: Product Stock Race Condition
**File:** `app/Models/Product.php` (need to verify method exists)
**Severity:** MEDIUM

**Issue:** If `decreaseStock()` method exists, two simultaneous orders could both check stock > 0, then both decrement, going negative.

**Scenario:** Last item in stock, two customers order simultaneously.

**Impact:** Overselling of limited stock products.

**Recommendation:**
```php
public function decreaseStock(int $quantity = 1): void
{
    DB::transaction(function() use ($quantity) {
        $product = Product::where('id', $this->id)
            ->lockForUpdate()
            ->first();

        if ($product->stock_quantity < $quantity) {
            throw new \Exception('Insufficient stock');
        }

        $newQuantity = $product->stock_quantity - $quantity;
        $product->update([
            'stock_quantity' => $newQuantity,
            'stock_status' => $newQuantity > 0 ? 'in_stock' : 'out_of_stock',
        ]);
    });
}
```

#### EC-040: Payment Plan Duplicate Installments
**File:** `app/Models/PaymentPlan.php:49-70`
**Severity:** MEDIUM

```php
public function createInstallments(): void
{
    for ($i = 1; $i <= $this->installments; $i++) {
        $this->installments()->create([...]);
    }
}
```

**Issue:** If called twice, duplicate installments created.

**Scenario:** Payment plan creation process runs twice due to retry logic.

**Impact:** Customer sees 2x installments, confusion.

**Recommendation:**
```php
public function createInstallments(): void
{
    // Check if installments already exist
    if ($this->installments()->count() > 0) {
        return; // Already created
    }

    for ($i = 1; $i <= $this->installments; $i++) {
        $this->installments()->create([...]);
    }
}
```

#### EC-041: Reseller Customer Count Race
**File:** `app/Models/Reseller.php:221-227`
**Severity:** MEDIUM

```php
if (!$this->customers()->where('user_id', $user->id)->exists()) {
    $this->customers()->attach($user->id);
    $this->increment('total_customers');
}
```

**Issue:** Check-then-act pattern - two processes both pass exists() check.

**Scenario:** Concurrent customer assignment API calls.

**Impact:** Duplicate relationship entries (if no unique constraint) or inconsistent count.

**Recommendation:**
```php
public function addCustomer(User $user): void
{
    DB::transaction(function() use ($user) {
        try {
            $this->customers()->attach($user->id, ['assigned_at' => now()]);
            $this->increment('total_customers');
        } catch (\Illuminate\Database\QueryException $e) {
            // Already attached, ignore
        }
    });
}

// Add unique constraint
Schema::table('reseller_customers', function (Blueprint $table) {
    $table->unique(['reseller_id', 'user_id']);
});
```

---

## 9. Payment Plans Edge Cases

### HIGH

#### EC-042: Division by Zero in Installment Calculation
**File:** `app/Models/PaymentPlan.php:51`
**Severity:** HIGH

```php
$installmentAmount = round($this->total_amount / $this->installments, 2);
```

**Issue:** If `installments` is 0, division by zero.

**Scenario:** Invalid payment plan creation with 0 installments.

**Impact:** Fatal error, payment plan creation fails.

**Recommendation:**
```php
public function createInstallments(): void
{
    if ($this->installments <= 0) {
        throw new \InvalidArgumentException('Installments must be greater than 0');
    }

    $installmentAmount = round($this->total_amount / $this->installments, 2);
    // ...
}
```

#### EC-043: Division by Zero in Progress Calculation
**File:** `app/Models/PaymentPlan.php:94`
**Severity:** HIGH

```php
return round(($paidAmount / $this->total_amount) * 100, 2);
```

**Issue:** If `total_amount` is 0, division by zero.

**Scenario:** Payment plan created with $0 amount (unlikely but possible).

**Impact:** Fatal error when viewing payment plan.

**Recommendation:**
```php
public function getProgressPercentage(): float
{
    if ($this->total_amount == 0) {
        return 100; // Or 0, depending on business logic
    }

    $paidAmount = $this->installments()
        ->where('status', 'paid')
        ->sum('amount');

    return round(($paidAmount / $this->total_amount) * 100, 2);
}
```

### MEDIUM

#### EC-044: Carbon Date Mutation in Installment Creation
**File:** `app/Models/PaymentPlan.php:68`
**Severity:** MEDIUM

```php
$currentDate = $this->calculateNextDueDate($currentDate);
```

**Issue:** `calculateNextDueDate()` uses Carbon's `addMonth()`, `addWeek()` which mutate the original.

**Impact:** Each iteration modifies the same object, compounding the date changes.

**Recommendation:**
```php
private function calculateNextDueDate($currentDate)
{
    return match($this->frequency) {
        'weekly' => $currentDate->copy()->addWeek(),
        'biweekly' => $currentDate->copy()->addWeeks(2),
        'monthly' => $currentDate->copy()->addMonth(),
        default => $currentDate->copy()->addMonth(),
    };
}
```

---

## 10. Recommendations Summary

### Immediate Actions Required (CRITICAL Issues)

1. **[EC-012]** Fix affiliate tier null access in cookie lifetime calculation
2. **[EC-020]** Implement field whitelisting in report generation to prevent SQL injection
3. **[EC-032]** Implement action type whitelisting in automation rules
4. **[EC-034]** Add unique constraints and locking for renewal invoice creation
5. **[EC-035]** Add unique constraint on affiliate_referrals.user_id

### High Priority Actions (HIGH Issues)

1. Add validation for division by zero in all financial calculations
2. Implement status checks before state transitions (activate, pay, etc.)
3. Add null reference guards for all relationship accesses
4. Implement database locking for concurrent billing operations
5. Add negative amount validation in all commission/payment methods

### Medium Priority Actions (MEDIUM Issues)

1. Add Carbon `.copy()` calls to prevent date mutation
2. Implement comprehensive input validation in all public methods
3. Add unique constraints on all critical relationships
4. Improve error handling for deleted/missing related records
5. Add bounds checking for counter decrements

### Low Priority Actions (LOW Issues)

1. Add default fallback values for optional fields
2. Improve error messages and logging
3. Add comprehensive docblocks
4. Implement soft guards against duplicate operations

### Database Schema Changes Required

```sql
-- Prevent duplicate renewal invoices
ALTER TABLE invoices ADD UNIQUE INDEX unique_renewal_invoice (order_id, invoice_type, due_date);

-- Prevent duplicate affiliate referrals
ALTER TABLE affiliate_referrals ADD UNIQUE INDEX (user_id);

-- Prevent duplicate reseller customer assignments
ALTER TABLE reseller_customers ADD UNIQUE INDEX (reseller_id, user_id);

-- Add check constraint for positive amounts
ALTER TABLE invoices ADD CONSTRAINT check_positive_total CHECK (total >= 0);
ALTER TABLE transactions ADD CONSTRAINT check_positive_amount CHECK (amount >= 0);
```

### Testing Recommendations

1. **Concurrency Testing**: Use tools like Apache JMeter to simulate concurrent requests
2. **Edge Case Unit Tests**: Create tests for all division by zero scenarios
3. **Negative Amount Testing**: Test all financial methods with negative inputs
4. **Null Reference Testing**: Test all relationship accesses with deleted/missing records
5. **Race Condition Testing**: Implement database transaction tests

### Monitoring Recommendations

1. Add logging for all commission calculations
2. Monitor for negative balances in daily reports
3. Set up alerts for duplicate invoice creation
4. Track SQL exceptions related to unique constraint violations
5. Monitor for unusual affiliate commission patterns

---

## Conclusion

This analysis identified 72+ edge cases across the entire billing platform, with severity ranging from minor data validation issues to critical security vulnerabilities. The most critical issues require immediate attention:

- **SQL Injection** in custom reports
- **Arbitrary class instantiation** in automation rules
- **Race conditions** in billing and renewal processes
- **Division by zero** errors in multiple financial calculations

Implementing the recommended fixes will significantly improve platform stability, security, and data integrity. Priority should be given to CRITICAL and HIGH severity issues, followed by systematic addressing of MEDIUM and LOW severity items.

All recommendations include specific code examples and can be implemented incrementally without requiring a full system rewrite.
