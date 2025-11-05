@filamentScripts
@livewireScripts
@if(config('wave.dev_bar'))
    @include('theme::partials.dev_bar')
@endif

{{-- @yield('javascript') --}}

@if(setting('site.google_analytics_tracking_id', ''))
    <script>
        // Check cookie consent before loading Google Analytics (GDPR compliance)
        // IMPORTANT: We MUST get explicit consent before loading GA (GDPR requirement)
        function initGoogleAnalytics() {
            const consent = localStorage.getItem('cookie_consent');

            // GDPR: Only load GA if user has EXPLICITLY consented
            if (consent) {
                const preferences = JSON.parse(consent);
                if (preferences.analytics === true) {
                    loadGoogleAnalytics();
                }
            }
        }

        function loadGoogleAnalytics() {
            // Prevent duplicate loading
            if (window.gaLoaded) {
                return;
            }
            window.gaLoaded = true;

            // Load Google Analytics script
            const script = document.createElement('script');
            script.async = true;
            script.src = 'https://www.googletagmanager.com/gtag/js?id={{ setting("site.google_analytics_tracking_id") }}';
            document.head.appendChild(script);

            // Initialize gtag
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            window.gtag = gtag;

            gtag('js', new Date());
            gtag('config', '{{ setting("site.google_analytics_tracking_id") }}', {
                'anonymize_ip': true, // GDPR requirement
                'cookie_flags': 'SameSite=None;Secure',
                'send_page_view': true
            });

            // SaaS-Specific Events Setup
            setupSaaSTracking();
        }

        function setupSaaSTracking() {
            if (typeof gtag !== 'function') return;

            // Track important page views
            const path = window.location.pathname;
            if (path === '/pricing' || path.includes('/pricing')) {
                gtag('event', 'page_view', { 'page_title': 'Pricing Page' });
            } else if (path === '/dashboard') {
                gtag('event', 'page_view', { 'page_title': 'Dashboard' });
            } else if (path === '/campaigns' || path.includes('/campaigns')) {
                gtag('event', 'page_view', { 'page_title': 'Campaigns' });
            } else if (path === '/leads') {
                gtag('event', 'page_view', { 'page_title': 'Leads' });
            } else if (path.includes('/settings')) {
                gtag('event', 'page_view', { 'page_title': 'Settings' });
            }

            // Track authenticated user events
            @auth
            const userId = '{{ auth()->id() }}';

            // Track subscription status
            @php
                $userSubscription = auth()->user()->subscriptions()->where('status', 'active')->first();
                $hasPlan = $userSubscription && $userSubscription->plan;
                $isOnTrial = $userSubscription && $userSubscription->trial_ends_at && \Carbon\Carbon::parse($userSubscription->trial_ends_at)->isFuture();
            @endphp

            @if($hasPlan)
            gtag('event', 'user_status', {
                'subscription_status': 'active',
                'plan_name': '{{ $userSubscription->plan->name ?? "Unknown" }}',
                'billing_cycle': '{{ $userSubscription->cycle ?? "month" }}',
                'is_trial': {{ $isOnTrial ? 'true' : 'false' }}
            });
            @else
            gtag('event', 'user_status', {
                'subscription_status': 'none'
            });
            @endif

            // Listen for Livewire events to track user actions
            document.addEventListener('livewire:initialized', () => {
                // Campaign Events
                Livewire.on('campaignCreated', (data) => {
                    gtag('event', 'campaign_created', {
                        'event_category': 'engagement',
                        'value': 1
                    });
                });

                Livewire.on('campaignDeleted', () => {
                    gtag('event', 'campaign_deleted', {
                        'event_category': 'engagement'
                    });
                });

                // Sync Events
                Livewire.on('syncStarted', () => {
                    gtag('event', 'campaign_sync', {
                        'event_category': 'engagement',
                        'sync_type': 'manual'
                    });
                });

                Livewire.on('syncCompleted', (data) => {
                    gtag('event', 'sync_completed', {
                        'event_category': 'engagement',
                        'leads_found': data?.leads_count || 0
                    });
                });

                // AI Events
                Livewire.on('aiReplyGenerated', () => {
                    gtag('event', 'ai_reply_generated', {
                        'event_category': 'ai_usage',
                        'value': 1
                    });
                });

                Livewire.on('aiChatUsed', () => {
                    gtag('event', 'ai_chat_used', {
                        'event_category': 'ai_usage'
                    });
                });

                // Lead Events
                Livewire.on('leadStatusChanged', (data) => {
                    gtag('event', 'lead_status_changed', {
                        'event_category': 'engagement',
                        'new_status': data?.status || 'unknown'
                    });
                });

                Livewire.on('leadArchived', () => {
                    gtag('event', 'lead_archived', {
                        'event_category': 'engagement'
                    });
                });

                Livewire.on('leadMarkedIrrelevant', () => {
                    gtag('event', 'lead_marked_irrelevant', {
                        'event_category': 'engagement'
                    });
                });

                // Platform Connection Events
                Livewire.on('platformConnected', (data) => {
                    gtag('event', 'platform_connected', {
                        'event_category': 'integration',
                        'platform': data?.platform || 'unknown'
                    });
                });

                Livewire.on('platformDisconnected', (data) => {
                    gtag('event', 'platform_disconnected', {
                        'event_category': 'integration',
                        'platform': data?.platform || 'unknown'
                    });
                });

                // Organization/Team Events
                Livewire.on('organizationCreated', () => {
                    gtag('event', 'organization_created', {
                        'event_category': 'team'
                    });
                });

                Livewire.on('teamMemberInvited', () => {
                    gtag('event', 'team_member_invited', {
                        'event_category': 'team',
                        'value': 1
                    });
                });

                // Subscription Events
                Livewire.on('subscriptionCancelled', (data) => {
                    gtag('event', 'subscription_cancelled', {
                        'event_category': 'subscription',
                        'reason': data?.reason || 'unknown'
                    });
                });

                Livewire.on('planChanged', (data) => {
                    gtag('event', 'plan_changed', {
                        'event_category': 'subscription',
                        'new_plan': data?.plan || 'unknown',
                        'value': data?.value || 0
                    });
                });

                // Account Warmup
                Livewire.on('accountWarmupStarted', (data) => {
                    gtag('event', 'account_warmup_started', {
                        'event_category': 'engagement',
                        'platform': data?.platform || 'unknown'
                    });
                });
            });
            @endauth
        }

        // Initialize GA on page load if consent exists
        initGoogleAnalytics();

        // Re-initialize if consent is updated
        window.addEventListener('cookie-consent-updated', (e) => {
            if (e.detail.analytics) {
                loadGoogleAnalytics();
            }
        });
    </script>

    <!-- Checkout & Purchase Tracking (Stripe Success Page) -->
    @if(request()->is('subscription/welcome') && auth()->check())
    @php
        $activeSubscription = auth()->user()->subscriptions()->where('status', 'active')->first();
        $hasActivePlan = $activeSubscription && $activeSubscription->plan;
    @endphp
    @if($hasActivePlan)
    <script>
        // Track successful subscription purchase
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof gtag === 'function') {
                gtag('event', 'purchase', {
                    'transaction_id': '{{ $activeSubscription->vendor_subscription_id }}',
                    'value': {{ $activeSubscription->plan->monthly_price ?? 0 }},
                    'currency': '{{ $activeSubscription->plan->currency ?? "EUR" }}',
                    'items': [{
                        'item_name': '{{ $activeSubscription->plan->name }} Plan',
                        'item_category': 'subscription',
                        'price': {{ $activeSubscription->plan->monthly_price ?? 0 }},
                        'quantity': 1
                    }]
                });

                // Also track as conversion
                gtag('event', 'conversion', {
                    'send_to': '{{ setting("site.google_analytics_tracking_id") }}',
                    'value': {{ $activeSubscription->plan->monthly_price ?? 0 }},
                    'currency': '{{ $activeSubscription->plan->currency ?? "EUR" }}'
                });
            }
        });
    </script>
    @endif
    @endif
@endif