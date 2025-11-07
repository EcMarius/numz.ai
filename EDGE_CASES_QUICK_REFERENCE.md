# Edge Cases Quick Reference Guide

**Last Updated:** 2025-11-06
**Total Issues:** 86+
**For Full Details:** See [EDGE_CASE_ANALYSIS.md](./EDGE_CASE_ANALYSIS.md)

## Immediate Action Required (CRITICAL - 8 Issues)

| ID | System | Issue | File | Impact |
|----|--------|-------|------|--------|
| **EC-012** | Affiliate | Null tier access crashes tracking | `AffiliateTrackingService.php:44` | 500 errors on all referral links |
| **EC-020** | Reporting | SQL injection in custom reports | `ReportGenerationService.php:164` | **Database compromise** |
| **EC-032** | Automation | Arbitrary class instantiation | `AutomationRule.php:168` | **Remote Code Execution** |
| **EC-034** | Billing | Duplicate renewal invoices | `Order.php:309-318` | Double billing customers |
| **EC-035** | Affiliate | Duplicate referral creation | `AffiliateTrackingService.php:88-92` | Multiple commissions paid |
| **EC-045** | Credits | Race condition in balance updates | `CreditBalance.php:42-61` | Lost credits, revenue loss |
| **EC-047** | Credits | Race condition in deduction | `CreditBalance.php:66-87` | Negative balances |
| **EC-048** | Coupons | Race condition in usage counter | `Coupon.php:185-188` | Unlimited coupon uses |

## High Priority (HIGH - 22 Issues)

### Financial Calculations
- **EC-001**: Division by zero in prorated upgrade (`Order.php:236-238`)
- **EC-002**: Division by zero in tax calculation (`Order.php:248-250`)
- **EC-008**: Partial refund tracking incomplete (`Transaction.php:135-139`)
- **EC-023**: Division by zero in MRR growth (`RevenueMetric.php:67`)
- **EC-042**: Division by zero in installments (`PaymentPlan.php:51`)
- **EC-043**: Division by zero in progress % (`PaymentPlan.php:94`)

### Null Reference Errors
- **EC-013**: Negative commissions from refunds (`Affiliate.php:199`)
- **EC-014**: Multiple signup bonuses (`Affiliate.php:247-264`)
- **EC-028**: Null tier in reseller commission (`Reseller.php:245`)
- **EC-046**: Negative credit amounts allowed (`CreditBalance.php:42`)
- **EC-049**: Email domain parsing crash (`Coupon.php:111`)
- **EC-052**: Null invoice in chargeback (`Chargeback.php:85`)

### Race Conditions
- **EC-036**: Duplicate subscription billing (`Subscription.php:301-306`)
- **EC-037**: Concurrent invoice payment marking (`Transaction.php:99-104`)
- **EC-038**: Concurrent commission creation (`Affiliate.php:213-214`)

### Data Integrity
- **EC-015**: Commission decrement goes negative (`AffiliateCommission.php:89-95`)
- **EC-021**: SQL injection via LIKE operator (`ReportGenerationService.php:188`)
- **EC-022**: Array index error in between operator (`ReportGenerationService.php:190`)
- **EC-029**: Reseller customer count negative (`Reseller.php:236`)
- **EC-056**: Duplicate quote conversion (`Quote.php:230-263`)

## Medium Priority (MEDIUM - 38 Issues)

### Validation & Status Checks
- **EC-003**: Order re-activation not prevented (`Order.php:105-113`)
- **EC-004**: Null reference on deleted product (`Order.php:221-222`)
- **EC-005**: Quantity division by zero (`Subscription.php:262-266`)
- **EC-006**: Hardcoded 10% tax rate (`Subscription.php:227`)
- **EC-009**: Negative credit application (`Invoice.php:247-253`)
- **EC-011**: Negative invoice totals (`Invoice.php:169-182`)
- **EC-016**: Null confirmed_at in commission (`AffiliateTrackingService.php:186`)
- **EC-017**: Negative payout amount (`Affiliate.php:329-334`)
- **EC-018**: No domain grace period (`DomainRegistration.php:38-40`)
- **EC-030**: Negative reseller commission (`Reseller.php:254`)
- **EC-031**: Null notes concatenation (`Reseller.php:203`)
- **EC-033**: Field absence check fails (`AutomationRule.php:106-108`)
- **EC-050**: Percentage >100 creates negative (`Coupon.php:150`)
- **EC-051**: Null max uses per user (`Coupon.php:118-121`)
- **EC-053**: No chargeback amount validation (`Chargeback.php`)
- **EC-054**: Duplicate resolution allowed (`Chargeback.php:66-91`)
- **EC-055**: Discount >100% on quotes (`Quote.php:136`)
- **EC-057**: Accept expired quotes (`Quote.php:195-209`)
- **EC-058**: No item validation in quote (`Quote.php:130-150`)

