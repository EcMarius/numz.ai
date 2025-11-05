<?php

namespace Wave\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmail extends Notification
{
    use Queueable;

    public $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        // Use simple URL with hash (no signed URL to avoid proxy/expiration issues)
        // The hash is verified in the controller against the user's email
        $url = url('/auth/verify-email/' . $notifiable->getKey() . '/' . sha1($notifiable->getEmailForVerification()));

        // Build platform list dynamically from active platform schemas
        $platforms = \App\Models\PlatformSchema::where('is_active', true)
            ->select('platform')
            ->distinct()
            ->pluck('platform')
            ->map(function($platform) {
                // Format platform names nicely (reddit -> Reddit, x -> X (Twitter), etc.)
                return match(strtolower($platform)) {
                    'reddit' => 'Reddit',
                    'x' => 'X (Twitter)',
                    'twitter' => 'X (Twitter)',
                    'linkedin' => 'LinkedIn',
                    'facebook' => 'Facebook',
                    default => ucfirst($platform)
                };
            })
            ->unique() // Remove duplicates after formatting
            ->values()
            ->toArray();

        // Build platform list string
        $platformList = count($platforms) > 0
            ? implode(', ', array_slice($platforms, 0, -1)) . (count($platforms) > 1 ? ', and ' : '') . end($platforms)
            : 'multiple platforms';

        \Log::info('Email verification URL generated', [
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
            'url' => $url,
            'platforms' => $platformList,
        ]);

        return (new MailMessage)
            ->subject('EvenLeads: Verify your email to start collecting leads.')
            ->greeting('Welcome to EvenLeads!')
            ->line('Thank you for signing up! You\'re just one step away from starting your first lead generation campaign.')
            ->line('Please verify your email address to unlock the full power of automated lead discovery across multiple platforms.')
            ->action('Verify Email Address', $url)
            ->line('Once verified, you\'ll be able to:')
            ->line('• Create and manage lead generation campaigns')
            ->line('• Discover qualified leads from ' . $platformList)
            ->line('• Engage with prospects automatically')
            ->line('• Track your campaign performance in real-time')
            ->line('If you didn\'t create an account with EvenLeads, no further action is required.');

    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
