<div x-data="{ sidebarOpen: false }"  @open-sidebar.window="sidebarOpen = true"
    x-init="
        $watch('sidebarOpen', function(value){
            if(value){ document.body.classList.add('overflow-hidden'); } else { document.body.classList.remove('overflow-hidden'); }
        });
    "
    class="relative z-50 w-screen md:w-auto" x-cloak>
    {{-- Backdrop for mobile --}}
    <div x-show="sidebarOpen" @click="sidebarOpen=false" class="fixed top-0 right-0 z-50 w-screen h-screen duration-300 ease-out bg-black/20 dark:bg-white/10"></div>
    
    {{-- Sidebar --}} 
    <div :class="{ '-translate-x-full': !sidebarOpen }"
        class="fixed top-0 left-0 flex items-stretch -translate-x-full overflow-hidden lg:translate-x-0 z-50 h-dvh md:h-screen transition-[width,transform] duration-150 ease-out bg-zinc-50 dark:bg-zinc-900 w-64 group @if(config('wave.dev_bar')){{ 'pb-10' }}@endif">  
        <div class="flex flex-col justify-between w-full overflow-auto md:h-full h-svh pt-4 pb-2.5">
            <div class="relative flex flex-col">
                <button x-on:click="sidebarOpen=false" class="flex items-center justify-center flex-shrink-0 w-10 h-10 ml-4 rounded-md lg:hidden text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 dark:hover:bg-zinc-700/70 hover:bg-gray-200/70">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>

                <div class="flex items-center px-5 space-x-2">
                    <a href="/" class="flex justify-center items-center py-4 pl-0.5 space-x-1 font-bold text-zinc-900 dark:text-white">
                        <x-logo class="w-auto h-11" />
                    </a>
                </div>
                <div class="hidden items-center px-4 pt-1 pb-3">
                    <div class="relative flex items-center w-full h-full rounded-lg">
                        <x-phosphor-magnifying-glass class="absolute left-0 w-5 h-5 ml-2 text-gray-400 -translate-y-px" />
                        <input type="text" class="w-full py-2 pl-8 text-sm border rounded-lg bg-zinc-200/70 focus:bg-white duration-50 dark:bg-zinc-950 ease border-zinc-200 dark:border-zinc-700/70 dark:ring-zinc-700/70 focus:ring dark:text-zinc-200 dark:focus:ring-zinc-700/70 dark:focus:border-zinc-700 focus:ring-zinc-200 focus:border-zinc-300 dark:placeholder-zinc-400" placeholder="Search">
                    </div>
                </div>

                <div class="flex flex-col justify-start items-center px-4 space-y-1.5 w-full h-full text-slate-600 dark:text-zinc-400">
                    <x-app.sidebar-link href="/dashboard" icon="phosphor-house" :active="Request::is('dashboard')">Dashboard</x-app.sidebar-link>

                    {{-- Hidden: Projects, Stories, Users --}}
                    <div class="hidden">
                        <x-app.sidebar-dropdown text="Projects" icon="phosphor-stack" id="projects_dropdown" :active="(Request::is('projects'))" :open="(Request::is('project_a') || Request::is('project_b') || Request::is('project_c')) ? '1' : '0'">
                            <x-app.sidebar-link onclick="event.preventDefault(); new FilamentNotification().title('Modify this button inside of sidebar.blade.php').send()" icon="phosphor-cube" :active="(Request::is('project_a'))">Project A</x-app.sidebar-link>
                            <x-app.sidebar-link onclick="event.preventDefault(); new FilamentNotification().title('Modify this button inside of sidebar.blade.php').send()" icon="phosphor-cube" :active="(Request::is('project_b'))">Project B</x-app.sidebar-link>
                            <x-app.sidebar-link onclick="event.preventDefault(); new FilamentNotification().title('Modify this button inside of sidebar.blade.php').send()" icon="phosphor-cube" :active="(Request::is('project_c'))">Project C</x-app.sidebar-link>
                        </x-app.sidebar-dropdown>
                        <x-app.sidebar-link onclick="event.preventDefault(); new FilamentNotification().title('Modify this button inside of sidebar.blade.php').send()" icon="phosphor-pencil-line" active="false">Stories</x-app.sidebar-link>
                        <x-app.sidebar-link  onclick="event.preventDefault(); new FilamentNotification().title('Modify this button inside of sidebar.blade.php').send()" icon="phosphor-users" active="false">Users</x-app.sidebar-link>
                    </div>

                    @php
                        // Load menu items from plugins
                        $pluginManager = app()->bound(\Wave\Plugins\PluginManager::class) ? app(\Wave\Plugins\PluginManager::class) : null;
                        $pluginMenuItems = [];

                        if ($pluginManager) {
                            foreach ($pluginManager->getPlugins() as $plugin) {
                                if (method_exists($plugin, 'getSidebarMenuItems')) {
                                    $items = $plugin->getSidebarMenuItems();
                                    $pluginMenuItems = array_merge($pluginMenuItems, $items);
                                }
                            }
                        }
                    @endphp

                    @foreach($pluginMenuItems as $menuItem)
                        @if(isset($menuItem['items']) && count($menuItem['items']) > 0)
                            {{-- Dropdown menu with sub-items --}}
                            <x-app.sidebar-dropdown
                                text="{{ $menuItem['label'] }}"
                                icon="{{ $menuItem['icon'] ?? 'phosphor-cube' }}"
                                id="{{ Str::slug($menuItem['label']) }}_dropdown"
                                :active="isset($menuItem['active']) && is_callable($menuItem['active']) ? ($menuItem['active'])() : false"
                                :open="isset($menuItem['open']) && is_callable($menuItem['open']) ? ($menuItem['open'])() : '0'">
                                @foreach($menuItem['items'] as $subItem)
                                    <x-app.sidebar-link
                                        href="{{ $subItem['url'] }}"
                                        icon="{{ $subItem['icon'] ?? 'phosphor-circle' }}"
                                        :active="isset($subItem['active']) && is_callable($subItem['active']) ? ($subItem['active'])() : Request::is(ltrim($subItem['url'], '/'))">
                                        {{ $subItem['label'] }}
                                        @if(isset($subItem['badge']))
                                            <span class="ml-auto">{{ is_callable($subItem['badge']) ? ($subItem['badge'])() : $subItem['badge'] }}</span>
                                        @endif
                                    </x-app.sidebar-link>
                                @endforeach
                            </x-app.sidebar-dropdown>
                        @else
                            {{-- Single link --}}
                            <x-app.sidebar-link
                                href="{{ $menuItem['url'] }}"
                                icon="{{ $menuItem['icon'] ?? 'phosphor-cube' }}"
                                :active="isset($menuItem['active']) && is_callable($menuItem['active']) ? ($menuItem['active'])() : Request::is(ltrim($menuItem['url'], '/'))">
                                <span class="flex items-center justify-between w-full">
                                    <span>{{ $menuItem['label'] }}</span>
                                    @if(isset($menuItem['badge']))
                                        @if($menuItem['label'] === 'Leads')
                                            @livewire('evenleads-new-leads-badge')
                                        @else
                                            <span class="ml-2 text-xs px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full">
                                                {{ is_callable($menuItem['badge']) ? ($menuItem['badge'])() : $menuItem['badge'] }}
                                            </span>
                                        @endif
                                    @endif
                                </span>
                            </x-app.sidebar-link>
                        @endif
                    @endforeach

                    {{-- Team Management Link (for seated plans) --}}
                    @php
                        $hasSeatedPlan = false;
                        try {
                            $userSubscription = \Wave\Subscription::where('billable_id', auth()->id())
                                ->where('billable_type', 'user')
                                ->where('status', 'active')
                                ->first();

                            if ($userSubscription && isset($userSubscription->plan_id)) {
                                $plan = \Wave\Plan::find($userSubscription->plan_id);
                                $hasSeatedPlan = $plan && isset($plan->is_seated_plan) && $plan->is_seated_plan;
                            }
                        } catch (\Exception $e) {
                            // Silently fail - hide team link if error
                            $hasSeatedPlan = false;
                        }
                    @endphp
                    @if($hasSeatedPlan)
                        <x-app.sidebar-link href="/team" icon="phosphor-users-three" :active="Request::is('team*')">
                            Team
                        </x-app.sidebar-link>
                    @endif

                    {{-- Settings link (moved here to appear after Team) --}}
                    <x-app.sidebar-link href="/settings" icon="phosphor-gear-duotone" :active="Request::is('settings/profile*') || Request::is('settings/security*') || Request::is('settings/api*')">
                        Settings
                    </x-app.sidebar-link>
                </div>
            </div>

            <div class="relative px-2.5 space-y-2.5 text-zinc-700 dark:text-zinc-400">
                @livewire('plan-quota-widget')
                <x-app.user-menu />
            </div>
        </div>
    </div>
</div>
