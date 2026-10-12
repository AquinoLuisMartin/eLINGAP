@props(['status'])
@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $classes = match ($value) {
        'VERIFIED', 'ACTIVE', 'APPROVED', 'RELEASED', 'SENT' => 'bg-green-100 text-green-800',
        'REJECTED', 'FAILED', 'ARCHIVED', 'VOIDED' => 'bg-red-100 text-red-800',
        default => 'bg-amber-100 text-amber-800',
    };
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded px-2 py-1 text-xs font-normal', $classes]) }}>{{ ucfirst(strtolower($value)) }}</span>
