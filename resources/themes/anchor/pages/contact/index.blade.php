<?php
    use function Laravel\Folio\{name};
    use Livewire\Volt\Component;
    use Livewire\Attributes\Validate;
    use Illuminate\Support\Facades\RateLimiter;

    name('contact');

    new class extends Component
    {
        #[Validate('required|string|min:2')]
        public $name = '';

        #[Validate('required|email')]
        public $email = '';

        #[Validate('nullable|string')]
        public $company = '';

        #[Validate('required|string')]
        public $subject = '';

        #[Validate('required|string|min:10')]
        public $message = '';

        public $submitted = false;

        public function submit()
        {
            // Rate limiting: 3 contact form submissions per 10 minutes per IP
            $key = 'contact-form:' . request()->ip();

            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableIn($key);
                session()->flash('error', 'Too many contact form submissions. Please try again in ' . ceil($seconds / 60) . ' minute(s).');
                return;
            }

            $this->validate();

            // Increment rate limiter after validation
            RateLimiter::hit($key, 600); // 10 minutes decay

            // Here you can add logic to send email or store in database
            // For now, we'll just show success message

            // Example: Send email to admin
            // Mail::to(config('mail.from.address'))->send(new ContactFormMail($this->all()));

            $this->submitted = true;
            $this->reset(['name', 'email', 'company', 'subject', 'message']);
        }
    };
?>

<x-layouts.marketing
    :seo="[
        'title' => 'Contact Us - EvenLeads',
        'description' => 'Get in touch with us for custom solutions, enterprise plans, or any questions about EvenLeads.',
    ]"
>

    @volt('contact')
        <x-container class="py-10 sm:py-20">
            <div class="max-w-5xl mx-auto">
                <!-- Header -->
                <div class="text-center mb-16">
                    <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold tracking-tighter text-zinc-900 dark:text-white mb-4">
                        Get in Touch
                    </h1>
                    <p class="text-lg md:text-xl text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
                        Have questions about enterprise plans or custom solutions? We'd love to hear from you.
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-8 mb-12">
                    <!-- Contact Info Cards -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 shadow-sm p-6 flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">Email Us</h3>
                            <a href="mailto:{{ config('mail.from.address', 'hello@evenleads.com') }}" class="text-zinc-600 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400">
                                {{ config('mail.from.address', 'hello@evenleads.com') }}
                            </a>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 shadow-sm p-6 flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">Response Time</h3>
                            <p class="text-zinc-600 dark:text-zinc-400">Within 24 hours</p>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div>
                    @if($submitted)
                        <div class="bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800 rounded-xl p-8 text-center">
                            <svg class="w-16 h-16 text-green-600 dark:text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-2xl font-bold text-green-900 dark:text-green-100 mb-2">Message Sent!</h3>
                            <p class="text-green-700 dark:text-green-300 mb-6">Thank you for contacting us. We'll get back to you within 24 hours.</p>
                            <button wire:click="$set('submitted', false)" class="px-6 py-3 bg-green-600 dark:bg-green-700 text-white rounded-lg hover:bg-green-700 dark:hover:bg-green-600 transition-all font-medium">
                                Send Another Message
                            </button>
                        </div>
                    @else
                        <form wire:submit.prevent="submit" class="bg-white dark:bg-zinc-800 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 shadow-sm p-8">
                                <div class="grid md:grid-cols-2 gap-6">
                                    <!-- Name -->
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                            Name <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            wire:model="name"
                                            class="block w-full rounded-lg border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm focus:border-zinc-900 dark:focus:border-white focus:ring-zinc-900 dark:focus:ring-white"
                                            placeholder="John Doe"
                                        >
                                        @error('name')
                                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                            Email <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            wire:model="email"
                                            class="block w-full rounded-lg border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm focus:border-zinc-900 dark:focus:border-white focus:ring-zinc-900 dark:focus:ring-white"
                                            placeholder="john@example.com"
                                        >
                                        @error('email')
                                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Company -->
                                <div class="mt-6">
                                    <label for="company" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                        Company
                                    </label>
                                    <input
                                        type="text"
                                        id="company"
                                        wire:model="company"
                                        class="block w-full rounded-lg border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm focus:border-zinc-900 dark:focus:border-white focus:ring-zinc-900 dark:focus:ring-white"
                                        placeholder="Your Company Name"
                                    >
                                    @error('company')
                                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Subject -->
                                <div class="mt-6">
                                    <label for="subject" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                        Subject <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="subject"
                                        wire:model="subject"
                                        class="block w-full rounded-lg border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm focus:border-zinc-900 dark:focus:border-white focus:ring-zinc-900 dark:focus:ring-white"
                                    >
                                        <option value="">Select a subject</option>
                                        <option value="Enterprise Plan">Enterprise Plan Inquiry</option>
                                        <option value="Custom Solution">Custom Solution</option>
                                        <option value="General Question">General Question</option>
                                        <option value="Technical Support">Technical Support</option>
                                        <option value="Partnership">Partnership Opportunity</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    @error('subject')
                                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Message -->
                                <div class="mt-6">
                                    <label for="message" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                        Message <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        id="message"
                                        wire:model="message"
                                        rows="6"
                                        class="block w-full rounded-lg border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm focus:border-zinc-900 dark:focus:border-white focus:ring-zinc-900 dark:focus:ring-white"
                                        placeholder="Tell us about your needs..."
                                    ></textarea>
                                    @error('message')
                                        <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <div class="mt-8">
                                    <button
                                        type="submit"
                                        class="w-full px-6 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-medium rounded-lg hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-all"
                                    >
                                        Send Message
                                    </button>
                                </div>
                        </form>
                    @endif
                </div>
            </div>
        </x-container>
    @endvolt

</x-layouts.marketing>
