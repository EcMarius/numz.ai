@component('mail::message')
# Your Feedback Has Been Rewarded!

Hi {{ $feedback->user->name }},

Great news! Your valuable feedback has been recognized and we want to thank you with a special reward.

**Your Feedback:**
- **Type:** {{ ucfirst(str_replace('_', ' ', $feedback->type)) }}
- **Subject:** {{ $feedback->subject }}

**Your Reward:**
{{ $rewardDetails }}

This reward has been automatically applied to your account. We truly appreciate you taking the time to help us improve EvenLeads!

Your insights help us build better features and create a better experience for everyone.

@component('mail::button', ['url' => config('app.url')])
Go to Dashboard
@endcomponent

Thank you for being an amazing part of our community!

Best regards,<br>
The {{ config('app.name') }} Team
@endcomponent
