@php
    $navItems = [
        ['label' => 'Dashboard', 'icon' => 'grid', 'active' => true],
        ['label' => 'Senior citizens', 'icon' => 'users', 'active' => false],
        ['label' => 'Applications', 'icon' => 'clipboard', 'active' => false],
        ['label' => 'Programs', 'icon' => 'folder', 'active' => false],
        ['label' => 'Reports', 'icon' => 'chart', 'active' => false],
    ];

    $stats = [
        ['label' => 'Registered senior citizens', 'value' => '1,248', 'change' => '+12.5%', 'tone' => 'blue'],
        ['label' => 'Pending applications', 'value' => '86', 'change' => '+8.2%', 'tone' => 'amber'],
        ['label' => 'Approved this month', 'value' => '214', 'change' => '+18.4%', 'tone' => 'green'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff dashboard | eLINGAP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-72 shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col">
            <div class="flex h-20 items-center gap-3 border-b border-slate-100 px-7">
                <div class="flex size-10 items-center justify-center rounded-xl bg-osca-primary text-lg font-bold text-white">e</div>
                <div>
                    <p class="text-lg font-bold tracking-tight text-osca-primary">eLINGAP</p>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Care, dignity, service</p>
                </div>
            </div>

            <div class="px-4 py-7">
                <p class="px-3 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Workspace</p>
                <nav class="mt-3 space-y-1" aria-label="Primary navigation">
                    @foreach ($navItems as $item)
                        <a href="#" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold {{ $item['active'] ? 'bg-blue-50 text-osca-primary' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="flex size-8 items-center justify-center rounded-lg {{ $item['active'] ? 'bg-white' : 'bg-slate-100' }}">
                                @include('staff.partials.icon', ['name' => $item['icon']])
                            </span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="mt-auto border-t border-slate-100 p-5">
                <div class="rounded-2xl bg-osca-primary-dark p-5 text-white">
                    <p class="text-sm font-semibold">Need assistance?</p>
                    <p class="mt-1 text-xs leading-5 text-blue-100">Visit the help center for guides and support.</p>
                    <a href="#" class="mt-4 inline-flex items-center text-xs font-bold text-white hover:text-blue-100">Open help center <span class="ml-2" aria-hidden="true">&#8594;</span></a>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-20 items-center justify-between border-b border-slate-200 bg-white px-5 sm:px-8">
                <div class="flex items-center gap-3">
                    <button type="button" class="flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 lg:hidden" aria-label="Open navigation">
                        <span class="text-xl" aria-hidden="true">&#9776;</span>
                    </button>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-400">OSCA workspace</p>
                        <h1 class="text-lg font-bold text-slate-900 sm:text-xl">Good morning, {{ $user['name'] }}</h1>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" class="relative flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Notifications">
                        <span class="text-lg" aria-hidden="true">&#128276;</span>
                        <span class="absolute right-2 top-2 size-2 rounded-full bg-osca-danger ring-2 ring-white"></span>
                    </button>
                    <div class="hidden h-9 w-px bg-slate-200 sm:block"></div>
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-osca-primary">MS</div>
                        <div class="hidden sm:block">
                            <p class="text-sm font-bold text-slate-800">{{ $user['name'] }}</p>
                            <p class="text-xs text-slate-400">{{ $user['role'] }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-5 py-8 sm:px-8 lg:px-10">
                <div class="mx-auto max-w-7xl">
                    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Thursday, September 24, 2026</p>
                            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Your overview</h2>
                        </div>
                        <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-osca-primary px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-osca-primary-dark">
                            <span class="text-lg leading-none" aria-hidden="true">+</span> Register senior citizen
                        </button>
                    </div>

                    <section class="grid gap-4 md:grid-cols-3" aria-label="Dashboard statistics">
                        @foreach ($stats as $stat)
                            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <p class="max-w-[14rem] text-sm font-medium leading-5 text-slate-500">{{ $stat['label'] }}</p>
                                    <span class="rounded-lg p-2 {{ $stat['tone'] === 'blue' ? 'bg-blue-50 text-osca-primary' : ($stat['tone'] === 'amber' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') }}" aria-hidden="true">&#9679;</span>
                                </div>
                                <div class="mt-5 flex items-end justify-between">
                                    <p class="text-3xl font-bold tracking-tight text-slate-900">{{ $stat['value'] }}</p>
                                    <p class="text-xs font-bold text-emerald-600">{{ $stat['change'] }} <span class="font-medium text-slate-400">vs last month</span></p>
                                </div>
                            </article>
                        @endforeach
                    </section>

                    <section class="mt-8 grid gap-6 xl:grid-cols-[1.35fr_1fr]">
                        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-slate-900">Recent applications</h3>
                                    <p class="mt-1 text-sm text-slate-500">Review the latest requests from your community.</p>
                                </div>
                                <a href="#" class="text-sm font-bold text-osca-primary hover:text-osca-primary-dark">View all</a>
                            </div>
                            <div class="mt-6 overflow-x-auto">
                                <table class="w-full min-w-[34rem] text-left text-sm">
                                    <thead class="border-b border-slate-100 text-xs font-bold uppercase tracking-wider text-slate-400">
                                        <tr><th class="pb-3">Applicant</th><th class="pb-3">Program</th><th class="pb-3">Status</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ([['name' => 'Elena Cruz', 'program' => 'Social pension', 'status' => 'Pending'], ['name' => 'Ramon Dela Cruz', 'program' => 'Medical assistance', 'status' => 'Approved'], ['name' => 'Lourdes Garcia', 'program' => 'Social pension', 'status' => 'For review']] as $application)
                                            <tr><td class="py-4 font-semibold text-slate-700">{{ $application['name'] }}</td><td class="py-4 text-slate-500">{{ $application['program'] }}</td><td class="py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $application['status'] === 'Approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $application['status'] }}</span></td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div><h3 class="font-bold text-slate-900">Quick actions</h3><p class="mt-1 text-sm text-slate-500">Common tasks at your fingertips.</p></div>
                            </div>
                            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                @foreach ([['title' => 'Add a senior citizen', 'description' => 'Create a new record'], ['title' => 'Verify an application', 'description' => 'Review pending requests'], ['title' => 'View reports', 'description' => 'See program performance']] as $action)
                                    <a href="#" class="group flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 p-4 transition hover:border-blue-100 hover:bg-blue-50"><div><p class="text-sm font-bold text-slate-800">{{ $action['title'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $action['description'] }}</p></div><span class="text-lg text-osca-primary transition group-hover:translate-x-1" aria-hidden="true">&#8594;</span></a>
                                @endforeach
                            </div>
                        </article>
                    </section>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
