@php
    use Wave\Plugins\EvenLeads\Models\Platform;

    // Get active platforms dynamically
    $activePlatformsList = Cache::remember('evenleads.active_platforms_list', 3600, function() {
        try {
            return Platform::where('is_active', true)->pluck('display_name')->toArray();
        } catch (\Exception $e) {
            return ['Reddit']; // Fallback
        }
    });

    $platformText = count($activePlatformsList) > 1
        ? implode(', ', array_slice($activePlatformsList, 0, -1)) . ' and ' . end($activePlatformsList)
        : ($activePlatformsList[0] ?? 'multiple platforms');
@endphp

<section id="features">
    <x-marketing.elements.heading
        level="h2"
        title="Everything You Need to <br> Capture More Leads"
        description="Stop manually searching for potential customers. EvenLeads automates lead discovery across {{ $platformText }} and helps you engage at the perfect moment."
    />
    <div class="text-center">
        <div class="grid grid-cols-2 gap-x-6 gap-y-12 mt-12 text-center lg:mt-16 lg:grid-cols-4 lg:gap-x-8 lg:gap-y-16">
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-magnifying-glass class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">Multi-Platform Monitoring</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        Track conversations across {{ $platformText }} 24/7 with automated syncing to find your ideal customers.
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-robot class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">AI-Powered Replies</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        Generate contextual, helpful responses with AI that sound natural and authentic across all platforms.
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-target class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">Smart Lead Scoring</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        AI intelligently ranks leads by relevance so you focus on high-quality opportunities first.
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-chart-line class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">Campaign Analytics</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        Track campaign performance with detailed metrics and optimize your lead generation strategy.
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-chat-circle-text class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">Post Management</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        Manage and sync your own posts across platforms with AI-powered engagement tracking.
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-users-three class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">Multi-Account Support</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        Connect multiple accounts per platform and manage all your leads from one dashboard.
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-folders class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">Campaign from Website</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        Generate targeted campaigns instantly by analyzing your website content with AI.
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-code class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">Developer API</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        Full REST API with comprehensive documentation for custom integrations and automation.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>