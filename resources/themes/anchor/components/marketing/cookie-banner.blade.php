@if(setting('site.cookie_banner_enabled', '1') == '1')
<div x-data="cookieBanner()" x-show="showBanner" x-cloak
     class="fixed bottom-6 right-6 z-50 max-w-md"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0">

    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl border-2 border-zinc-200 dark:border-zinc-700 p-6">
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Cookie Preferences</h3>
            </div>
        </div>

        <!-- Description -->
        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
            We use cookies to enhance your experience, analyze site traffic, and personalize content. You can choose which cookies to accept.
        </p>

        <!-- Cookie Categories (Simple view by default) -->
        <div x-show="!showDetails" class="space-y-3 mb-5">
            <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <div class="flex-1">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Essential Cookies</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Required for the site to work</p>
                </div>
                <span class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Always On</span>
            </div>

            <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <div class="flex-1">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Analytics Cookies</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Help us improve the site</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="preferences.analytics" class="sr-only peer">
                    <div class="w-11 h-6 bg-zinc-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-zinc-900 dark:peer-focus:ring-white rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-zinc-900 dark:peer-checked:bg-white"></div>
                </label>
            </div>
        </div>

        <!-- Detailed View (Expandable) -->
        <div x-show="showDetails" x-cloak class="space-y-3 mb-5">
            <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Essential Cookies</p>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Always On</span>
                </div>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                    These cookies are necessary for the website to function and cannot be switched off. They are usually only set in response to actions like logging in or filling in forms.
                </p>
            </div>

            <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Analytics Cookies</p>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="preferences.analytics" class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-zinc-900 dark:peer-focus:ring-white rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-zinc-900 dark:peer-checked:bg-white"></div>
                    </label>
                </div>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                    These cookies allow us to count visits and traffic sources so we can measure and improve the performance of our site (Google Analytics).
                </p>
            </div>
        </div>

        <!-- Toggle Details Link -->
        <button @click="showDetails = !showDetails" class="text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white font-medium mb-4 underline">
            <span x-show="!showDetails">Show Details</span>
            <span x-show="showDetails" x-cloak>Hide Details</span>
        </button>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3">
            <button @click="acceptAll()"
                    class="flex-1 px-4 py-2.5 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-sm font-medium rounded-lg hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
                Accept All
            </button>
            <button @click="acceptSelected()"
                    class="flex-1 px-4 py-2.5 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white border-2 border-zinc-300 dark:border-zinc-600 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-600 transition-colors">
                Save Preferences
            </button>
            <button @click="declineAll()"
                    class="flex-1 px-4 py-2.5 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white border-2 border-zinc-300 dark:border-zinc-600 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-600 transition-colors">
                Decline All
            </button>
        </div>

        <!-- Privacy Policy Link -->
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-4 text-center">
            Read our <a href="/privacy" class="underline hover:text-zinc-900 dark:hover:text-white">Privacy Policy</a> for more information.
        </p>
    </div>
</div>

<script>
function cookieBanner() {
    return {
        showBanner: false,
        showDetails: false,
        preferences: {
            essential: true, // Always true
            analytics: false,
            marketing: false
        },

        init() {
            // Check if consent has been given
            const consent = localStorage.getItem('cookie_consent');
            const consentTimestamp = localStorage.getItem('cookie_consent_timestamp');

            // Show banner if no consent or consent is older than 1 year
            if (!consent || !consentTimestamp) {
                this.showBanner = true;
            } else {
                const oneYearAgo = Date.now() - (365 * 24 * 60 * 60 * 1000);
                if (parseInt(consentTimestamp) < oneYearAgo) {
                    this.showBanner = true;
                } else {
                    // Load saved preferences
                    const savedPrefs = JSON.parse(consent);
                    this.preferences = { ...this.preferences, ...savedPrefs };

                    // Initialize cookies based on saved preferences
                    this.initializeCookies();
                }
            }

            // Listen for preference panel opening
            window.addEventListener('open-cookie-preferences', () => {
                this.showBanner = true;
                this.showDetails = true;
            });
        },

        acceptAll() {
            this.preferences.analytics = true;
            this.preferences.marketing = true;
            this.saveConsent();
        },

        acceptSelected() {
            this.saveConsent();
        },

        declineAll() {
            this.preferences.analytics = false;
            this.preferences.marketing = false;
            this.saveConsent();
        },

        saveConsent() {
            // Save preferences to localStorage
            localStorage.setItem('cookie_consent', JSON.stringify(this.preferences));
            localStorage.setItem('cookie_consent_timestamp', Date.now().toString());

            // Initialize cookies based on preferences
            this.initializeCookies();

            // Hide banner
            this.showBanner = false;

            // Reload page to apply Google Analytics if consented
            if (this.preferences.analytics) {
                window.location.reload();
            }
        },

        initializeCookies() {
            // Dispatch event for other components to react to consent
            window.dispatchEvent(new CustomEvent('cookie-consent-updated', {
                detail: this.preferences
            }));

            // Set a flag for Google Analytics
            if (this.preferences.analytics) {
                window.cookieConsent = { analytics: true };
            }
        }
    }
}
</script>
@endif
