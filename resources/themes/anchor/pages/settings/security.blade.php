<?php
    use Filament\Forms\Components\TextInput;
    use Livewire\Volt\Component;
    use function Laravel\Folio\{middleware, name};
    use Filament\Forms\Concerns\InteractsWithForms;
    use Filament\Forms\Contracts\HasForms;
    use Filament\Forms\Form;
    use Filament\Schemas\Schema;
    use Filament\Notifications\Notification;

    middleware('auth');
    name('settings.security');

	new class extends Component implements HasForms
	{
        use InteractsWithForms;

        public ?array $data = [];
        public string $deleteReason = '';
        public string $confirmText = '';
        public bool $confirmPlanLoss = false;
        public bool $hasActivePlan = false;
        public string $planDetails = '';
        public $pendingDeletion = null;

        public function mount(): void
        {
            $this->form->fill();

            // Check if user has a pending deletion request
            $user = auth()->user();
            $this->pendingDeletion = $user->pendingDeletionRequest();

            // Check if user has an active plan
            if ($user->subscriber()) {
                $this->hasActivePlan = true;
                $subscription = $user->subscription;

                if ($subscription) {
                    // Check if subscription has an end date (cancelled but still active)
                    if ($subscription->ends_at && $subscription->ends_at->isFuture()) {
                        $this->planDetails = "You have an active {$subscription->plan->name} plan (cancelled but valid until " .
                                            $subscription->ends_at->format('M d, Y') . ")";
                    } else {
                        $this->planDetails = "You have an active {$subscription->plan->name} plan";
                    }
                }
            }
        }

        public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    TextInput::make('current_password')
                        ->label('Current Password')
                        ->required()
                        ->currentPassword()
                        ->password()
                        ->revealable(),
                    TextInput::make('password')
                        ->label('New Password')
                        ->required()
                        ->minLength(4)
                        ->password()
                        ->revealable(),
                    TextInput::make('password_confirmation')
                        ->label('Confirm New Password')
                        ->required()
                        ->password()
                        ->revealable()
                        ->same('password')
                    // ...
                ])
                ->statePath('data');
        }

        public function save(): void
        {
            $state = $this->form->getState();
            $this->validate();

            auth()->user()->forceFill([
                'password' => bcrypt($state['password'])
            ])->save();

            $this->form->fill();

            Notification::make()
                ->title('Successfully changed password')
                ->success()
                ->send();
        }

        public function submitDeleteRequest(): void
        {
            // Check if user already has a pending deletion request
            $user = auth()->user();
            if ($user->hasPendingDeletionRequest()) {
                Notification::make()
                    ->title('Request Already Exists')
                    ->body('You already have a pending data deletion request. Please check your email or contact support.')
                    ->warning()
                    ->duration(8000)
                    ->send();

                $this->dispatch('close-modal', id: 'delete-account-modal');
                return;
            }

            // Validate
            $rules = [
                'deleteReason' => 'nullable|string|max:1000',
                'confirmText' => 'required|in:delete my data',
            ];

            if ($this->hasActivePlan) {
                $rules['confirmPlanLoss'] = 'accepted';
            }

            $this->validate($rules, [
                'confirmPlanLoss.accepted' => 'You must confirm that you understand you will lose access to your plan.',
                'confirmText.required' => 'You must type the confirmation text to proceed.',
                'confirmText.in' => 'Please type exactly "delete my data" to confirm.',
            ]);

            // Create the deletion request
            $deletionRequest = \App\Models\DataDeletionRequest::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'facebook_user_id' => $user->facebook_id ?? null,
                'reason' => $this->deleteReason,
                'status' => 'pending',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Send confirmation email to user
            try {
                \Mail::to($user->email)->send(new \App\Mail\DataDeletionRequested($deletionRequest));

                \Log::info('Data deletion request email sent', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'confirmation_code' => $deletionRequest->confirmation_code,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to send data deletion request email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);

                // Still show success to user, but log the email failure
                // The request was created successfully
            }

            // Update pendingDeletion to show in UI immediately (reactive update)
            $this->pendingDeletion = $deletionRequest;

            // Close modal and reset form
            $this->deleteReason = '';
            $this->confirmText = '';
            $this->confirmPlanLoss = false;
            $this->dispatch('close-modal', id: 'delete-account-modal');

            Notification::make()
                ->title('Data deletion request submitted')
                ->body('Check your email for confirmation and cancellation instructions. Confirmation code: ' . $deletionRequest->confirmation_code)
                ->success()
                ->duration(15000)
                ->send();
        }

	}

?>

