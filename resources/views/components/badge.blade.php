@props(['status'])
@php($value = $status instanceof \BackedEnum ? $status->value : (string) $status)
<span {{ $attributes->class(['ui-label']) }}>{{ ucfirst(strtolower($value)) }}</span>
