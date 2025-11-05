<x-filament-widgets::widget class="gap-5 fi-filament-info-widget">
    <section class="flex flex-col gap-5 mb-5 space-x-5 w-full xl:flex-row">
        <x-filament::section class="w-full">
            <div class="flex gap-x-3 items-center w-full">
                <div class="flex-1">
                    <a href="/" rel="noopener noreferrer" target="_blank"><x-logo class="w-auto h-6"></x-logo></a>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ wave_version() }}</p>
                </div>
                <div class="flex flex-col gap-y-1 items-end">
                    <x-filament::link color="gray" href="/admin/kpis" icon="heroicon-m-chart-bar">
                        View Detailed Analytics
                    </x-filament::link>
                </div>
            </div>
        </x-filament::section>
        <x-filament::section class="w-full">
            <div class="flex gap-x-3 items-center w-full">
                <div class="flex-1">
                    <h2 class="grid flex-1 text-base font-semibold leading-6 text-gray-950 dark:text-white">Welcome to EvenLeads Admin</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-medium text-blue-600">Active Theme: </span>{{ \Wave\Theme::where('active', 1)->first()->name }}</p>
                </div>
                <x-filament::button color="gray" icon="heroicon-m-arrow-top-right-on-square" icon-alias="panels::widgets.account.logout-button" labeled-from="sm" tag="a" type="submit" href="/" target="_blank">
                    Visit your Site
                </x-filament::button>
            </div>
        </x-filament::section>
    </section>
    <section class="flex gap-5 mb-5">
        <section class="flex flex-col gap-5 items-center w-full xl:flex-row">
            <x-filament::section class="w-full">
                <div class="flex gap-x-5 items-center">
                    <div class="flex-">
                        <x-phosphor-users-duotone class="h-10 text-blue-600 fill-current" />
                    </div>
                    <div class="flex flex-col w-full">
                        <div class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-200">{{ \Wave\User::count() }}</div>
                    </div>
                </div>
                <div class="mt-2 text-xs font-medium text-gray-500 truncate">User Accounts</div>
            </x-filament::section>
            <x-filament::section class="w-full">
                <div class="flex gap-x-5 items-center">
                    <div class="flex-">
                        <x-phosphor-credit-card-duotone class="h-10 text-blue-600 fill-current" />
                    </div>
                    <div class="flex flex-col w-full">
                        <div class="mt-1 text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-200">{{ \Wave\Subscription::where('status', 'active')->count() }}</div>
                    </div>
                </div>
                <div class="mt-2 text-xs font-medium text-gray-500 truncate">Active Subscriptions</div>
            </x-filament::section>
        </section>
        <section class="flex flex-col gap-5 items-center w-full xl:flex-row">
            <x-filament::section class="w-full">
                <div class="flex gap-x-5 items-center">
                    <div class="hidden lg:inline">
                        <x-phosphor-pencil-line-duotone class="h-10 text-blue-600 fill-current" />
                    </div>
                    <div class="flex flex-col w-full">
                        <div class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-200">{{ \Wave\Post::count() }}</div>
                    </div>
                </div>
                <div class="mt-2 text-xs font-medium text-gray-500 truncate">Total Post Articles</div>
            </x-filament::section>
            <x-filament::section class="w-full">
                <div class="flex gap-x-5 items-center">
                    <div class="flex-">
                        <x-phosphor-file-text-duotone class="h-10 text-blue-600 fill-current" />
                    </div>
                    <div class="flex flex-col w-full">
                        <div class="mt-1 text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-200">{{ \Wave\Page::count() }}</div>
                    </div>
                </div>
                <div class="mt-2 text-xs font-medium text-gray-500 truncate">Total Pages</div>
            </x-filament::section>
        </section>
    </section>
    @php
        $totalCampaigns = \Wave\Plugins\EvenLeads\Models\Campaign::count();
        $totalLeads = \Wave\Plugins\EvenLeads\Models\Lead::count();
        $aiRepliesThisMonth = \Wave\Plugins\EvenLeads\Models\AIGeneration::whereMonth('created_at', now()->month)->count();
    @endphp
    <section class="flex gap-5 mb-5">
        <section class="flex flex-col gap-5 items-center w-full xl:flex-row">
            <x-filament::section class="w-full">
                <div class="flex gap-x-5 items-center">
                    <div class="flex-">
                        <x-phosphor-megaphone-duotone class="h-10 text-emerald-600 fill-current" />
                    </div>
                    <div class="flex flex-col w-full">
                        <div class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-200">{{ number_format($totalCampaigns) }}</div>
                    </div>
                </div>
                <div class="mt-2 text-xs font-medium text-gray-500 truncate">Total Campaigns</div>
            </x-filament::section>
            <x-filament::section class="w-full">
                <div class="flex gap-x-5 items-center">
                    <div class="flex-">
                        <x-phosphor-users-three-duotone class="h-10 text-purple-600 fill-current" />
                    </div>
                    <div class="flex flex-col w-full">
                        <div class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-200">{{ number_format($totalLeads) }}</div>
                    </div>
                </div>
                <div class="mt-2 text-xs font-medium text-gray-500 truncate">Total Leads Generated</div>
            </x-filament::section>
            <x-filament::section class="w-full">
                <div class="flex gap-x-5 items-center">
                    <div class="flex-">
                        <x-phosphor-sparkle-duotone class="h-10 text-orange-600 fill-current" />
                    </div>
                    <div class="flex flex-col w-full">
                        <div class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-200">{{ number_format($aiRepliesThisMonth) }}</div>
                    </div>
                </div>
                <div class="mt-2 text-xs font-medium text-gray-500 truncate">AI Replies This Month</div>
            </x-filament::section>
        </section>
    </section>
    <x-filament::section>
        <div class="flex flex-col gap-4 w-full">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/admin/kpis" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-700">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-1">Detailed Analytics</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">View comprehensive KPIs and metrics</p>
                </a>
                <a href="/admin/users" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-700">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-1">Manage Users</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">View and edit user accounts</p>
                </a>
                <a href="/admin/plans" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-700">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-1">Subscription Plans</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Configure pricing and limits</p>
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
