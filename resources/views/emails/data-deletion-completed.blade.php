<x-mail::message>
# Data Deletion Completed

Hello {{ $userName }},

This email confirms that your data deletion request has been successfully processed and completed.

**Confirmation Code:** `{{ $confirmationCode }}`
**Completed On:** {{ $completedAt->format('F d, Y \a\t g:i A') }}

## What Has Been Deleted

All your personal data and account information have been permanently removed from our systems, including:

- Your account profile and settings
- Campaign data and lead information
- API keys and access tokens
- All associated user data

## Important Notes

- This action is permanent and cannot be undone
- You will no longer be able to access your previous account
- Any active subscriptions have been cancelled
- If you wish to use EvenLeads in the future, you will need to create a new account

## Need Help?

If you have any questions or did not request this deletion, please contact us immediately:

<x-mail::button :url="'mailto:contact@evenleads.com'">
Contact Support
</x-mail::button>

Thank you for using EvenLeads.

Best regards,
The EvenLeads Team

---

<small style="color: #666;">
This is an automated confirmation email. Please do not reply to this message.
If you need assistance, contact us at contact@evenleads.com
</small>
</x-mail::message>
