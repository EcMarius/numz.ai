<?php

    use function Laravel\Folio\{middleware, name};
    use Filament\Forms\Concerns\InteractsWithForms;
    use Filament\Forms\Contracts\HasForms;
    use Filament\Forms\Form;
    use Filament\Schemas\Schema;
    use Filament\Notifications\Notification;
	use Livewire\Volt\Component;
	use Wave\Traits\HasDynamicFields;
    use Wave\ApiKey;

	middleware('auth');
    name('settings.profile');

	new class extends Component implements HasForms
	{
        use InteractsWithForms, HasDynamicFields;

        public ?array $data = [];
		public ?string $avatar = null;
		public $connectedPlatforms = [];

		// Reddit API Configuration (BYOAPI) properties
		public $reddit_use_custom_api = false;
		public $reddit_client_id = '';
		public $reddit_client_secret = '';

		// X API Configuration (BYOAPI) properties
		public $x_use_custom_api = false;
		public $x_client_id = '';
		public $x_client_secret = '';

		public function mount(): void
        {
            $this->form->fill();
			$this->loadConnectedPlatforms();
			$this->loadRedditApiSettings();
			$this->loadXApiSettings();
        }

		public function loadRedditApiSettings()
		{
			$user = auth()->user();
			$this->reddit_use_custom_api = $user->reddit_use_custom_api ?? false;
			$this->reddit_client_id = $user->reddit_client_id ?? '';
			$this->reddit_client_secret = $user->reddit_client_secret ?? '';
		}

		public function loadXApiSettings()
		{
			$user = auth()->user();
			$this->x_use_custom_api = $user->x_use_custom_api ?? false;
			$this->x_client_id = $user->x_client_id ?? '';
			$this->x_client_secret = $user->x_client_secret ?? '';
		}

		public function loadConnectedPlatforms()
		{
			// Load lead generation platform connections (Reddit, X, Facebook, LinkedIn)
			$connections = \Wave\Plugins\EvenLeads\Models\PlatformConnection::where('user_id', auth()->id())
				->active()
				->orderBy('platform')
				->orderBy('created_at', 'desc')
				->get();

			// Group connections by platform
			$this->connectedPlatforms = $connections->groupBy('platform')->map(function($platformConnections, $platformName) {
				$accounts = $platformConnections->map(function($connection) {
					// Check if token is expired
					$isExpired = $connection->isExpired();

					// Get account info from metadata
					$metadata = $connection->metadata ?? [];
					$accountName = $metadata['username'] ?? $metadata['name'] ?? $connection->account_name ?? 'Account';

					return [
						'id' => $connection->id,
						'name' => $accountName,
						'is_expired' => $isExpired,
						'expires_at' => $connection->expires_at,
						'created_at' => $connection->created_at,
					];
				})->toArray();

				return [
					'platform' => $platformName,
					'display_name' => ucfirst($platformName),
					'accounts' => $accounts,
					'count' => count($accounts),
				];
			})->toArray();
		}

		public function disconnectPlatform($accountId)
		{
			$connection = \Wave\Plugins\EvenLeads\Models\PlatformConnection::where('id', $accountId)
				->where('user_id', auth()->id())
				->first();

			if (!$connection) {
				Notification::make()
					->title('Connection not found')
					->danger()
					->send();
				return;
			}

			$connection->delete();

			Notification::make()
				->title('Platform disconnected successfully')
				->success()
				->send();

			$this->loadConnectedPlatforms();
		}

       public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required()
						->rules('required|string')
						->default(auth()->user()->name),
					\Filament\Forms\Components\TextInput::make('email')
                        ->label('Email Address')
                        ->required()
						->rules('sometimes|required|email|unique:users,email,' . auth()->user()->id)
						->default(auth()->user()->email),
					...($this->dynamicFields( config('profile.fields') )),
					\Filament\Forms\Components\Toggle::make('email_on_leads_found')
						->label('Notify me when new leads are found')
						->helperText('Receive instant email notifications when we discover relevant leads for your campaigns')
						->default(auth()->user()->email_on_leads_found ?? true)
						->inline(false)
						->id('email_prefs')
                ])
                ->statePath('data');
        }

		public function save()
		{
			$this->validate([
				'avatar' => 'sometimes|nullable|imageable',
			]);

			$state = $this->form->getState();
            $this->validate();

			if($this->avatar != null){
				$this->saveNewUserAvatar();
			}

			$this->saveFormFields($state);

			Notification::make()
                ->title('Successfully saved your profile settings')
                ->success()
                ->send();
		}

		private function saveNewUserAvatar(){
			$path = 'avatars/' . auth()->user()->username . '.png';
			$image = \Intervention\Image\ImageManagerStatic::make($this->avatar)->resize(800, 800);
			Storage::disk('public')->put($path, $image->encode());
			auth()->user()->avatar = $path;
			auth()->user()->save();
			// This will update/refresh the avatar in the sidebar
			$this->js('window.dispatchEvent(new CustomEvent("refresh-avatar"));');
		}

		private function saveFormFields($state){
			auth()->user()->name = $state['name'];
			auth()->user()->email = $state['email'];
			auth()->user()->email_on_leads_found = $state['email_on_leads_found'] ?? true;
			auth()->user()->save();
			$fieldsToSave = config('profile.fields');
			$this->saveDynamicFields($fieldsToSave);
		}

		public function saveRedditApiCredentials()
		{
			// Validate credentials
			$this->validate([
				'reddit_client_id' => 'required|string|min:15|max:30',
				'reddit_client_secret' => 'required|string|min:20|max:40',
			], [
				'reddit_client_id.required' => 'Reddit Client ID is required.',
				'reddit_client_id.min' => 'Reddit Client ID must be at least 15 characters.',
				'reddit_client_secret.required' => 'Reddit Client Secret is required.',
				'reddit_client_secret.min' => 'Reddit Client Secret must be at least 20 characters.',
			]);

			try {
				$user = auth()->user();
				$user->reddit_use_custom_api = true;
				$user->reddit_client_id = $this->reddit_client_id;
				$user->reddit_client_secret = $this->reddit_client_secret;
				$user->save();

				Notification::make()
					->title('Reddit API credentials saved successfully!')
					->body('Your custom Reddit API credentials are now active. All Reddit connections will use your API keys.')
					->success()
					->send();

				$this->loadRedditApiSettings();
			} catch (\Exception $e) {
				Notification::make()
					->title('Failed to save Reddit API credentials')
					->body('An error occurred: ' . $e->getMessage())
					->danger()
					->send();
			}
		}

		public function clearRedditApiCredentials()
		{
			try {
				$user = auth()->user();
				$user->reddit_use_custom_api = false;
				$user->reddit_client_id = null;
				$user->reddit_client_secret = null;
				$user->save();

				$this->reddit_use_custom_api = false;
				$this->reddit_client_id = '';
				$this->reddit_client_secret = '';

				Notification::make()
					->title('Reddit API credentials cleared')
					->body('Your account will now use the shared platform Reddit API keys.')
					->success()
					->send();
			} catch (\Exception $e) {
				Notification::make()
					->title('Failed to clear Reddit API credentials')
					->body('An error occurred: ' . $e->getMessage())
					->danger()
					->send();
			}
		}

		public function saveXApiCredentials()
		{
			// Validate credentials
			$this->validate([
				'x_client_id' => 'required|string|min:15|max:50',
				'x_client_secret' => 'required|string|min:20|max:60',
			], [
				'x_client_id.required' => 'X Client ID is required.',
				'x_client_id.min' => 'X Client ID must be at least 15 characters.',
				'x_client_secret.required' => 'X Client Secret is required.',
				'x_client_secret.min' => 'X Client Secret must be at least 20 characters.',
			]);

			try {
				$user = auth()->user();
				$user->x_use_custom_api = true;
				$user->x_client_id = $this->x_client_id;
				$user->x_client_secret = $this->x_client_secret;
				$user->save();

				Notification::make()
					->title('X API credentials saved successfully!')
					->body('Your custom X (Twitter) API credentials are now active. All X connections will use your API keys.')
					->success()
					->send();

				$this->loadXApiSettings();
			} catch (\Exception $e) {
				Notification::make()
					->title('Failed to save X API credentials')
					->body('An error occurred: ' . $e->getMessage())
					->danger()
					->send();
			}
		}

		public function clearXApiCredentials()
		{
			try {
				$user = auth()->user();
				$user->x_use_custom_api = false;
				$user->x_client_id = null;
				$user->x_client_secret = null;
				$user->save();

				$this->x_use_custom_api = false;
				$this->x_client_id = '';
				$this->x_client_secret = '';

				Notification::make()
					->title('X API credentials cleared')
					->body('Your account will now use the shared platform X API keys.')
					->success()
					->send();
			} catch (\Exception $e) {
				Notification::make()
					->title('Failed to clear X API credentials')
					->body('An error occurred: ' . $e->getMessage())
					->danger()
					->send();
			}
		}

	}
