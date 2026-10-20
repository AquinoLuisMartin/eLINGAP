@props(['label'])

<div {{ $attributes->class(['staff-table max-w-full overflow-x-auto']) }} role="region" aria-label="{{ $label }}" tabindex="0">
    {{ $slot }}
</div>
