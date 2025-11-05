<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Data Deletion Request</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Request deletion of your personal data from our platform
            </p>
        </div>

        @if(!$submitted)
            <!-- Request Form -->
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 md:p-8">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Submit Data Deletion Request</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        We take your privacy seriously. Submit a request below and we will process your data deletion within 30 days.
                    </p>
                </div>

                <form wire:submit.prevent="submit" class="space-y-6">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            wire:model="email"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                            placeholder="your@email.com"
                            required
                        >
                        @error('email')
                            <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Reason Field -->
                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Reason (Optional)
                        </label>
                        <textarea
                            id="reason"
                            wire:model="reason"
                            rows="4"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                            placeholder="Please let us know why you're requesting data deletion..."
                        ></textarea>
                        @error('reason')
                            <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Active Plan Warning -->
                    @if($hasActivePlan)
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div class="flex-1">
                                    <p class="font-bold text-red-800 dark:text-red-300 mb-2">Warning: Active Subscription</p>
                                    <p class="text-sm text-red-700 dark:text-red-300 mb-3">
                                        {{ $planDetails }}. By submitting this data deletion request, you will immediately lose access to all plan features and benefits.
                                    </p>
                                    <label class="flex items-start">
                                        <input
                                            type="checkbox"
                                            wire:model="confirmPlanLoss"
                                            class="mt-1 rounded border-red-300 text-red-600 focus:ring-red-500 dark:bg-red-900 dark:border-red-700"
                                        >
                                        <span class="ml-2 text-sm text-red-800 dark:text-red-300">
                                            I understand that I will lose access to my plan and all associated data immediately upon data deletion.
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
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-blue-800 dark:text-blue-300">
                                <p class="font-medium mb-1">What happens next?</p>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>You will receive a confirmation code</li>
                                    <li>Our team will review your request within 1-2 business days</li>
                                    <li>Your data will be permanently deleted within 30 days</li>
                                    <li>You will receive confirmation once completed</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                        >
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        @else
            <!-- Success Message -->
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 md:p-8">
                <div class="text-center">
                    <!-- Success Icon -->
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/20 mb-4">
                        <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Request Submitted Successfully!</h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Your data deletion request has been received and will be processed shortly.
                    </p>

                    <!-- Confirmation Code -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Your confirmation code:</p>
                        <code class="text-lg font-mono font-semibold text-gray-900 dark:text-white">
                            {{ $confirmationCode }}
                        </code>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            Please save this code for your records
                        </p>
                    </div>

                    <!-- Next Steps -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-left">
                        <p class="font-medium text-blue-900 dark:text-blue-300 mb-2">What happens next?</p>
                        <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800 dark:text-blue-300">
                            <li>Our team will review your request (1-2 business days)</li>
                            <li>You'll receive an email confirmation</li>
                            <li>Your data will be permanently deleted within 30 days</li>
                            <li>You'll receive a final confirmation when complete</li>
                        </ol>
                    </div>

                    <!-- Back Button -->
                    <div class="mt-6">
                        <a href="/" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                            ← Return to Home
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Additional Information -->
        <div class="mt-8 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">About Data Deletion</h3>
            <div class="prose prose-sm dark:prose-invert text-gray-600 dark:text-gray-400">
                <p class="mb-3">
                    When you submit a data deletion request, we will remove:
                </p>
                <ul class="list-disc list-inside space-y-1 mb-3">
                    <li>Your account information and profile data</li>
                    <li>Campaign data and leads you've created</li>
                    <li>Generated AI replies and history</li>
                    <li>Platform connections (Reddit, Facebook, etc.)</li>
                    <li>Any other personal data associated with your account</li>
                </ul>
                <p class="text-sm">
                    <strong>Note:</strong> Some data may be retained for legal and accounting purposes for up to 7 years as required by law.
                </p>
            </div>
        </div>
    </div>
</div>
