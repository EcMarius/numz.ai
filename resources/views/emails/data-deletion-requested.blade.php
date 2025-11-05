<x-mail::message>
# Data Deletion Request Received

Hello {{ $userName }},

We have received your request to delete your account and all associated data from EvenLeads.

**Confirmation Code:** `{{ $confirmationCode }}`
**Submitted On:** {{ $createdAt->format('F d, Y \a\t g:i A') }}

## What Happens Next?

Your request will be processed according to the following timeline:

1. **Review Period:** 1-2 business days
2. **Deletion Process:** Within 30 days of approval
3. **Confirmation:** You'll receive a final email once completed

## Important Information

### What Will Be Deleted

Once processed, the following data will be permanently removed:

- Your account profile and personal information
- All campaign data and lead information
- API keys and access tokens
- Payment information and subscription history
- All other associated user data

### Active Subscription Notice

@if($deletionRequest->user && $deletionRequest->user->subscriber())
**⚠️ Warning:** You currently have an active subscription. By proceeding with this deletion request, you will immediately lose access to all plan features and benefits. Your subscription will be cancelled automatically.
@endif

## Need to Cancel This Request?

If you submitted this request by mistake or have changed your mind, you can cancel it at any time before it's processed.

**To cancel:**

<x-mail::panel>
Send an email to **contact@evenleads.com** with the following information:

- **Subject:** Cancel Data Deletion Request
- **Confirmation Code:** {{ $confirmationCode }}
- **Your Email:** {{ $deletionRequest->email }}

We'll cancel your request immediately upon receiving your email.
</x-mail::panel>

## Security Notice

If you did **not** submit this request, please contact us immediately at contact@evenleads.com. Your account security may be compromised.

<x-mail::button :url="'mailto:contact@evenleads.com?subject=Cancel%20Data%20Deletion%20Request%20' . $confirmationCode">
Contact Support
</x-mail::button>

---

**Questions?** Reply to this email or contact us at contact@evenleads.com

Best regards,
The EvenLeads Team

---

<small style="color: #666;">
Confirmation Code: {{ $confirmationCode }} | Submitted: {{ $createdAt->format('M d, Y g:i A') }}
</small>
</x-mail::message>
