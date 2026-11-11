<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eLINGAP</title>
    <x-favicon />
    <script>
        (function () {
            var theme = 'light';
            try { theme = localStorage.getItem('elingap-theme') === 'dark' ? 'dark' : 'light'; } catch {}
            document.documentElement.classList.add(theme === 'dark' ? 'theme-dark' : 'theme-light');
            document.documentElement.style.colorScheme = theme;
        })();
    </script>
    @vite(['resources/css/admin.css', 'resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @if (session('clear-registration-names'))
        <script>
            try { sessionStorage.removeItem({{ \Illuminate\Support\Js::from('elingap-id-names-'.auth()->id()) }}); } catch {}
        </script>
    @endif
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    @php($user = auth()->user()->loadMissing('role'))
    @php($isAdmin = $user->isAdmin())
    @php($portalLabel = $isAdmin ? 'System Administration' : 'OSCA Staff Portal')
    @php($recordsSection = request()->routeIs('registrations.import.*') ? 'registry' : (request()->routeIs('registrations.*') || request('from') === 'registrations' ? 'registrations' : 'registry'))
    @php($lastLoginAt = array_key_exists('last_login_at', $user->getAttributes()) ? $user->last_login_at : null)
    <div class="admin-shell staff-shell {{ $isAdmin ? '' : 'osca-workspace' }}" x-data="{
        sidebarOpen: false,
        sidebarTrigger: null,
        adminOpen: true,
        profileOpen: @js($errors->has('photo')),
        profileTrigger: null,
        ...workspaceTheme(),
    }" :class="theme === 'dark' ? 'theme-dark' : 'theme-light'" @storage.window="syncTheme($event)" @keydown.escape.window="if (profileOpen) profileTrigger?.focus(); else if (sidebarOpen) sidebarTrigger?.focus(); profileOpen = false; sidebarOpen = false" @resize.window="sidebarOpen = false">
        <aside id="osca-navigation" class="admin-sidebar" x-trap.inert.noscroll="sidebarOpen && window.matchMedia('(max-width: 760px)').matches" :class="{ 'is-expanded': sidebarOpen }" @mouseenter="if (window.matchMedia('(min-width: 761px)').matches) sidebarOpen = true" @mouseleave="if (window.matchMedia('(min-width: 761px)').matches && !$el.contains(document.activeElement)) sidebarOpen = false" @focusin="sidebarOpen = true" @focusout="if (window.matchMedia('(min-width: 761px)').matches && !$el.contains($event.relatedTarget) && !$el.matches(':hover')) sidebarOpen = false">
            <div class="sidebar-brand">
                <div class="brand-mark">
                    <img class="brand-logo" src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP Logo">
                </div>
                <div class="brand-copy">
                    <strong>eLINGAP</strong>
                    <small>{{ $officeDetails['office_name'] ?? 'Office of the Senior Citizen Affairs' }}</small>
                </div>
            </div>

            @if ($isAdmin)
                <x-admin.navigation :active="match (true) {
                    request()->routeIs('administration.users.*') => 'users',
                    request()->routeIs('benefits.*', 'programs.*', 'administration.programs.*', 'payouts.*', 'payout-schedules.*') => 'programs',
                    request()->routeIs('sms.*') => 'sms',
                    request()->routeIs('senior-citizens.*', 'senior-id.*', 'registrations.*', 'applications.*') => 'records',
                    default => '',
                }" />
            @else
            <nav class="sidebar-nav" aria-label="OSCA Staff navigation">
                <p class="nav-label">Workspace</p>
                @foreach ([
                    ['Dashboard', 'dashboard', 'layout-dashboard', 'dashboard'],
                    ['Senior Masterlist', 'senior-citizens.index', 'users', 'senior-citizens.*'],
                    ['Registrations', 'registrations.index', 'clipboard-list', 'registrations.*'],
                    ['Payouts', 'payouts.index', 'archive', 'payouts.*'],
                    ['Messaging', 'sms.blasts.create', 'message-square', 'sms.*'],
                    ['Reports', 'reports.index', 'bar-chart-3', 'reports.*'],
                    ['Benefits Workspace', 'benefits.workspace', 'heart-pulse', 'benefits.*'],
                ] as [$label, $route, $icon, $pattern])
                    @php($isActive = in_array($route, ['senior-citizens.index', 'registrations.index'])
                        ? (request()->routeIs('senior-citizens.*', 'senior-id.*', 'registrations.*') && ($route === 'registrations.index') === ($recordsSection === 'registrations'))
                        : (request()->routeIs($pattern) || match ($route) {
                        'senior-citizens.index' => request()->routeIs('senior-id.*'),
                        'payouts.index' => request()->routeIs('payout-schedules.*'),
                        'benefits.workspace' => request()->routeIs('programs.*'),
                        default => false,
                    }))
                    <a class="nav-item {{ $isActive ? 'is-active' : '' }}" href="{{ route($route) }}" title="{{ $label }}" @if($isActive) aria-current="page" @endif>
                        <x-admin.icon :name="$icon" size="18" />
                        <span>{{ $label }}</span>
                        @if ($isActive) <i class="active-dot"></i> @endif
                    </a>
                @endforeach

            </nav>
            @endif
        </aside>

        <button type="button" class="sidebar-backdrop" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false; sidebarTrigger?.focus()" aria-label="Close navigation"></button>

        <div class="profile-menu-backdrop" x-show="profileOpen" x-transition.opacity @click="profileOpen = false" style="display: none;"></div>

        <div class="admin-main">
            <header class="admin-header">
                <div class="header-title">
                    <button type="button" class="icon-button mobile-menu-button" @click="sidebarTrigger = $event.currentTarget; sidebarOpen = !sidebarOpen; if (sidebarOpen) $nextTick(() => document.querySelector('#osca-navigation a')?.focus())" aria-label="Toggle navigation" aria-controls="osca-navigation" :aria-expanded="sidebarOpen">
                        <x-admin.icon name="menu" size="20" />
                    </button>
                    <span class="eyebrow">{{ $portalLabel }}</span>
                </div>
                <div class="header-actions">
                    <span class="header-date"><x-admin.icon name="calendar-days" size="16" />{{ now()->format('l, F j, Y') }}</span>
                    <livewire:task-notifications />
                    <div class="header-account" @click.outside="profileOpen = false">
                        <button type="button" class="profile-avatar" @click="profileTrigger = $event.currentTarget; profileOpen = !profileOpen" aria-label="Open account menu" aria-controls="osca-account-panel" :aria-expanded="profileOpen"><x-profile-photo :user="$user" /></button>
                        <aside id="osca-account-panel" class="profile-menu" x-trap.inert.noscroll="profileOpen" aria-label="Account details" x-show="profileOpen" x-transition @click.stop style="display: none;">
                            <x-account-menu :user="$user" :office="$officeDetails['office_name'] ?? 'Office of the Senior Citizen Affairs'" :last-login="$lastLoginAt" />
                    </aside>
                    </div>
                </div>
            </header>

            <main id="main-content" @class(['admin-content', 'admin-content-wide' => request()->routeIs('senior-id.show', 'sms.messages.index', 'sms.blasts.show')]) tabindex="-1">
                @include('components.alert')
                @yield('content')
            </main>
        </div>
        <x-confirm-dialog />
    </div>
    @livewireScripts
</body>
</html>
