@component('mail::message')
# Thank You for Your Feedback!

Hi {{ $feedback->user->name }},

Thank you for taking the time to share your feedback with us! Your input is invaluable in helping us improve EvenLeads.

**Your Feedback:**
- **Type:** {{ ucfirst(str_replace('_', ' ', $feedback->type)) }}
- **Subject:** {{ $feedback->subject }}

We've received your message and our team will review it carefully. Quality feedback helps us build better features and improve your experience.

Keep an eye out - we may reward helpful feedback with extended trials or bonus credits!

@component('mail::button', ['url' => config('app.url')])
Go to Dashboard
@endcomponent

Thanks again for helping us improve!

Best regards,<br>
The {{ config('app.name') }} Team
@endcomponent
