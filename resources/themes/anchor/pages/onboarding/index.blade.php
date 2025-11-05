<?php
use function Laravel\Folio\{middleware, name};
use Livewire\Volt\Component;

middleware(['auth', 'verified']);
name('onboarding');

new class extends Component {
    public $name = '';
    public $occupation = '';
    public $occupation_other = '';
    public $country = '';
    public $referral_source = '';
    public $email_on_leads_found = false;
    public $step = 1;

    public function mount()
    {
        $user = auth()->user();

        // If user already completed onboarding, redirect to plan selection
        if ($user->onboarding_completed) {
            return redirect()->route('plan-selection');
        }

        // Pre-fill if data exists
        $this->name = $user->name ?? '';
        $this->occupation = $user->occupation ?? '';
        $this->country = $user->country ?? '';
        $this->referral_source = $user->referral_source ?? '';

        // Country will be auto-detected on client-side using browser's navigator API
    }

    public function saveAndContinue()
    {
        // Validate name (required)
        $this->validate([
            'name' => 'required|min:2',
        ], [
            'name.required' => 'Please enter your name before continuing.',
            'name.min' => 'Your name must be at least 2 characters.',
        ]);

        $user = auth()->user();

        // Save name (required)
        $user->name = $this->name;

        // Handle occupation
        if ($this->occupation === 'Other' && !empty($this->occupation_other)) {
            $user->occupation = $this->occupation_other;
        } elseif (!empty($this->occupation) && $this->occupation !== 'Other') {
            $user->occupation = $this->occupation;
        }

        if (!empty($this->country)) {
            $user->country = $this->country;
        }

        if (!empty($this->referral_source)) {
            $user->referral_source = $this->referral_source;
        }

        // Save email notification preference
        $user->email_on_leads_found = $this->email_on_leads_found;

        $user->onboarding_completed = true;
        $user->save();

        // Redirect to plan selection
        return redirect()->route('plan-selection');
    }

    public function skipForNow()
    {
        // Name is required even when skipping
        $this->validate([
            'name' => 'required|min:2',
        ], [
            'name.required' => 'Please enter your name before continuing.',
            'name.min' => 'Your name must be at least 2 characters.',
        ]);

        $user = auth()->user();

        // Save name (required)
        $user->name = $this->name;

        $user->onboarding_completed = true;
        $user->save();

        return redirect()->route('plan-selection');
    }
}
?>

