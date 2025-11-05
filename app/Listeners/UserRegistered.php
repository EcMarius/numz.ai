<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserRegistered
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        Log::info('Registered event fired', [
            'user_id' => $user->id,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'event_class' => get_class($event),
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ]);

        // Send email verification notification if user needs to verify
        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            // Check if we already sent a verification email recently (within last 5 minutes)
            $cacheKey = 'verification_email_sent_' . $user->id;

            if (Cache::has($cacheKey)) {
                Log::warning('Verification email already sent recently, skipping duplicate', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
                return;
            }

            // Send the verification email
            $user->sendEmailVerificationNotification();

            // Set cache to prevent duplicate sends (5 minutes)
            Cache::put($cacheKey, true, now()->addMinutes(5));

            Log::info('Verification email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } else {
            Log::info('Verification email not needed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'is_must_verify' => $user instanceof MustVerifyEmail,
                'has_verified' => $user->hasVerifiedEmail(),
            ]);
        }

        // Perform any additional functionality to the user here...
    }
}
