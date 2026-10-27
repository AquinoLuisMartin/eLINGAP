@props(['type' => 'button', 'variant' => 'primary'])
@php
    $style = match ($variant) {
        'secondary' => 'secondary-button',
        'danger' => 'danger-button',
        'text' => 'text-button',
        default => 'primary-button',
    };
@endphp
<button {{ $attributes->class([$style])->merge(['type' => $type]) }}>
    {{ $slot }}
</button>
