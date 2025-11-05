@component('mail::message')
# Important: Price Change for Your {{ $plan->name }} Plan

Dear {{ $user->name }},

We're writing to inform you of an upcoming price change to your **{{ $plan->name }}** subscription.

@component('mail::panel')
**Current Price:** {{ $currentPrice }} {{ $currency }} per {{ $subscription->cycle }}
**New Price:** {{ $newPrice }} {{ $currency }} per {{ $subscription->cycle }}
**Increase:** {{ number_format($newPrice - $currentPrice, 2) }} {{ $currency }} ({{ number_format((($newPrice - $currentPrice) / $currentPrice) * 100, 1) }}%)
@endcomponent

## When Does This Take Effect?

This price change will take effect on **{{ $renewalDate }}** (your next renewal date). You have **{{ $daysUntilRenewal }} days** to review this change.

## Your Options

You have two choices:

### Option 1: Accept the New Price
If you're happy to continue at the new price, please log in to your dashboard and accept the change.

@component('mail::button', ['url' => $acceptUrl, 'color' => 'success'])
Review & Accept Price Change
@endcomponent

### Option 2: Cancel Your Subscription
If you prefer not to continue at the new price, you can cancel your subscription at any time before {{ $renewalDate }} with no penalty.

@component('mail::button', ['url' => $cancelUrl, 'color' => 'error'])
Cancel My Subscription
@endcomponent

## What Happens If I Don't Respond?

**Important:** If you do not accept the new price by {{ $renewalDate }}, your subscription will be **automatically paused** to prevent any unauthorized charges. You can reactivate it at any time by accepting the new price.

## Your Rights

- ✓ You have the right to cancel your subscription at any time before {{ $renewalDate }} without penalty
- ✓ No charges will be made at the new price without your explicit acceptance
- ✓ You can contact our support team with any questions: {{ config('mail.from.address') }}

---

**Why are we changing the price?**

We continuously invest in improving our service, adding new features, and providing better support. This price adjustment helps us maintain and enhance the quality of service you expect from us.

Thank you for being a valued customer,

{{ config('app.name') }} Team

---

<small style="color: #999; font-size: 11px;">
You are receiving this email because we are required by law to notify you of changes to your subscription pricing. This email was sent to {{ $user->email }} on {{ now()->format('F j, Y') }}. If you have questions, please contact support at {{ config('mail.from.address') }}.
</small>
@endcomponent
