<?php
    use function Laravel\Folio\{middleware, name};
	middleware(['auth', 'verified']);
    name('dashboard');
?>

<x-layouts.app>
	<x-app.container x-data class="lg:space-y-6" x-cloak>

		{{-- BYOAPI Requirement Warning Banner --}}
		@php
			$byoapiRequired = setting('site.bring_your_api_key_required', false);
			$byoapiDismissedToday = session('byoapi_prompt_dismissed_' . date('Y-m-d'), false);

			if ($byoapiRequired && !$byoapiDismissedToday) {
				$user = auth()->user();
				$connectedPlatforms = \Wave\Plugins\EvenLeads\Models\PlatformConnection::where('user_id', $user->id)
					->active()
					->pluck('platform')
					->unique()
					->toArray();

				$missingApiKeys = [];
				if (in_array('reddit', $connectedPlatforms) && !$user->reddit_use_custom_api) {
					$missingApiKeys[] = 'Reddit';
				}
				if (in_array('x', $connectedPlatforms) && !$user->x_use_custom_api) {
					$missingApiKeys[] = 'X (Twitter)';
				}
			} else {
				$missingApiKeys = [];
			}
		@endphp
		@if(!empty($missingApiKeys))
			<div x-data="{
				show: true,
				dismissToday() {
					localStorage.setItem('byoapi_dismissed_{{ date('Y-m-d') }}', '1');
					this.show = false;
				}
			}"
			x-init="
				// Check if already dismissed today
				if (localStorage.getItem('byoapi_dismissed_{{ date('Y-m-d') }}')) {
					show = false;
				}
			"
			x-show="show" class="p-4 mb-6 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 rounded-lg">
				<div class="flex items-start justify-between">
					<div class="flex items-start flex-1">
						<div class="flex-shrink-0">
							<svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
							</svg>
						</div>
						<div class="ml-3 flex-1">
							<h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
								API Keys Required
							</h3>
							<div class="mt-2 text-sm text-amber-700 dark:text-amber-300">
								<p class="mb-2">
									You have connected {{ implode(', ', $missingApiKeys) }} accounts but haven't configured your own API credentials yet.
								</p>
								<p class="mb-3">
									To continue using these platforms for lead generation, you'll need to add your own API keys.
								</p>
								<a href="{{ route('settings.profile') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors">
									Add API Keys in Settings
									<svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
									</svg>
								</a>
							</div>
						</div>
					</div>
					<button type="button" @click="dismissToday()" class="ml-4 text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-200 flex-shrink-0">
						<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
						</svg>
					</button>
				</div>
			</div>
		@endif

		{{-- Data Deletion Request Warning Banner --}}
		@php
			$pendingDeletion = auth()->user()->pendingDeletionRequest();
		@endphp
		@if($pendingDeletion)
			<div class="p-4 mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg">
				<div class="flex items-start">
					<div class="flex-shrink-0">
						<svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
						</svg>
					</div>
					<div class="ml-3 flex-1">
						<h3 class="text-sm font-semibold text-red-800 dark:text-red-200">
							Account Deletion Request Pending
						</h3>
						<div class="mt-2 text-sm text-red-700 dark:text-red-300">
							<p class="mb-2">
								You have submitted a data deletion request. Your account and data will be reviewed and deleted within 1-2 business days, followed by a 30-day deletion window.
							</p>
							<p class="mb-2">
								<strong>Confirmation Code:</strong> <code class="px-2 py-1 bg-red-100 dark:bg-red-800 rounded text-red-900 dark:text-red-100 font-mono">{{ $pendingDeletion->confirmation_code }}</code>
							</p>
							<p class="mb-2">
								<strong>Requested on:</strong> {{ $pendingDeletion->created_at->format('F j, Y \a\t g:i A') }}
							</p>
							<div class="mt-3 p-3 bg-red-100 dark:bg-red-800/50 rounded">
								<p class="font-semibold">⚠️ Want to cancel this request?</p>
								<p class="mt-1">
									Please email <a href="mailto:contact@evenleads.com" class="underline font-semibold hover:text-red-900 dark:hover:text-red-100">contact@evenleads.com</a> with your confirmation code to cancel this deletion request.
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		@endif

		<x-app.alert id="dashboard_alert" class="hidden lg:flex">This is the user dashboard where users can manage settings and access features. <a href="https://devdojo.com/wave/docs" target="_blank" class="mx-1 underline">View the docs</a> to learn more.</x-app.alert>

        <x-app.heading
                title="Dashboard"
                description="Welcome to an example application dashboard. Find more resources below."
                :border="false"
            />

        <div class="flex flex-col w-full mt-6 space-y-5 md:flex-row lg:mt-0 md:space-y-0 md:space-x-5">
            <x-app.dashboard-card
				href="https://devdojo.com/wave/docs"
				target="_blank"
				title="Documentation"
				description="Learn how to customize your app and make it shine!"
				link_text="View The Docs"
				image="/wave/img/docs.png"
			/>
			<x-app.dashboard-card
				href="https://devdojo.com/questions"
				target="_blank"
				title="Ask The Community"
				description="Share your progress and get help from other builders."
				link_text="Ask a Question"
				image="/wave/img/community.png"
			/>
        </div>

		<div class="flex flex-col w-full mt-5 space-y-5 md:flex-row md:space-y-0 md:mb-0 md:space-x-5">
			<x-app.dashboard-card
				href="https://github.com/thedevdojo/wave"
				target="_blank"
				title="Github Repo"
				description="View the source code and submit a Pull Request"
				link_text="View on Github"
				image="/wave/img/laptop.png"
			/>
			<x-app.dashboard-card
				href="https://devdojo.com"
				target="_blank"
				title="Resources"
				description="View resources that will help you build your SaaS"
				link_text="View Resources"
				image="/wave/img/globe.png"
			/>
		</div>

		<div class="mt-5 space-y-5">
			@subscriber
				<p>You are a subscribed user with the <strong>{{ auth()->user()->roles()->first()->name }}</strong> role. Learn <a href="https://devdojo.com/wave/docs/features/roles-permissions" target="_blank" class="underline">more about roles</a> here.</p>
				<x-app.message-for-subscriber />
			@else
				<p>This current logged in user has a <strong>{{ auth()->user()->roles()->first()->name }}</strong> role. To upgrade, <a href="{{ route('settings.subscription') }}" class="underline">subscribe to a plan</a>. Learn <a href="https://devdojo.com/wave/docs/features/roles-permissions" target="_blank" class="underline">more about roles</a> here.</p>
			@endsubscriber

			@admin
				<x-app.message-for-admin />
			@endadmin
		</div>
    </x-app.container>
</x-layouts.app>
