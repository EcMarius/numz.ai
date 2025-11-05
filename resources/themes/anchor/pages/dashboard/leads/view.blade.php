@php
    use Wave\Plugins\EvenLeads\Models\Lead;

    $leadId = request()->route('id');
    $lead = Lead::with('campaign')->find($leadId);

    if (!$lead || $lead->campaign->user_id !== auth()->id()) {
        abort(404);
    }

    $platformColors = [
        'facebook' => '#1877F2',
        'linkedin' => '#0A66C2',
        'reddit' => '#FF4500',
        'fiverr' => '#1DBF73',
        'upwork' => '#14A800',
        'x' => '#000000',
    ];

    $statusColors = [
        'new' => ['bg' => 'bg-blue-100 dark:bg-blue-900', 'text' => 'text-blue-800 dark:text-blue-200'],
        'contacted' => ['bg' => 'bg-green-100 dark:bg-green-900', 'text' => 'text-green-800 dark:text-green-200'],
        'qualified' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900', 'text' => 'text-yellow-800 dark:text-yellow-200'],
        'converted' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900', 'text' => 'text-emerald-800 dark:text-emerald-200'],
        'archived' => ['bg' => 'bg-gray-100 dark:bg-gray-900', 'text' => 'text-gray-800 dark:text-gray-200'],
    ];

    $platformColor = $platformColors[$lead->platform] ?? '#6B7280';
    $statusColor = $statusColors[$lead->status] ?? $statusColors['new'];
@endphp

<x-layouts.app>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <!-- Back Button -->
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 mb-6 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Dashboard
        </a>

        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex items-start gap-4">
                <!-- Platform Icon -->
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-lg" style="background-color: {{ $platformColor }}">
                        {{ strtoupper(substr($lead->platform, 0, 2)) }}
                    </div>
                </div>

                <!-- Title & Meta -->
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $lead->title }}
                    </h1>
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusColor['bg'] }} {{ $statusColor['text'] }}">
                            {{ ucfirst($lead->status) }}
                        </span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Confidence: {{ $lead->confidence_score }}/10
                        </span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ ucfirst($lead->platform) }}
                        </span>
                        @if($lead->subreddit)
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                r/{{ $lead->subreddit }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex-shrink-0">
                    <a href="{{ $lead->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90 transition" style="background-color: {{ $platformColor }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        View on {{ ucfirst($lead->platform) }}
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Description -->
                @if($lead->description)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        Description
                    </h2>
                    <div class="prose dark:prose-invert max-w-none">
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $lead->description }}</p>
                    </div>
                </div>
                @endif

                <!-- Matched Keywords -->
                @if($lead->matched_keywords && count($lead->matched_keywords) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Matched Keywords
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach($lead->matched_keywords as $keyword)
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600">
                            {{ $keyword }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Campaign Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Campaign
                    </h2>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <a href="{{ route('dashboard') }}#campaigns" class="hover:underline">
                            {{ $lead->campaign->name }}
                        </a>
                    </p>
                </div>

                <!-- Author Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Author
                    </h2>
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $lead->author }}</p>
                </div>

                <!-- Metadata -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Details
                    </h2>
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Created:</span>
                            <p class="text-gray-900 dark:text-white">{{ $lead->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        @if($lead->synced_at)
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Synced:</span>
                            <p class="text-gray-900 dark:text-white">{{ $lead->synced_at->format('M d, Y H:i') }}</p>
                        </div>
                        @endif
                        @if($lead->comments_count)
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Comments:</span>
                            <p class="text-gray-900 dark:text-white">{{ $lead->comments_count }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
