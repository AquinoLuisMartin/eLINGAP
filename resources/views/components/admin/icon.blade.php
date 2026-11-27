@props(['name', 'size' => 18, 'class' => ''])

@php
    $icons = [
        'info', 'map-pin', 'layout-dashboard', 'users', 'heart-pulse',
        'message-square', 'user-cog', 'clipboard-list', 'settings', 'circle-help',
        'chevron-down', 'chevron-right', 'chevron-left', 'menu', 'search',
        'calendar-days', 'bell', 'moon', 'sun', 'check',
        'x', 'plus', 'pencil', 'download', 'sliders-horizontal',
        'more-horizontal', 'arrow-up-right', 'arrow-down-right', 'activity', 'bar-chart-3',
        'send', 'alert-triangle', 'shield-check', 'sparkles', 'gauge',
        'file-text', 'file-check-2', 'archive', 'log-out', 'clock-3',
        'house', 'contact-round', 'clipboard-pen-line', 'banknote', 'messages-square',
        'chart-no-axes-combined', 'hand-heart',
        'arrow-right', 'key-round', 'log-in', 'shield-alert',
        'user-round-check', 'user-round-minus', 'user-round-plus', 'user-round-x',
        'id-card', 'printer', 'printer-check', 'printer-x',
    ];
    $icon = match ($name) {
        'dashboard' => 'layout-dashboard',
        'help' => 'circle-help',
        default => in_array($name, $icons, true) ? $name : 'circle',
    };
@endphp

<x-icon :name="'lucide-'.$icon" {{ $attributes->class($class)->merge(['width' => (int) $size, 'height' => (int) $size, 'aria-hidden' => 'true']) }} />
