<?php

use function Laravel\Folio\{name, middleware};
use Livewire\Volt\Component;

middleware(['guest']);
name('auth.oauth.consent');

new class extends Component
{
    public $accepted_terms = false;
    public $provider = '';
    public $userName = '';
    public $userEmail = '';

    public function mount()
    {
        // Check if OAuth data exists in session
        if (!session()->has('oauth_pending_user')) {
            return redirect()->route('auth.register');
        }

        $oauthData = session('oauth_pending_user');
        $this->provider = ucfirst($oauthData['driver']);
        $this->userName = $oauthData['name'];
        $this->userEmail = $oauthData['email'];
    }

    public function acceptAndContinue()
    {
        if (!$this->accepted_terms) {
            $this->addError('accepted_terms', 'You must accept the Terms and Conditions and Privacy Policy to continue.');
            return;
        }

        // Mark terms as accepted in session
        session()->put('oauth_terms_accepted', true);

        // Redirect back to OAuth callback to complete registration
        $driver = session('oauth_pending_user')['driver'];
        return redirect()->route('auth.callback', ['driver' => $driver]);
    }

    public function cancel()
    {
        session()->forget(['oauth_pending_user', 'oauth_terms_accepted', 'oauth_initiated', 'oauth_driver']);
        return redirect()->route('auth.register');
    }
};

?>

<x-layouts.marketing
    :seo="[
        'title' => 'Accept Terms - EvenLeads',
        'description' => 'Please accept our Terms and Conditions to continue.',
    ]"
>
    @volt('auth.oauth.consent')
        <x-container class="py-10 sm:py-20">
            <div class="max-w-md mx-auto">
                <div class="bg-white dark:bg-zinc-800 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 shadow-sm p-8">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <div class="mx-auto w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">
                            Welcome to EvenLeads
                        </h1>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            You're signing up with {{ $provider }}
                        </p>
                    </div>

                    <!-- User Info -->
                    <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-zinc-200 dark:bg-zinc-700 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-zinc-900 dark:text-white text-sm">{{ $userName }}</p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $userEmail }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Terms Checkbox -->
                    <form wire:submit.prevent="acceptAndContinue" class="space-y-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input wire:model="accepted_terms" id="accepted_terms" name="accepted_terms" type="checkbox" class="w-4 h-4 border-zinc-300 rounded text-zinc-900 focus:ring-zinc-900" required>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="accepted_terms" class="font-normal text-zinc-700">
                                    I agree to the <a href="{{ route('terms') }}" target="_blank" class="font-medium text-black hover:underline">Terms and Conditions</a> and <a href="{{ route('privacy') }}" target="_blank" class="font-medium text-black hover:underline">Privacy Policy</a>
                                </label>
                                @error('accepted_terms')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex flex-col gap-3">
                            <button type="submit" class="w-full px-6 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-medium rounded-lg hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-all">
                                Accept and Continue
                            </button>
                            <button type="button" wire:click="cancel" class="w-full px-6 py-3 bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-white font-medium rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-all">
                                Cancel
                            </button>
                        </div>
                    </form>

                    <!-- Info Note -->
                    <div class="mt-6 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-xs text-blue-800 dark:text-blue-200">
                            <strong>Note:</strong> By continuing, you're creating a new EvenLeads account. Your {{ $provider }} account will be linked to this new account.
                        </p>
                    </div>
                </div>
            </div>
        </x-container>
    @endvolt
</x-layouts.marketing>
