@props(['color', 'value', 'label', 'detail'])

@php($colorValue = ['primary' => '#00327D', 'warning' => '#E0A800', 'success' => '#1E8E3E', 'danger' => '#C0293C'][$color])

<article class="rounded-lg border border-osca-border bg-white p-5">
    <div class="mb-5 flex size-11 items-center justify-center rounded-lg" style="background-color: color-mix(in srgb, {{ $colorValue }} 10%, transparent); color: {{ $colorValue }}">{{ $slot }}</div>
    <p class="text-3xl font-bold text-osca-ink">{{ $value }}</p>
    <p class="mt-1 text-sm font-medium">{{ $label }}</p>
    <p class="mt-3 text-xs">{{ $detail }}</p>
</article>