<x-layouts.app>
    @volt('settings.security')
        <div class="relative">
            <x-app.settings-layout
                title="Security"
                description="Update and change your current account password."
            >
                <!-- Change Password Section -->
                <form wire:submit="save" class="w-full max-w-lg">
                    {{ $this->form }}
                    <div class="w-full pt-6 text-right">
                        <x-button type="submit">Save</x-button>
                    </div>
                </form>

                <!-- Danger Zone Section -->
                <div class="w-full max-w-lg mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex flex-col p-5 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/50 rounded-lg">
                        <div class="flex flex-col">
                            <h3 class="text-base font-semibold text-red-900 dark:text-red-200">Danger Zone</h3>

                            @if($pendingDeletion)
                                <!-- Pending Deletion Warning -->
                                <div class="mt-4 p-4 bg-red-100 dark:bg-red-900/40 border border-red-300 dark:border-red-800 rounded-lg">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        <div class="flex-1">
                                            <p class="font-semibold text-red-900 dark:text-red-200 text-sm mb-2">Data Deletion Request Pending</p>
                                            <p class="text-sm text-red-800 dark:text-red-300 mb-2">
                                                You have submitted a data deletion request on {{ $pendingDeletion->created_at->format('M d, Y \a\t g:i A') }}.
                                            </p>
                                            <p class="text-sm text-red-800 dark:text-red-300 mb-2">
                                                <strong>Confirmation Code:</strong> <code class="px-2 py-1 bg-red-200 dark:bg-red-800 rounded font-mono text-xs">{{ $pendingDeletion->confirmation_code }}</code>
                                            </p>
                                            <p class="text-sm text-red-800 dark:text-red-300">
                                                <strong>To cancel:</strong> Email <a href="mailto:contact@evenleads.com" class="underline font-semibold hover:text-red-900 dark:hover:text-red-100">contact@evenleads.com</a> with your confirmation code.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-3 text-sm text-red-700 dark:text-red-300">You cannot submit another deletion request while one is pending.</p>
                            @else
                                <p class="mt-1 text-sm text-red-700 dark:text-red-300">Permanently delete your account and all associated data</p>
                                <button
                                    @click="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'delete-account-modal' }}))"
                                    type="button"
                                    class="mt-4 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 w-fit"
                                >
                                    Delete Account
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Delete Account Modal -->
                <div class="z-[99]">
                    <x-filament::modal id="delete-account-modal" width="2xl">
                        <form wire:submit.prevent="submitDeleteRequest">
                            <div class="flex flex-col justify-between w-full">
                                <!-- Icon and Title -->
                                <div class="flex flex-col items-center">
                                    <div class="flex items-center justify-center w-12 h-12 mx-auto text-center bg-red-100 dark:bg-red-900/30 rounded-full">
                                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center w-full">
                                        <h3 class="text-lg font-medium leading-6 text-zinc-900 dark:text-zinc-100">
                                            Delete Account
                                        </h3>
                                        <div class="mt-2">
                                            <p class="text-sm leading-5 text-zinc-500 dark:text-zinc-400">
                                                This action cannot be undone. All your data will be permanently deleted.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Fields -->
                                <div class="mt-5 space-y-4">
                                    <!-- Confirmation Text Field (REQUIRED) -->
                                    <div>
                                        <label for="confirmText" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                            Type "delete my data" to confirm <span class="text-red-600">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="confirmText"
                                            wire:model="confirmText"
                                            onpaste="return false;"
                                            autocomplete="off"
                                            class="block w-full rounded-lg border-zinc-300 dark:border-zinc-700 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-zinc-800 dark:text-white text-sm"
                                            placeholder="delete my data"
                                        />
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">For security reasons, paste is disabled. You must type this exactly.</p>
                                        @error('confirmText')
                                            <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Reason Field -->
                                    <div>
                                        <label for="deleteReason" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                            Reason (Optional)
                                        </label>
                                        <textarea
                                            id="deleteReason"
                                            wire:model="deleteReason"
                                            rows="3"
                                            class="block w-full rounded-lg border-zinc-300 dark:border-zinc-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-zinc-800 dark:text-white text-sm"
                                            placeholder="Please let us know why you're deleting your account..."
                                        ></textarea>
                                        @error('deleteReason')
                                            <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Active Plan Warning -->
                                    @if($hasActivePlan)
                                        <div class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50 rounded-lg p-4">
                                            <div class="flex">
                                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                <div class="flex-1">
                                                    <p class="font-bold text-red-800 dark:text-red-300 text-sm mb-2">Warning: Active Subscription</p>
                                                    <p class="text-sm text-red-700 dark:text-red-300 mb-3">
                                                        {{ $planDetails }}. By deleting your account, you will immediately lose access to all plan features and benefits.
                                                    </p>
                                                    <label class="flex items-start cursor-pointer">
                                                        <input
                                                            type="checkbox"
                                                            wire:model="confirmPlanLoss"
                                                            class="mt-1 rounded border-red-300 text-red-600 focus:ring-red-500 dark:bg-red-900/30 dark:border-red-700"
                                                        >
                                                        <span class="ml-2 text-sm text-red-800 dark:text-red-300">
                                                            I understand that I will lose access to my plan and all associated data immediately.
                                                        </span>
                                                    </label>
                                                    @error('confirmPlanLoss')
                                                        <span class="block mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Info Box -->
                                    <div class="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/50 rounded-lg p-4">
                                        <div class="flex">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                            <div class="text-sm text-blue-800 dark:text-blue-300">
                                                <p class="font-medium mb-1">What happens next?</p>
                                                <ul class="list-disc list-inside space-y-1 text-sm">
                                                    <li>Your request will be reviewed within 1-2 business days</li>
                                                    <li>Your data will be permanently deleted within 30 days</li>
                                                    <li>You will receive confirmation once completed</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse gap-3">
                                    <button
                                        type="submit"
                                        wire:loading.attr="disabled"
                                        class="inline-flex justify-center items-center w-full px-4 py-2 text-base font-medium leading-6 text-white transition duration-150 ease-in-out bg-red-600 border border-transparent rounded-lg shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 sm:text-sm sm:leading-5 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <svg wire:loading wire:target="submitDeleteRequest" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="submitDeleteRequest">Confirm Delete Account</span>
                                        <span wire:loading wire:target="submitDeleteRequest">Deleting...</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'delete-account-modal' }}))"
                                        class="inline-flex justify-center w-full px-4 py-2 text-base font-medium leading-6 text-zinc-700 dark:text-zinc-300 transition duration-150 ease-in-out bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 sm:text-sm sm:leading-5"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </x-filament::modal>
                </div>

            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
