@props(['section'])
@php
    $items = match ($section) {
        'messaging' => [['Broadcasts', 'sms.blasts.create', 'sms.blasts.*'], ['Individual messages', 'sms.messages.index', 'sms.messages.*'], ['Templates', 'sms.templates.index', 'sms.templates.*']],
        'payouts' => [['Payout records', 'payouts.index', 'payouts.*'], ['Payout schedules', 'payout-schedules.index', 'payout-schedules.*']],
        'benefits' => [['Schedules and reports', 'benefits.workspace', 'benefits.workspace'], ['Program rules', 'benefits.programs', 'benefits.programs']],
        default => [],
    };
@endphp
<nav aria-label="{{ ucfirst($section) }} navigation" class="workspace-navigation flex">
    @foreach ($items as [$label, $route, $pattern])
        <a href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif class="inline-flex shrink-0 items-center text-sm">{{ $label }}</a>
    @endforeach
</nav>
