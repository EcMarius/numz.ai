@php
    // Check if section should be shown
    $showSection = setting('site.show_feature_showcase', '1') == '1';

    // Load active feature showcases from database
    $featureShowcases = $showSection
        ? \App\Models\FeatureShowcase::active()->ordered()->get()
        : collect();
@endphp

@if($showSection && $featureShowcases->isNotEmpty())
<section class="w-full">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center"
         x-data="{
            selectedFeature: 0,
            features: {{ $featureShowcases->map(function($feature) {
                return [
                    'id' => $feature->id,
                    'title' => $feature->title,
                    'description' => $feature->description,
                    'media' => $feature->media_url,
                    'mediaType' => $feature->media_type,
                ];
            })->toJson() }},
            switchFeature(index) {
                if (this.selectedFeature !== index) {
                    this.selectedFeature = index;
                }
            }
         }">

        <!-- Left Side: Features List -->
        <div class="space-y-8">
            <!-- Badge and Heading -->
            <div class="space-y-4">
                <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-zinc-900 text-white">
                    Features
                </span>

                <h2 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                    See what we can do
                </h2>

                <p class="text-lg text-zinc-600 dark:text-zinc-400">
                    Discover how EvenLeads helps you find leads, engage with your audience, and grow your business across multiple platforms.
                </p>
            </div>

            <!-- Feature Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($featureShowcases as $index => $feature)
                    <button
                        type="button"
                        @click="switchFeature({{ $index }})"
                        :class="{
                            'border-zinc-900 dark:border-zinc-100 bg-zinc-900 dark:bg-zinc-100': selectedFeature === {{ $index }},
                            'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-600': selectedFeature !== {{ $index }}
                        }"
                        class="group relative p-5 rounded-xl border-2 transition-all duration-300 text-left cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 dark:focus:ring-offset-zinc-900">
                        <div class="space-y-2">
                            <h3
                                :class="{
                                    'text-white dark:text-zinc-900': selectedFeature === {{ $index }},
                                    'text-zinc-900 dark:text-white group-hover:text-zinc-600 dark:group-hover:text-zinc-400': selectedFeature !== {{ $index }}
                                }"
                                class="font-semibold transition-colors">
                                {{ $feature->title }}
                            </h3>
                            <p
                                :class="{
                                    'text-zinc-100 dark:text-zinc-800': selectedFeature === {{ $index }},
                                    'text-zinc-600 dark:text-zinc-400': selectedFeature !== {{ $index }}
                                }"
                                class="text-sm line-clamp-2">
                                {{ $feature->description }}
                            </p>
                        </div>

                        <!-- Selection Indicator -->
                        <div
                            x-show="selectedFeature === {{ $index }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            class="absolute top-3 right-3">
                            <div class="flex items-center justify-center w-6 h-6 rounded-full bg-white dark:bg-zinc-900">
                                <svg class="w-4 h-4 text-zinc-900 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Right Side: Dynamic Media Display -->
        <div class="relative">
            <div class="sticky top-24">
                <!-- Media Container with smooth transitions -->
                <div class="relative rounded-2xl overflow-hidden bg-zinc-100 dark:bg-zinc-800 shadow-2xl">
                    <div class="aspect-video relative">
                        <template x-for="(feature, index) in features" :key="feature.id">
                            <div
                                x-show="selectedFeature === index"
                                x-transition:enter="transition ease-out duration-500"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="absolute inset-0">

                                <!-- Image/GIF -->
                                <template x-if="feature.mediaType === 'image' || feature.mediaType === 'gif'">
                                    <img
                                        :src="feature.media || 'https://via.placeholder.com/1280x720/e4e4e7/71717a?text=Feature+Preview'"
                                        :alt="feature.title"
                                        class="w-full h-full object-cover"
                                        onerror="if(this.src!=='https://via.placeholder.com/1280x720/e4e4e7/71717a?text=Feature+Preview'){this.src='https://via.placeholder.com/1280x720/e4e4e7/71717a?text=Feature+Preview';}" />
                                </template>

                                <!-- Video -->
                                <template x-if="feature.mediaType === 'video'">
                                    <video
                                        :src="feature.media"
                                        class="w-full h-full object-cover"
                                        autoplay
                                        loop
                                        muted
                                        playsinline></video>
                                </template>
                            </div>
                        </template>

                        <!-- Loading Placeholder (shown if no media) -->
                        <div
                            x-show="!features[selectedFeature]?.media"
                            class="absolute inset-0 flex items-center justify-center bg-zinc-200 dark:bg-zinc-700">
                            <svg class="w-16 h-16 text-zinc-400 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Media Title Overlay -->
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-6">
                        <h4
                            x-text="features[selectedFeature]?.title"
                            class="text-xl font-semibold text-white"></h4>
                    </div>
                </div>

                <!-- Decorative Elements -->
                <div class="absolute -z-10 -top-4 -right-4 w-72 h-72 bg-zinc-200/20 dark:bg-zinc-800/20 rounded-full blur-3xl"></div>
                <div class="absolute -z-10 -bottom-4 -left-4 w-72 h-72 bg-zinc-300/20 dark:bg-zinc-700/20 rounded-full blur-3xl"></div>
            </div>
        </div>
    </div>
</section>
@endif
