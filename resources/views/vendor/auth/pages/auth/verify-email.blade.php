<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use function Laravel\Folio\{middleware, name};
use Livewire\Volt\Component;
use Devdojo\Auth\Traits\HasConfigs;

middleware(['auth']);
name('verification.notice');

new class extends Component
{
    use HasConfigs;

    public $resendStatus = '';

    public function mount()
    {
        $this->loadConfigs();

        // If already verified, redirect
        if (auth()->user()->hasVerifiedEmail()) {
            return redirect()->intended(config('devdojo.auth.settings.redirect_after_auth', '/dashboard'));
        }
    }

    public function resendVerification()
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            $this->resendStatus = 'already_verified';
            return redirect()->intended(config('devdojo.auth.settings.redirect_after_auth', '/dashboard'));
        }

        // Send verification email
        $user->sendEmailVerificationNotification();

        $this->resendStatus = 'sent';
    }

    public function logout()
    {
        Auth::logout();
        Session::flush();
        return redirect()->route('auth.login');
    }
};

?>

<x-auth::layouts.app title="Verify Email Address">

    @volt('auth.verify-email')
    <x-auth::elements.container>

        <!-- Heading -->
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-zinc-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-zinc-900 mb-2">
                Verify Your Email Address
            </h1>
            <p class="text-sm text-zinc-600">
                We've sent a verification link to your email
            </p>
        </div>

        <!-- Info Message -->
        <div class="mb-6 p-5 bg-zinc-50 border border-zinc-200 rounded-lg">
            <p class="text-sm text-zinc-700 mb-3">
                Before you can start using EvenLeads and create your first lead generation campaign, we need to verify your email address.
            </p>
            <p class="text-sm text-zinc-700">
                Please check your inbox at <span class="font-semibold">{{ auth()->user()->email }}</span> and click the verification link we sent you.
            </p>
        </div>

        <!-- Success Message -->
        @if($resendStatus === 'sent')
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-green-800">
                    A new verification link has been sent to your email address. Please check your inbox and spam folder.
                </p>
            </div>
        </div>
        @endif

        <!-- Resend Button -->
        <div class="space-y-3 mb-6">
            <x-auth::elements.button wire:click="resendVerification" rounded="md" size="md" full="true">
                @if($resendStatus === 'sent')
                    Resend Verification Email Again
                @else
                    Resend Verification Email
                @endif
            </x-auth::elements.button>
        </div>

        <!-- Additional Info -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="text-sm font-semibold text-blue-900 mb-2">
                Didn't receive the email?
            </h3>
            <ul class="text-sm text-blue-800 space-y-1">
                <li class="flex items-start gap-2">
                    <span class="text-blue-500 mt-0.5">•</span>
                    <span>Check your spam or junk folder</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-500 mt-0.5">•</span>
                    <span>Make sure {{ auth()->user()->email }} is the correct email address</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-500 mt-0.5">•</span>
                    <span>Click the button above to resend the verification email</span>
                </li>
            </ul>
        </div>

        <!-- Logout Link -->
        <div class="text-center">
            <button wire:click="logout" class="text-sm text-zinc-600 hover:text-zinc-900 underline">
                Sign out and try with a different account
            </button>
        </div>

    </x-auth::elements.container>
    @endvolt

</x-auth::layouts.app>