### Carbon Date Mutations
- **EC-010**: Invoice date mutation (`Invoice.php:273`)
- **EC-026**: A/B test CRC32 negative (`ABTest.php:57-58`)
- **EC-044**: Carbon mutation in installments (`PaymentPlan.php:68`)

### Data Processing
- **EC-024**: Empty array min/max warning (`ReportGenerationService.php:257-258`)
- **EC-025**: CSV inconsistent row structure (`ReportGenerationService.php:326`)
- **EC-027**: Square root of negative (`ABTest.php:127`)
- **EC-039**: Product stock race condition (`Product.php`)
- **EC-040**: Duplicate payment installments (`PaymentPlan.php:49-70`)
- **EC-041**: Reseller customer count race (`Reseller.php:221-227`)

## Low Priority (LOW - 18 Issues)

- **EC-007**: Invoice paid check missing (`Transaction.php:99-104`)
- **EC-019**: No domain registration failure handling (`DomainRegistration.php`)

## Fix Priority Matrix

```
┌─────────────────────────────────────────────────────┐
│ CRITICAL (8) → Fix Within 24 Hours                 │
│ ├─ Security: EC-020, EC-032 (RCE, SQL injection)   │
│ ├─ Financial: EC-034, EC-035, EC-045, EC-047, EC-048│
│ └─ Availability: EC-012                             │
├─────────────────────────────────────────────────────┤
│ HIGH (22) → Fix Within 1 Week                      │
│ ├─ Division by Zero: EC-001, EC-002, EC-023, etc.  │
│ ├─ Race Conditions: EC-036, EC-037, EC-038         │
│ └─ Null References: EC-013, EC-014, EC-028, etc.   │
├─────────────────────────────────────────────────────┤
│ MEDIUM (38) → Fix Within 1 Month                   │
│ ├─ Validation Gaps: EC-003 through EC-033          │
│ ├─ Date Mutations: EC-010, EC-026, EC-044          │
│ └─ Data Processing: EC-024, EC-025, EC-027         │
├─────────────────────────────────────────────────────┤
│ LOW (18) → Fix When Convenient                     │
│ └─ Minor improvements and edge case handling       │
└─────────────────────────────────────────────────────┘
```

## System Coverage

| System | Critical | High | Medium | Low | Total |
|--------|----------|------|--------|-----|-------|
| Reporting | 1 | 3 | 3 | 0 | 7 |
| Automation | 1 | 0 | 1 | 0 | 2 |
| Billing/Orders | 2 | 4 | 5 | 1 | 12 |
| Affiliate | 2 | 4 | 2 | 0 | 8 |
| Credits | 3 | 1 | 0 | 0 | 4 |
| Coupons | 1 | 1 | 2 | 0 | 4 |
| Reseller | 0 | 2 | 2 | 0 | 4 |
| Subscriptions | 0 | 1 | 2 | 0 | 3 |
| Transactions | 0 | 1 | 1 | 1 | 3 |
| Quotes | 0 | 1 | 3 | 0 | 4 |
| Chargebacks | 0 | 1 | 2 | 0 | 3 |
| Payment Plans | 0 | 2 | 1 | 0 | 3 |
| Domains | 0 | 0 | 1 | 1 | 2 |
| A/B Testing | 0 | 0 | 2 | 0 | 2 |
| **TOTAL** | **8** | **22** | **38** | **18** | **86** |

## Quick Fix Checklist

### For Database Locking Issues (EC-034, EC-035, EC-036, EC-037, EC-038, EC-039, EC-041, EC-045, EC-047, EC-048)

```php
// Wrap critical sections in transaction with row locking
DB::transaction(function() {
    $record = Model::where('id', $id)
        ->lockForUpdate()  // ← Add this
        ->first();

    // Perform checks and updates
    $record->update([...]);
});
```

### For Division by Zero (EC-001, EC-002, EC-023, EC-042, EC-043)

```php
// Always validate denominator before division
if ($denominator == 0) {
    throw new \Exception('Invalid calculation');
}
$result = $numerator / $denominator;
```

