@php
    // Check if stats section should be shown
    $showStats = setting('site.show_stats', '1') == '1';

    // Load active stats from database
    $stats = $showStats
        ? \App\Models\Stat::active()->ordered()->get()
        : collect();
@endphp

@if($showStats && $stats->isNotEmpty())
<section class="w-full">
    <div class="text-center mb-16">
        <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">"Show Me The Numbers"</h2>
        <p class="mt-4 text-lg text-zinc-600">Key performance indicators across our platform</p>
    </div>

    <div class="grid grid-cols-1 gap-12 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($stats as $stat)
        <div class="flex flex-col items-center justify-center text-center"
             x-data="{
                 current: 0,
                 target: {{ preg_replace('/[^0-9]/', '', $stat->value) }},
                 suffix: '{{ preg_replace('/[0-9,]/', '', $stat->value) }}',
                 init() {
                     // Use Intersection Observer to trigger animation when in view
                     const observer = new IntersectionObserver((entries) => {
                         entries.forEach(entry => {
                             if (entry.isIntersecting && this.current === 0) {
                                 this.animateNumber();
                             }
                         });
                     }, { threshold: 0.5 });
                     observer.observe(this.$el);
                 },
                 animateNumber() {
                     const duration = 2000; // 2 seconds
                     const steps = 60;
                     const increment = this.target / steps;
                     const stepDuration = duration / steps;

                     const timer = setInterval(() => {
                         this.current += increment;
                         if (this.current >= this.target) {
                             this.current = this.target;
                             clearInterval(timer);
                         }
                     }, stepDuration);
                 },
                 formatNumber(num) {
                     return Math.floor(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                 }
             }">
            <div class="text-6xl font-bold text-zinc-900 mb-3">
                <span x-text="formatNumber(current) + suffix"></span>
            </div>
            <div class="text-base font-medium text-zinc-600 uppercase tracking-wide">
                {{ $stat->label }}
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
