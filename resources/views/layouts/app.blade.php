<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'eLINGAP' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="min-h-screen md:flex">
        <aside id="staff-sidebar" class="border-b border-slate-200 bg-osca-primary text-white md:min-h-screen md:w-64">
            <div class="flex items-center justify-between gap-2 p-4">
                <a href="{{ auth()->user()->isAdmin() ? route('administration.dashboard') : route('dashboard') }}" class="staff-nav-label font-bold">eLINGAP</a>
                <button type="button" id="staff-nav-toggle" class="rounded p-2 hover:bg-white/10 focus-visible:outline-2" aria-label="Collapse navigation" aria-expanded="true">☰</button>
            </div>
            <nav aria-label="Staff navigation" class="grid gap-1 p-3">
                @foreach ([['Dashboard', auth()->user()->isAdmin() ? 'administration.dashboard' : 'dashboard', auth()->user()->isAdmin() ? 'administration.dashboard' : 'dashboard'], ['Masterlist', 'senior-citizens.index', 'senior-citizens.*'], ['Registrations', 'applications.index', 'applications.*'], ['Payouts', 'payouts.index', 'payouts.*'], ['Messaging', 'sms.blasts.index', 'sms.*'], ['Reports', 'reports.index', 'reports.*']] as [$label, $route, $pattern])
                    <a href="{{ route($route) }}" title="{{ $label }}" aria-label="{{ $label }}" @class(['rounded px-3 py-2 hover:bg-white/10 focus-visible:outline-2', 'bg-white/20 font-semibold' => request()->routeIs($pattern)]) @if(request()->routeIs($pattern)) aria-current="page" @endif>
                        <span aria-hidden="true">{{ mb_substr($label, 0, 1) }}</span> <span class="staff-nav-label">{{ $label }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>
        <div class="min-w-0 flex-1">
            <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 md:px-8">
                <span class="font-semibold">Office for Senior Citizens Affairs</span>
                <div class="flex items-center gap-3 text-sm">
                    <span>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf <button class="rounded border px-3 py-1 hover:bg-slate-100">Log out</button></form>
                </div>
            </header>
            <main class="mx-auto max-w-7xl p-4 md:p-8">
                @include('components.alert')
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