?>

<x-layouts.app>

    <x-app.settings-layout
        title="Settings"
        description="Manage your account avatar, name, email, and more.">

		@volt('settings.profile')
		<div x-data="{
				uploadCropEl: null,
				uploadLoading: null,
				fileTypes: null,
				avatar: @entangle('avatar'),
				readFile() {
					input = document.getElementById('upload');
					if (input.files && input.files[0]) {
						let reader = new FileReader();

						let fileType = input.files[0].name.split('.').pop().toLowerCase();
						if (this.fileTypes.indexOf(fileType) < 0) {
							alert('Invalid file type. Please select a JPG or PNG file.');
							return false;
						}
						reader.onload = function (e) {
							uploadCrop.bind({
								url: e.target.result,
								orientation: 4
							}).then(function(){
								//uploadCrop.setZoom(0);
							});
						}
						reader.readAsDataURL(input.files[0]);
					}
					else {
						alert('Sorry - you\'re browser doesn\'t support the FileReader API');
					}
				},
				applyImageCrop(){
					let fileType = input.files[0].name.split('.').pop().toLowerCase();
					if (this.fileTypes.indexOf(fileType) < 0) {
						alert('Invalid file type. Please select a JPG or PNG file.');
						return false;
					}
					let that = this;
					uploadCrop.result({type:'base64',size:'original',format:'png',quality:1}).then(function(base64) {
						that.avatar = base64;
						document.getElementById('preview').src = that.avatar;
					});

				}
			}"
		x-init="
			uploadCropEl = document.getElementById('upload-crop');
			uploadLoading = document.getElementById('uploadLoading');
			fileTypes = ['jpg', 'jpeg', 'png'];

			if(document.getElementById('upload')){
				document.getElementById('upload').addEventListener('change', function () {
					window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'profile-avatar-crop' }}));
					uploadCropEl.classList.add('hidden');
					uploadLoading.classList.remove('hidden');
					setTimeout(function(){
						uploadLoading.classList.add('hidden');
						uploadCropEl.classList.remove('hidden');

						if(typeof(uploadCrop) != 'undefined'){
							uploadCrop.destroy();
						}
						uploadCrop = new Croppie(uploadCropEl, {
							viewport: { width: 190, height: 190, type: 'square' },
							boundary: { width: 190, height: 190 },
							enableExif: true,
						});

						readFile();
					}, 800);
				});
			}
		"
		class="relative w-full">
			<form wire:submit="save" class="w-full">
				<div class="relative flex flex-col mt-5 lg:px-10">

					<!-- Connected Lead Generation Platforms Section -->
					@if(count($connectedPlatforms) > 0)
						<div class="mb-8 pb-8 border-b border-zinc-200 dark:border-zinc-700">
							<h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Connected Lead Generation Platforms</h3>
							<div class="space-y-4">
								@foreach($connectedPlatforms as $platformGroup)
									<div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
										<!-- Platform Header -->
										<div class="flex items-center justify-between mb-3">
											<div class="flex items-center gap-3">
												<!-- Platform Logo -->
												<div @class([
													'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center',
													'bg-orange-100 dark:bg-orange-900/30' => $platformGroup['platform'] === 'reddit',
													'bg-blue-100 dark:bg-blue-900/30' => $platformGroup['platform'] === 'facebook',
													'bg-zinc-100 dark:bg-zinc-700' => $platformGroup['platform'] === 'x',
													'bg-indigo-100 dark:bg-indigo-900/30' => $platformGroup['platform'] === 'linkedin',
												])>
													<svg class="w-5 h-5 @if($platformGroup['platform'] === 'reddit') text-orange-600 dark:text-orange-400 @elseif($platformGroup['platform'] === 'facebook') text-blue-600 dark:text-blue-400 @elseif($platformGroup['platform'] === 'x') text-zinc-900 dark:text-zinc-100 @elseif($platformGroup['platform'] === 'linkedin') text-indigo-600 dark:text-indigo-400 @else text-zinc-600 dark:text-zinc-400 @endif" fill="currentColor" viewBox="0 0 24 24">
														@if($platformGroup['platform'] === 'reddit')
															<path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
														@elseif($platformGroup['platform'] === 'facebook')
															<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
														@elseif($platformGroup['platform'] === 'x')
															<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
														@elseif($platformGroup['platform'] === 'linkedin')
															<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
														@endif
													</svg>
												</div>

												<!-- Platform Name and Count -->
												<div>
													<h4 class="text-sm font-semibold text-zinc-900 dark:text-white">
														{{ $platformGroup['display_name'] }}
														<span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">({{ $platformGroup['count'] }} {{ $platformGroup['count'] === 1 ? 'account' : 'accounts' }})</span>
													</h4>
												</div>
											</div>

											<!-- Connect Another Button -->
											<a href="/oauth/{{ $platformGroup['platform'] }}?return_to={{ urlencode(request()->fullUrl()) }}"
											   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors">
												<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
												</svg>
												Connect Another
											</a>
										</div>

										<!-- Accounts List -->
										<div class="space-y-2">
											@foreach($platformGroup['accounts'] as $account)
												<div @class([
													'flex items-center justify-between p-2.5 rounded-lg border transition-colors',
													'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-600' => !$account['is_expired'],
													'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' => $account['is_expired'],
												])>
													<div class="flex items-center gap-2 flex-1 min-w-0">
														<span class="text-sm text-zinc-900 dark:text-white truncate">{{ $account['name'] }}</span>
														@if($account['is_expired'])
															<span class="px-1.5 py-0.5 text-[10px] font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded">Expired</span>
														@endif
													</div>

													<!-- Disconnect Button -->
													<button
														wire:click="disconnectPlatform({{ $account['id'] }})"
														wire:confirm="Are you sure you want to disconnect this {{ $platformGroup['display_name'] }} account ({{ $account['name'] }})?"
														type="button"
														class="flex-shrink-0 p-1 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
														title="Disconnect"
													>
														<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
														</svg>
													</button>
												</div>
											@endforeach
										</div>

										<!-- Reddit API Configuration (BYOAPI) -->
										@if($platformGroup['platform'] === 'reddit')
											<div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700" x-data="{ showTutorial: false }">
												<h4 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">API Configuration (Optional)</h4>
												<p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Bring Your Own API Key for enhanced performance</p>

												<!-- Benefits List -->
												<div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 mb-4">
													<ul class="space-y-1.5 text-xs text-zinc-700 dark:text-zinc-300">
														<li class="flex items-center gap-2">
															<svg class="w-4 h-4 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
															</svg>
															<span><strong>Higher Rate Limits:</strong> Up to 60 requests/min guaranteed</span>
														</li>
														<li class="flex items-center gap-2">
															<svg class="w-4 h-4 flex-shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
															</svg>
															<span><strong>No Shared Quota:</strong> Your own isolated API quota</span>
														</li>
														<li class="flex items-center gap-2">
															<svg class="w-4 h-4 flex-shrink-0 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
															</svg>
															<span><strong>More Control:</strong> Monitor and manage your Reddit app</span>
														</li>
														<li class="flex items-center gap-2">
															<svg class="w-4 h-4 flex-shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
															</svg>
															<span><strong>Better Reliability:</strong> No shared throttling issues</span>
														</li>
													</ul>
												</div>

												<!-- Toggle Switch -->
												<div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 mb-3">
													<div>
														<label for="reddit_use_custom_api" class="text-sm font-medium text-zinc-900 dark:text-white cursor-pointer">
															Use Custom Reddit API Credentials
														</label>
														<p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Enable to use your own Reddit app credentials</p>
													</div>
													<label class="relative inline-flex items-center cursor-pointer">
														<input type="checkbox" id="reddit_use_custom_api" wire:model.live="reddit_use_custom_api" class="sr-only peer">
														<div class="w-11 h-6 bg-zinc-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-orange-600 dark:peer-checked:bg-orange-500"></div>
													</label>
												</div>

												<!-- API Credentials Form (shown when toggle is ON) -->
												@if($reddit_use_custom_api)
													<div class="space-y-3">
														<!-- Reddit Client ID -->
														<div>
															<label for="reddit_client_id" class="block text-sm font-medium text-zinc-900 dark:text-white mb-1.5">
																Reddit Client ID
															</label>
															<input
																type="text"
																id="reddit_client_id"
																wire:model="reddit_client_id"
																placeholder="Enter your Reddit app Client ID (20 characters)"
																class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:ring-2 focus:ring-orange-500 focus:border-transparent"
															>
															@error('reddit_client_id') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
														</div>

														<!-- Reddit Client Secret -->
														<div>
															<label for="reddit_client_secret" class="block text-sm font-medium text-zinc-900 dark:text-white mb-1.5">
																Reddit Client Secret
															</label>
															<input
																type="password"
																id="reddit_client_secret"
																wire:model="reddit_client_secret"
																placeholder="Enter your Reddit app Client Secret (27 characters)"
																class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:ring-2 focus:ring-orange-500 focus:border-transparent"
															>
															@error('reddit_client_secret') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
														</div>

														<!-- Redirect URI (Read-only) -->
														<div>
															<label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1.5">
																Redirect URI (copy this)
															</label>
															<div class="relative">
																<input
																	type="text"
																	value="{{ request()->getSchemeAndHttpHost() }}/oauth/reddit/callback"
																	readonly
																	class="w-full px-3 py-2 pr-20 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300"
																>
																<button
																	type="button"
																	onclick="navigator.clipboard.writeText('{{ request()->getSchemeAndHttpHost() }}/oauth/reddit/callback')"
																	class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1 text-xs font-medium text-orange-700 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 rounded hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors"
																>
																	Copy
																</button>
															</div>
														</div>

														<!-- Save/Clear Buttons -->
														<div class="flex gap-2 justify-end">
															<button
																type="button"
																wire:click="clearRedditApiCredentials"
																wire:confirm="Are you sure? This will revert to using shared platform Reddit API keys."
																wire:loading.attr="disabled"
																class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 rounded-lg transition-colors"
															>
																Clear
															</button>
														</div>
													</div>
												@endif
											</div>
										@endif

										<!-- X (Twitter) API Configuration (BYOAPI) -->
										@if($platformGroup['platform'] === 'x')
											<div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700" x-data="{ showTutorial: false }">
												<h4 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">API Configuration (Optional)</h4>
												<p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Bring Your Own API Key for enhanced performance</p>

												<!-- Benefits List -->
												<div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 mb-4">
													<ul class="space-y-1.5 text-xs text-zinc-700 dark:text-zinc-300">
														<li class="flex items-center gap-2">
															<svg class="w-4 h-4 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
															</svg>
															<span><strong>Higher Rate Limits:</strong> Dedicated quota for your app</span>
														</li>
														<li class="flex items-center gap-2">
															<svg class="w-4 h-4 flex-shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
															</svg>
															<span><strong>No Shared Quota:</strong> Your own isolated API quota</span>
														</li>
														<li class="flex items-center gap-2">
															<svg class="w-4 h-4 flex-shrink-0 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
															</svg>
															<span><strong>More Control:</strong> Monitor and manage your X app</span>
														</li>
														<li class="flex items-center gap-2">
															<svg class="w-4 h-4 flex-shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
															</svg>
															<span><strong>Better Reliability:</strong> No shared throttling issues</span>
														</li>
													</ul>
												</div>

												<!-- Toggle Switch -->
												<div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 mb-3">
													<div>
														<label for="x_use_custom_api" class="text-sm font-medium text-zinc-900 dark:text-white cursor-pointer">
															Use Custom X (Twitter) API Credentials
														</label>
														<p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Enable to use your own X app credentials</p>
													</div>
													<label class="relative inline-flex items-center cursor-pointer">
														<input type="checkbox" id="x_use_custom_api" wire:model.live="x_use_custom_api" class="sr-only peer">
														<div class="w-11 h-6 bg-zinc-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-zinc-300 dark:peer-focus:ring-zinc-600 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-zinc-900 dark:peer-checked:bg-white"></div>
													</label>
												</div>

												<!-- API Credentials Form (shown when toggle is ON) -->
												@if($x_use_custom_api)
													<div class="space-y-3">
														<!-- X Client ID -->
														<div>
															<label for="x_client_id" class="block text-sm font-medium text-zinc-900 dark:text-white mb-1.5">
																X Client ID
															</label>
															<input
																type="text"
																id="x_client_id"
																wire:model="x_client_id"
																placeholder="Enter your X app Client ID"
																class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:ring-2 focus:ring-zinc-500 focus:border-transparent"
															>
															@error('x_client_id') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
														</div>

														<!-- X Client Secret -->
														<div>
															<label for="x_client_secret" class="block text-sm font-medium text-zinc-900 dark:text-white mb-1.5">
																X Client Secret
															</label>
															<input
																type="password"
																id="x_client_secret"
																wire:model="x_client_secret"
																placeholder="Enter your X app Client Secret"
																class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:ring-2 focus:ring-zinc-500 focus:border-transparent"
															>
															@error('x_client_secret') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
														</div>

														<!-- Redirect URI (Read-only) -->
														<div>
															<label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1.5">
																Redirect URI (copy this)
															</label>
															<div class="relative">
																<input
																	type="text"
																	value="{{ request()->getSchemeAndHttpHost() }}/oauth/x/callback"
																	readonly
																	class="w-full px-3 py-2 pr-20 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300"
																>
																<button
																	type="button"
																	onclick="navigator.clipboard.writeText('{{ request()->getSchemeAndHttpHost() }}/oauth/x/callback')"
																	class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1 text-xs font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors"
																>
																	Copy
																</button>
															</div>
														</div>

														<!-- Save/Clear Buttons -->
														<div class="flex gap-2 justify-end">
															<button
																type="button"
																wire:click="clearXApiCredentials"
																wire:confirm="Are you sure? This will revert to using shared platform X API keys."
																wire:loading.attr="disabled"
																class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 rounded-lg transition-colors"
															>
																Clear
															</button>
														</div>
													</div>
												@endif
											</div>
										@endif
									</div>
								@endforeach
							</div>
						</div>
					@endif

					<!-- Browser Extension Connection -->
					<div class="mb-8 pb-8 border-b border-zinc-200 dark:border-zinc-700">
						<h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Browser Extension Connection</h3>
						<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-4">
							<div class="flex items-center gap-3">
								<!-- Browser Extension Icon -->
								<div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-blue-100 dark:bg-blue-900/30">
									<svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
									</svg>
								</div>

								<!-- Coming Soon Info -->
								<div class="flex-1">
									<h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200">
										Browser Extension
									</h4>
									<p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5">
										Coming soon! Our browser extension is currently in development.
									</p>
								</div>

								<!-- Status Badge -->
								<span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
									Coming Soon
								</span>
							</div>
						</div>
					</div>

					<!-- Connect Additional Platforms -->
					@php
						// Get active platforms from database - only show those with proper OAuth credentials configured
						$activePlatforms = \Wave\Plugins\EvenLeads\Models\Platform::where('is_active', true)
							->get()
							->filter(function($platform) {
								// Only show platforms that should be visible (have credentials configured)
								return $platform->shouldBeVisible();
							})
							->mapWithKeys(function($platform) {
								// Map platform colors and icons
								$iconColors = [
									'reddit' => ['icon_bg' => 'bg-orange-100 dark:bg-orange-900/30', 'icon_text' => 'text-orange-600 dark:text-orange-400'],
									'facebook' => ['icon_bg' => 'bg-blue-100 dark:bg-blue-900/30', 'icon_text' => 'text-blue-600 dark:text-blue-400'],
									'x' => ['icon_bg' => 'bg-zinc-100 dark:bg-zinc-700', 'icon_text' => 'text-zinc-900 dark:text-zinc-100'],
									'linkedin' => ['icon_bg' => 'bg-indigo-100 dark:bg-indigo-900/30', 'icon_text' => 'text-indigo-600 dark:text-indigo-400']
								];

								$colors = $iconColors[$platform->name] ?? ['icon_bg' => 'bg-gray-100 dark:bg-gray-700', 'icon_text' => 'text-gray-600 dark:text-gray-400'];

								return [
									$platform->name => [
										'name' => $platform->display_name,
										'icon_bg' => $colors['icon_bg'],
										'icon_text' => $colors['icon_text']
									]
								];
							})
							->toArray();

						$connectedPlatformKeys = collect($connectedPlatforms)->pluck('platform')->toArray();
					@endphp

					@if(count($activePlatforms) > count($connectedPlatformKeys))
					<div id="connect-platforms" class="mb-8 pb-8 border-b border-zinc-200 dark:border-zinc-700">
						<h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Connect Additional Platforms</h3>
						<p class="text-xs text-zinc-600 dark:text-zinc-400 mb-4">Connect your social media accounts to manage posts and engage with your audience</p>
						<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
							@foreach($activePlatforms as $platformKey => $platform)
								@if(!in_array($platformKey, $connectedPlatformKeys))
									<a href="/oauth/{{ $platformKey }}?return_to={{ urlencode(request()->fullUrl()) }}"
									   class="flex flex-col items-center gap-3 p-4 rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-600 hover:border-emerald-500 dark:hover:border-emerald-500 transition-colors group">
										<div class="{{ $platform['icon_bg'] }} w-12 h-12 rounded-full flex items-center justify-center">
											<svg class="w-6 h-6 {{ $platform['icon_text'] }}" fill="currentColor" viewBox="0 0 24 24">
												@if($platformKey === 'reddit')
													<path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
												@elseif($platformKey === 'facebook')
													<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
												@elseif($platformKey === 'x')
													<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
												@elseif($platformKey === 'linkedin')
													<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
												@endif
											</svg>
										</div>
										<div class="text-center">
											<p class="text-sm font-medium text-zinc-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Connect {{ $platform['name'] }}</p>
											<p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Not connected</p>
										</div>
									</a>
								@endif
							@endforeach

							@if(count($connectedPlatformKeys) >= count($activePlatforms))
								<div class="col-span-full text-center py-4">
									<p class="text-sm text-emerald-600 dark:text-emerald-400 flex items-center justify-center gap-2">
										<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
											<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
										</svg>
										All platforms connected!
									</p>
								</div>
							@endif
						</div>
					</div>
					@endif

					<div class="relative flex-shrink-0 w-32 h-32 cursor-pointer group">
						<img id="preview" src="{{ auth()->user()->avatar() . '?' . time() }}" class="w-32 h-32 rounded-full">

						<div class="absolute inset-0 w-full h-full">
							<input type="file" id="upload" class="absolute inset-0 z-20 w-full h-full opacity-0 cursor-pointer group">
							<button class="absolute bottom-0 z-10 flex items-center justify-center w-10 h-10 mb-2 -ml-5 bg-black bg-opacity-75 rounded-full opacity-75 left-1/2 group-hover:opacity-100">
								<svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
							</button>
						</div>
					</div>
					@error('avatar')
						<p class="mt-3 text-sm text-red-600">The avatar must be a valid image type.</p>
					@enderror
					<div class="w-full mt-8">
						{{ $this->form }}
					</div>
					<div class="w-full pt-6 text-right">
						<x-button type="submit">Save</x-button>
					</div>
				</div>

			</form>

			<div class="z-[99]">
				<x-filament::modal id="profile-avatar-crop">
					<div>
						<div class="mt-3 text-center sm:mt-5">
							<h3 class="text-lg font-medium leading-6 text-zinc-900" id="modal-headline">
								Position and resize your photo
							</h3>
							<div class="mt-2">
								<div id="upload-crop-container" class="relative flex items-center justify-center h-56 mt-5">
									<div id="uploadLoading" class="flex items-center justify-center w-full h-full">
										<svg class="w-5 h-5 mr-3 -ml-1 animate-spin text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
											<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
											<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
										</svg>
									</div>
									<div id="upload-crop"></div>
								</div>
							</div>
						</div>
					</div>
					<div class="mt-5 sm:mt-6">
						<span class="flex w-full rounded-md shadow-sm">
							<button @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'profile-avatar-crop' }}));" class="inline-flex justify-center w-full px-4 py-2 mr-2 text-base font-medium leading-6 transition duration-150 ease-in-out bg-white border border-transparent rounded-md shadow-sm text-zinc-700 border-zinc-300 hover:text-zinc-500 active:text-zinc-800 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue sm:text-sm sm:leading-5" type="button">Cancel</button>
							<button @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'profile-avatar-crop' }})); applyImageCrop()" class="inline-flex justify-center w-full px-4 py-2 ml-2 text-base font-medium leading-6 text-white transition duration-150 ease-in-out bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-500 focus:outline-none focus:border-blue-700 focus:shadow-outline-wave sm:text-sm sm:leading-5" id="apply-crop" type="button">Apply</button>
						</span>
					</div>
				</x-filament::modal>
			</div>
		</div>
		@endvolt
    </x-app.settings-layout>

	<x-slot:javascript>
		<style>
			#upload-crop-container .croppie-container .cr-resizer, #upload-crop-container .croppie-container .cr-viewport{
				box-shadow: 0 0 2000px 2000px rgba(255,255,255,1) !important;
				border: 0px !important;
			}
			.croppie-container .cr-boundary {
				border-radius: 50% !important;
				overflow: hidden;
			}
			.croppie-container .cr-slider-wrap{
				margin-bottom: 0px !important;
			}
			.croppie-container{
				height:auto !important;
			}
		</style>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/exif-js/2.3.0/exif.min.js"></script>
		<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.2/croppie.min.css">
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.2/croppie.min.js"></script>

		<script>
			// Handle unsubscribe from email notifications
			document.addEventListener('DOMContentLoaded', function() {
				const urlParams = new URLSearchParams(window.location.search);
				if (urlParams.get('unsubscribe') === '1' && urlParams.get('highlight') === 'email_prefs') {
					// Wait for form to render
					setTimeout(function() {
						// Find the email preferences section
						const emailPrefsToggle = document.querySelector('[data-field-wrapper="email_on_leads_found"]');
						if (emailPrefsToggle) {
							// Scroll to the element smoothly
							emailPrefsToggle.scrollIntoView({ behavior: 'smooth', block: 'center' });

							// Add highlight effect
							emailPrefsToggle.style.transition = 'all 0.3s ease';
							emailPrefsToggle.style.backgroundColor = '#FEF3C7'; // Light yellow highlight
							emailPrefsToggle.style.border = '2px solid #F59E0B'; // Orange border
							emailPrefsToggle.style.borderRadius = '0.5rem';
							emailPrefsToggle.style.padding = '0.5rem';

							// Remove highlight after 3 seconds
							setTimeout(function() {
								emailPrefsToggle.style.backgroundColor = '';
								emailPrefsToggle.style.border = '';
								emailPrefsToggle.style.padding = '';

								// Clean up URL
								window.history.replaceState({}, document.title, window.location.pathname);
							}, 3000);
						}
					}, 500);
				}
			});
		</script>
	</x-slot>

</x-layouts.app>
