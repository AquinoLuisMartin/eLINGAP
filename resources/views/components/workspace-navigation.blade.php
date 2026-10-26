@props(['section'])
@php
    $items = match ($section) {
        'records' => [['Senior Registry', 'senior-citizens.index', 'senior-citizens.*'], ['Registrations', 'registrations.index', 'registrations.*']],
        'messaging' => [['Broadcasts', 'sms.blasts.index', 'sms.blasts.*'], ['Individual messages', 'sms.messages.index', 'sms.messages.*'], ['Templates', 'sms.templates.index', 'sms.templates.*']],
        'payouts' => [['Payout records', 'payouts.index', 'payouts.*'], ['Payout schedules', 'payout-schedules.index', 'payout-schedules.*']],
        'benefits' => [['Schedules and reports', 'benefits.workspace', 'benefits.workspace'], ['Program rules', 'benefits.programs', 'benefits.programs']],
        default => [],
    };
    $fromRegistrations = $section === 'records' && request('from') === 'registrations';
@endphp
<nav aria-label="{{ ucfirst($section) }} navigation" class="workspace-navigation mb-5 flex w-fit max-w-full gap-1 overflow-x-auto rounded-lg border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-900">
    @foreach ($items as [$label, $route, $pattern])
        @php($isActive = $section === 'records'
            ? ($route === 'registrations.index' ? (request()->routeIs('registrations.*') || $fromRegistrations) : (!request()->routeIs('registrations.*') && !$fromRegistrations))
            : request()->routeIs($pattern))
        <a href="{{ route($route) }}" @if($isActive) aria-current="page" @endif @class(['inline-flex min-h-11 shrink-0 items-center rounded-md px-3 py-2 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary', 'bg-osca-primary text-white' => $isActive, 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' => !$isActive])>{{ $label }}</a>
    @endforeach
</nav>
