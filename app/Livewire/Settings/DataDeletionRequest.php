<?php

namespace App\Livewire\Settings;

use App\Models\DataDeletionRequest as DataDeletionRequestModel;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;

class DataDeletionRequest extends Component
{
    public $email = '';
    public $reason = '';
    public $submitted = false;
    public $confirmationCode = '';
    public $hasActivePlan = false;
    public $planDetails = '';
    public $confirmPlanLoss = false;

    public function mount()
    {
        // Pre-fill email if user is logged in
        if (Auth::check()) {
            $user = Auth::user();
            $this->email = $user->email;

            // Check if user has an active plan (including cancelled with remaining days)
            if ($user->subscribed()) {
                $this->hasActivePlan = true;
                $subscription = $user->subscription();

                if ($subscription->onGracePeriod()) {
                    $this->planDetails = "You have an active {$subscription->plan->name} plan (cancelled but valid until " .
                                        $subscription->ends_at->format('M d, Y') . ")";
                } else {
                    $this->planDetails = "You have an active {$subscription->plan->name} plan";
                }
            }
        }
    }

    protected function rules()
    {
        $rules = [
            'email' => 'required|email',
            'reason' => 'nullable|string|max:1000',
        ];

        // Require confirmation if user has active plan
        if ($this->hasActivePlan) {
            $rules['confirmPlanLoss'] = 'accepted';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'confirmPlanLoss.accepted' => 'You must confirm that you understand you will lose access to your plan.',
        ];
    }

    public function submit()
    {
        $this->validate();

        // Find user by email
        $user = User::where('email', $this->email)->first();

        // Create the deletion request
        $deletionRequest = DataDeletionRequestModel::create([
            'user_id' => $user?->id,
            'email' => $this->email,
            'facebook_user_id' => $user?->facebook_id ?? null,
            'reason' => $this->reason,
            'status' => 'pending',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        $this->confirmationCode = $deletionRequest->confirmation_code;
        $this->submitted = true;

        // Send confirmation email (optional - you can implement this)
        // Mail::to($this->email)->send(new DataDeletionRequestMail($deletionRequest));

        session()->flash('success', 'Your data deletion request has been submitted successfully.');
    }

    public function render()
    {
        return view('livewire.settings.data-deletion-request')
            ->layout('theme::components.layouts.app');
    }
}
