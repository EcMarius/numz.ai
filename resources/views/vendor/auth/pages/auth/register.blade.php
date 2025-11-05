<?php

use Devdojo\Auth\Models\SocialProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;
use Devdojo\Auth\Helper;
use Devdojo\Auth\Traits\HasConfigs;
use function Laravel\Folio\{middleware, name};
use Illuminate\Support\Facades\RateLimiter;

if (!isset($_GET['preview']) || (isset($_GET['preview']) && $_GET['preview'] != true) || !app()->isLocal()) {
    middleware(['guest']);
}

name('auth.register');

new class extends Component
{
    use HasConfigs;

    public $name;
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $accepted_terms = false;

    public $showNameField = false;
    public $showEmailField = true;
    public $showPasswordField = false;
    public $showPasswordConfirmationField = false;
    public $showEmailRegistration = true;

    public function rules()
    {
        if (!$this->settings->enable_email_registration) {
            return [];
        }

        $nameValidationRules = [];
        if (config('devdojo.auth.settings.registration_include_name_field')) {
            $nameValidationRules = ['name' => 'required'];
        }

        $passwordValidationRules = ['password' => 'required|min:8'];
        if (config('devdojo.auth.settings.registration_include_password_confirmation_field')) {
            $passwordValidationRules['password'] .= '|confirmed';
        }
        return array_merge(
            $nameValidationRules,
            ['email' => 'required|email|unique:users'],
            $passwordValidationRules,
            ['accepted_terms' => 'required|accepted']
        );
    }

    public function messages()
    {
        return [
            'accepted_terms.required' => 'You must accept the Terms and Conditions and Privacy Policy to register.',
            'accepted_terms.accepted' => 'You must accept the Terms and Conditions and Privacy Policy to register.',
        ];
    }

    public function mount()
    {
        $this->loadConfigs();

        if (!$this->settings->registration_enabled) {
            session()->flash('error', config('devdojo.auth.language.register.registrations_disabled', 'Registrations are currently disabled.'));
            redirect()->route('auth.login');
            return;
        }

        if (!$this->settings->enable_email_registration) {
            $this->showEmailRegistration = false;
            $this->showNameField = false;
            $this->showEmailField = false;
            $this->showPasswordField = false;
            $this->showPasswordConfirmationField = false;
            return;
        }

        if ($this->settings->registration_include_name_field) {
            $this->showNameField = true;
        }

        if ($this->settings->registration_show_password_same_screen) {
            $this->showPasswordField = true;

            if ($this->settings->registration_include_password_confirmation_field) {
                $this->showPasswordConfirmationField = true;
            }
        }
    }

    public function register()
    {
        // Rate limiting: 5 registrations per 5 minutes per IP (generous limit)
        $key = 'register-attempt:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', 'Too many registration attempts. Please try again in ' . ceil($seconds / 60) . ' minute(s).');
            return;
        }

        if (!$this->settings->registration_enabled) {
            session()->flash('error', config('devdojo.auth.language.register.registrations_disabled', 'Registrations are currently disabled.'));
            return redirect()->route('auth.login');
        }

        if (!$this->settings->enable_email_registration) {
            session()->flash('error', config('devdojo.auth.language.register.email_registration_disabled', 'Email registration is currently disabled. Please use social login.'));
            return redirect()->route('auth.register');
        }

        if (!$this->showPasswordField) {
            if ($this->settings->registration_include_name_field) {
                $this->validateOnly('name');
            }
            $this->validateOnly('email');

            $this->showPasswordField = true;
            if ($this->settings->registration_include_password_confirmation_field) {
                $this->showPasswordConfirmationField = true;
            }
            $this->showNameField = false;
            $this->showEmailField = false;
            $this->js("setTimeout(function(){ window.dispatchEvent(new CustomEvent('focus-password', {})); }, 10);");
            return;
        }

        $this->validate();

        // Increment rate limiter for actual registration attempts (after validation)
        RateLimiter::hit($key, 300); // 5 minutes decay

        $userData = [
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'accepted_terms_at' => now(),
            'avatar' => null, // Explicitly set to null to use Wave Setting default profile photo
        ];

        if ($this->settings->registration_include_name_field) {
            $userData['name'] = $this->name;
        }

        $user = app(config('auth.providers.users.model'))->create($userData);

        event(new Registered($user));

        Auth::login($user, true);

        // Clear rate limiter on successful registration
        RateLimiter::clear($key);

        if (config('devdojo.auth.settings.registration_require_email_verification')) {
            return redirect()->route('verification.notice');
        }

        if (session()->get('url.intended') != route('logout.get')) {
            session()->regenerate();
            redirect()->intended(config('devdojo.auth.settings.redirect_after_auth'));
        } else {
            session()->regenerate();
            return redirect(config('devdojo.auth.settings.redirect_after_auth'));
        }
    }
};

