<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Services\GrowthHacking\TrialActivationService;

class ActivateTrialOnLogin
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected TrialActivationService $trialActivationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Check if this is a growth hack account and trial hasn't been activated yet
        if ($user->is_growth_hack_account && !$user->trial_activated_at) {
            $this->trialActivationService->activateTrialOnFirstLogin($user);
        }
    }
}
