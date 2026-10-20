@props(['section'])
@php
    $items = match ($section) {
        'messaging' => [['Broadcasts', 'sms.blasts.index', 'sms.blasts.*'], ['Individual messages', 'sms.messages.index', 'sms.messages.*'], ['Templates', 'sms.templates.index', 'sms.templates.*']],
        'payouts' => [['Payout records', 'payouts.index', 'payouts.*'], ['Payout schedules', 'payout-schedules.index', 'payout-schedules.*']],
        'benefits' => [['Schedules and reports', 'benefits.workspace', 'benefits.workspace'], ['Program rules', 'benefits.programs', 'benefits.programs']],
        default => [],
    };
@endphp
<nav aria-label="{{ ucfirst($section) }} navigation" class="workspace-navigation mb-5 flex w-fit max-w-full gap-1 overflow-x-auto rounded-lg border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-900">
    @foreach ($items as [$label, $route, $pattern])
        <a href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif @class(['inline-flex min-h-10 shrink-0 items-center rounded-md px-3 py-2 text-sm', 'bg-osca-primary text-white' => request()->routeIs($pattern), 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' => !request()->routeIs($pattern)])>{{ $label }}</a>
    @endforeach
</nav>
