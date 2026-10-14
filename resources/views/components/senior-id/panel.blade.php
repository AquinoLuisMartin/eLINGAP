@props(['title', 'description' => null])
<section {{ $attributes->class(['rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900 sm:p-8']) }}>
    <h3 class="text-xl font-medium text-osca-primary dark:text-slate-100">{{ $title }}</h3>
    @if ($description)<p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>@endif
    <div class="mt-6">{{ $slot }}</div>
</section>