?>

<x-auth::layouts.app title="{{ config('devdojo.auth.language.register.page_title') }}">

    @volt('auth.register')
    <x-auth::elements.container>

        <x-auth::elements.heading :text="($language->register->headline ?? 'No Heading')" :description="($language->register->subheadline ?? 'No Description')" :show_subheadline="($language->register->show_subheadline ?? false)" />
        <x-auth::elements.session-message />

        {{-- Social auth is rendered by the plugin hook below - don't duplicate it here --}}

        @if($showEmailRegistration)
        <form wire:submit="register" class="space-y-5">

            @if($showNameField)
            <x-auth::elements.input :label="config('devdojo.auth.language.register.name')" type="text" wire:model="name" autofocus="true" required />
            @endif

            @if($showEmailField)
            @php
            $autofocusEmail = ($showNameField) ? false : true;
            @endphp
            <x-auth::elements.input :label="config('devdojo.auth.language.register.email_address')" id="email" name="email" type="email" wire:model="email" data-auth="email-input" :autofocus="$autofocusEmail" autocomplete="email" required />
            @endif

            @if($showPasswordField)
            <x-auth::elements.input :label="config('devdojo.auth.language.register.password')" type="password" wire:model="password" id="password" name="password" data-auth="password-input" autocomplete="new-password" required />
            @endif

            @if($showPasswordConfirmationField)
            <x-auth::elements.input :label="config('devdojo.auth.language.register.password_confirmation')" type="password" wire:model="password_confirmation" id="password_confirmation" name="password_confirmation" data-auth="password-confirmation-input" autocomplete="new-password" required />
            @endif

            @if($showPasswordField)
            <div class="flex items-center h-5">
                <input type="checkbox" wire:model="accepted_terms" id="accepted_terms" name="accepted_terms" class="hidden peer" required>
                <label for="accepted_terms" class="peer-checked:[&_svg]:scale-100 text-sm font-medium text-neutral-600 peer-checked:text-gray-800 [&_svg]:scale-0 peer-checked:[&_.custom-checkbox]:border-gray-800 peer-checked:[&_.custom-checkbox]:bg-gray-800 select-none flex items-start space-x-2">
                    <span class="flex justify-center items-center w-5 h-5 rounded border border-gray-300 custom-checkbox text-neutral-900 flex-shrink-0 mt-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-3 h-3 text-white duration-300 ease-out">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </span>
                    <span>I agree to the <a href="{{ route('terms') }}" target="_blank" class="font-semibold text-black underline decoration-2 hover:text-zinc-600">Terms and Conditions</a> and <a href="{{ route('privacy') }}" target="_blank" class="font-semibold text-black underline decoration-2 hover:text-zinc-600">Privacy Policy</a></span>
                </label>
                @error('accepted_terms')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <x-auth::elements.button data-auth="submit-button" rounded="md" submit="true">{{config('devdojo.auth.language.register.button')}}</x-auth::elements.button>
        </form>
        @endif

        <div class="@if(config('devdojo.auth.settings.social_providers_location') != 'top' && $showEmailRegistration){{ 'mt-3' }}@endif space-x-0.5 text-sm leading-5 @if(config('devdojo.auth.settings.center_align_text')){{ 'text-center' }}@else{{ 'text-left' }}@endif" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
            <span class="opacity-[47%]">{{config('devdojo.auth.language.register.already_have_an_account')}}</span>
            <x-auth::elements.text-link data-auth="login-link" href="{{ route('auth.login') }}">{{config('devdojo.auth.language.register.sign_in')}}</x-auth::elements.text-link>
        </div>

        {{-- Social auth is rendered by the plugin hook below - don't duplicate it here --}}

        {{-- Plugin Auth Methods Hook --}}
        <!-- PLUGIN AUTH HOOK START -->
        @if(app()->bound(\Wave\Plugins\PluginManager::class))
            @php
                $pluginManager = app(\Wave\Plugins\PluginManager::class);
                $authPlugins = $pluginManager->getAuthPlugins();
            @endphp
            <!-- Found {{ count($authPlugins) }} auth plugins -->
            @foreach($authPlugins as $plugin)
                <!-- Rendering auth buttons for: {{ get_class($plugin) }} -->
                {!! $plugin->renderAuthButtons('register') !!}
            @endforeach
        @else
            <!-- PluginManager not bound -->
        @endif
        <!-- PLUGIN AUTH HOOK END -->

    </x-auth::elements.container>
    @endvolt

</x-auth::layouts.app>