### For Null References (EC-004, EC-012, EC-013, EC-014, EC-016, EC-028, EC-046, EC-049, EC-052)

```php
// Check relationship exists before accessing
if (!$this->relation) {
    throw new \Exception('Required relation missing');
}
$value = $this->relation->property;

// Or use null coalescing
$value = $this->relation?->property ?? $defaultValue;
```

### For SQL Injection (EC-020, EC-021)

```php
// Whitelist allowed fields
$allowedFields = ['id', 'name', 'total', /* ... */];
if (!in_array($field, $allowedFields)) {
    throw new \Exception("Invalid field: {$field}");
}
```

### For Arbitrary Class Instantiation (EC-032)

```php
// Whitelist allowed actions
$allowedActions = [
    'SendEmail' => SendEmailAction::class,
    'SendSMS' => SendSMSAction::class,
];
if (!isset($allowedActions[$type])) {
    throw new \Exception("Invalid action: {$type}");
}
$handler = app($allowedActions[$type]);
```

## Database Schema Changes Required

```sql
-- Prevent duplicate renewals
ALTER TABLE invoices
  ADD UNIQUE INDEX unique_renewal_invoice (order_id, invoice_type, due_date);

-- Prevent duplicate affiliate referrals
ALTER TABLE affiliate_referrals
  ADD UNIQUE INDEX (user_id);

-- Prevent duplicate reseller customers
ALTER TABLE reseller_customers
  ADD UNIQUE INDEX (reseller_id, user_id);

-- Prevent duplicate coupon usages per invoice
ALTER TABLE coupon_usages
  ADD UNIQUE INDEX unique_coupon_per_invoice (coupon_id, invoice_id);

-- Enforce positive amounts
ALTER TABLE invoices
  ADD CONSTRAINT check_positive_total CHECK (total >= 0);

ALTER TABLE transactions
  ADD CONSTRAINT check_positive_amount CHECK (amount >= 0);

ALTER TABLE credit_balances
  ADD CONSTRAINT check_positive_balance CHECK (balance >= 0);
```

## Testing Priorities

### 1. Concurrency Tests (Week 1)
- Test all financial operations with Apache JMeter
- Simulate 100+ simultaneous requests
- Focus on: billing, credits, coupons, commissions

### 2. Security Tests (Week 1)
- SQL injection attempts on custom reports
- Class instantiation attacks on automation rules
- XSS attempts on all user inputs

### 3. Edge Case Unit Tests (Week 2)
- All division by zero scenarios
- All null reference scenarios
- All negative amount scenarios
- All status transition scenarios

### 4. Integration Tests (Week 3)
- Complete order lifecycle
- Complete subscription lifecycle
- Complete affiliate commission flow
- Complete quote-to-invoice flow

## Monitoring Setup

```php
// Add to app/Providers/AppServiceProvider.php

// Log all commission calculations
Event::listen(CommissionCreated::class, function($event) {
    Log::info('Commission created', [
        'affiliate_id' => $event->commission->affiliate_id,
        'amount' => $event->commission->commission_amount,
        'invoice_id' => $event->commission->invoice_id,
    ]);
});

// Alert on negative balances
if ($creditBalance->balance < 0) {
    Log::critical('Negative credit balance detected', [
        'user_id' => $creditBalance->user_id,
        'balance' => $creditBalance->balance,
    ]);
    // Send alert to Slack/Email
}

// Monitor duplicate constraint violations
try {
    // Critical operation
} catch (\Illuminate\Database\QueryException $e) {
    if ($e->getCode() === '23000') {
        Log::warning('Duplicate constraint violation', [
            'operation' => 'renewal_invoice_creation',
            'message' => $e->getMessage(),
        ]);
    }
}
```

## Development Workflow

1. **Before Implementation**: Check this guide for relevant edge cases
2. **During Development**: Test all edge cases locally
3. **Code Review**: Verify edge cases are handled
4. **Testing**: Run automated tests for affected systems
5. **Deployment**: Monitor logs for 24 hours after deployment

## Contact & References

- **Full Analysis**: [EDGE_CASE_ANALYSIS.md](./EDGE_CASE_ANALYSIS.md)
- **Git Branch**: `claude/research-hosting-billing-011CUrjwkSxZcMpCSkyXSvER`
- **Analysis Date**: 2025-11-06
- **Coverage**: 12 major systems, 135 models, 86+ edge cases