<x-auth::layouts.app title="Welcome to EvenLeads">
    @volt('onboarding')
    <x-auth::elements.container x-data="{
        countryDetected: false,
        detectCountry() {
            if (this.countryDetected || $wire.country) return;

            // Try to detect country from browser locale
            try {
                const locale = navigator.language || navigator.userLanguage;
                if (locale) {
                    // Extract country code from locale (e.g., 'en-US' -> 'US')
                    const parts = locale.split('-');
                    if (parts.length > 1) {
                        const countryCode = parts[1].toUpperCase();
                        $wire.set('country', countryCode);
                        this.countryDetected = true;
                    }
                }
            } catch (e) {
                // Silently fail - user can select manually
                console.log('Could not detect country from browser');
            }
        }
    }" x-init="detectCountry()">
        <!-- Heading -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                Welcome to EvenLeads!
            </h1>
            <p class="mt-3 text-sm opacity-60" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                Help us personalize your experience
            </p>
        </div>

        <!-- Onboarding Form -->
        <form wire:submit.prevent="saveAndContinue" class="space-y-6">
            <!-- Required Section: Name -->
            <div class="pb-6 border-b" style="border-color:{{ config('devdojo.auth.appearance.color.border') }}">
                <div class="mb-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide opacity-70" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                        Required Information
                    </h2>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                        Your Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model="name"
                        placeholder="Enter your full name"
                        required
                        class="w-full rounded-md border focus:ring-2 focus:ring-offset-2"
                        style="border-color:{{ config('devdojo.auth.appearance.color.border') }}; background-color:{{ config('devdojo.auth.appearance.color.input.background') }}; color:{{ config('devdojo.auth.appearance.color.input.text') }}; focus:border-color:{{ config('devdojo.auth.appearance.color.primary') }};" />
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Optional Section: Additional Information -->
            <div>
                <div class="mb-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide opacity-70" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                        Additional Information (Optional)
                    </h2>
                    <p class="text-xs opacity-60 mt-1" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                        Help us understand you better
                    </p>
                </div>
            </div>

            <!-- What do you do -->
            <div>
                <label class="block text-sm font-medium mb-2" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                    What do you do?
                </label>
                <select
                    wire:model="occupation"
                    class="w-full rounded-md border focus:ring-2 focus:ring-offset-2"
                    style="border-color:{{ config('devdojo.auth.appearance.color.border') }}; background-color:{{ config('devdojo.auth.appearance.color.input.background') }}; color:{{ config('devdojo.auth.appearance.color.input.text') }}; focus:border-color:{{ config('devdojo.auth.appearance.color.primary') }};">
                    <option value="">Select an option</option>
                    <option value="Freelancer">Freelancer</option>
                    <option value="Agency Owner">Agency Owner</option>
                    <option value="Entrepreneur">Entrepreneur</option>
                    <option value="Small Business Owner">Small Business Owner</option>
                    <option value="Marketing Professional">Marketing Professional</option>
                    <option value="Sales Professional">Sales Professional</option>
                    <option value="Consultant">Consultant</option>
                    <option value="Developer">Developer</option>
                    <option value="Student">Student</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <!-- Other occupation (shown only when "Other" is selected) -->
            <div x-data="{ showOther: @entangle('occupation') }" x-show="showOther === 'Other'" x-cloak>
                <label class="block text-sm font-medium mb-2" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                    Please specify
                </label>
                <textarea
                    wire:model="occupation_other"
                    rows="2"
                    placeholder="Tell us what you do..."
                    class="w-full rounded-md border focus:ring-2 focus:ring-offset-2"
                    style="border-color:{{ config('devdojo.auth.appearance.color.border') }}; background-color:{{ config('devdojo.auth.appearance.color.input.background') }}; color:{{ config('devdojo.auth.appearance.color.input.text') }}; focus:border-color:{{ config('devdojo.auth.appearance.color.primary') }};"></textarea>
            </div>

            <!-- Country -->
            <div>
                <label class="block text-sm font-medium mb-2" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                    Country
                </label>
                <select
                    wire:model="country"
                    class="w-full rounded-md border focus:ring-2 focus:ring-offset-2"
                    style="border-color:{{ config('devdojo.auth.appearance.color.border') }}; background-color:{{ config('devdojo.auth.appearance.color.input.background') }}; color:{{ config('devdojo.auth.appearance.color.input.text') }}; focus:border-color:{{ config('devdojo.auth.appearance.color.primary') }};">
                    <option value="">Select your country</option>
                    <option value="US">United States</option>
                    <option value="GB">United Kingdom</option>
                    <option value="CA">Canada</option>
                    <option value="AU">Australia</option>
                    <option value="DE">Germany</option>
                    <option value="FR">France</option>
                    <option value="ES">Spain</option>
                    <option value="IT">Italy</option>
                    <option value="NL">Netherlands</option>
                    <option value="SE">Sweden</option>
                    <option value="NO">Norway</option>
                    <option value="DK">Denmark</option>
                    <option value="FI">Finland</option>
                    <option value="PL">Poland</option>
                    <option value="RO">Romania</option>
                    <option value="BR">Brazil</option>
                    <option value="MX">Mexico</option>
                    <option value="AR">Argentina</option>
                    <option value="IN">India</option>
                    <option value="CN">China</option>
                    <option value="JP">Japan</option>
                    <option value="KR">South Korea</option>
                    <option value="SG">Singapore</option>
                    <option value="MY">Malaysia</option>
                    <option value="TH">Thailand</option>
                    <option value="ID">Indonesia</option>
                    <option value="PH">Philippines</option>
                    <option value="VN">Vietnam</option>
                    <option value="ZA">South Africa</option>
                    <option value="EG">Egypt</option>
                    <option value="NG">Nigeria</option>
                    <option value="KE">Kenya</option>
                    <option value="NZ">New Zealand</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <!-- Where did you hear about us -->
            <div>
                <label class="block text-sm font-medium mb-2" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                    Where did you hear about us?
                </label>
                <select
                    wire:model="referral_source"
                    class="w-full rounded-md border focus:ring-2 focus:ring-offset-2"
                    style="border-color:{{ config('devdojo.auth.appearance.color.border') }}; background-color:{{ config('devdojo.auth.appearance.color.input.background') }}; color:{{ config('devdojo.auth.appearance.color.input.text') }}; focus:border-color:{{ config('devdojo.auth.appearance.color.primary') }};">
                    <option value="">Select an option</option>
                    <option value="Google Search">Google Search</option>
                    <option value="Social Media">Social Media (Facebook, Twitter, LinkedIn)</option>
                    <option value="Reddit">Reddit</option>
                    <option value="YouTube">YouTube</option>
                    <option value="Friend/Colleague">Friend or Colleague</option>
                    <option value="Blog/Article">Blog or Article</option>
                    <option value="Podcast">Podcast</option>
                    <option value="Newsletter">Newsletter</option>
                    <option value="Advertisement">Advertisement</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <!-- Email Notifications -->
            <div class="py-6">
                <label class="flex items-start gap-3 cursor-pointer group">
                    <input
                        type="checkbox"
                        wire:model="email_on_leads_found"
                        class="mt-1 mr-3 rounded border-gray-300 focus:ring-2 focus:ring-offset-2"
                        style="color:{{ config('devdojo.auth.appearance.color.primary') }}; focus:ring-color:{{ config('devdojo.auth.appearance.color.primary') }};"
                    />
                    <div>
                        <span class="block text-sm font-medium group-hover:opacity-80 transition-opacity" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                            Notify me when new leads are found
                        </span>
                        <span class="block text-xs opacity-60 mt-1" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
                            Get instant email notifications when we discover relevant leads for your campaigns
                        </span>
                    </div>
                </label>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col space-y-3 pt-4">
                <x-auth::elements.button rounded="md" submit="true">
                    Continue
                </x-auth::elements.button>

                <button
                    type="button"
                    wire:click="skipForNow"
                    class="w-full px-6 py-3 text-sm font-medium rounded-md transition-colors duration-150 opacity-70 hover:opacity-100"
                    style="background-color:{{ config('devdojo.auth.appearance.color.background') }}; color:{{ config('devdojo.auth.appearance.color.text') }}; border: 1px solid {{ config('devdojo.auth.appearance.color.border') }};">
                    Skip for now
                </button>
            </div>
        </form>

        <!-- Privacy Note -->
        <p class="mt-6 text-center text-xs opacity-60" style="color:{{ config('devdojo.auth.appearance.color.text') }}">
            Your name is required. All other fields are optional and help us personalize your experience.
        </p>
    </x-auth::elements.container>
    @endvolt
</x-auth::layouts.app>
