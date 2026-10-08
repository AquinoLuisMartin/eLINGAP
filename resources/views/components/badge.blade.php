@props(['status'])
@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $classes = match ($value) {
        'VERIFIED', 'ACTIVE', 'APPROVED', 'RELEASED', 'SENT' => 'bg-green-100 text-green-800',
        'REJECTED', 'FAILED', 'ARCHIVED', 'VOIDED' => 'bg-red-100 text-red-800',
        default => 'bg-amber-100 text-amber-800',
    };
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold', $classes]) }}><span aria-hidden="true">●</span>{{ ucfirst(strtolower($value)) }}</span>
