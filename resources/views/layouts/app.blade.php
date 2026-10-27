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
    @php($recordsSection = request()->routeIs('registrations.*') || request('from') === 'registrations' ? 'registrations' : 'registry')
    @php($lastLoginAt = array_key_exists('last_login_at', $user->getAttributes()) ? $user->last_login_at : null)
    <div class="admin-shell staff-shell {{ $isAdmin ? '' : 'osca-workspace' }}" x-data="{
        sidebarOpen: false,
        sidebarTrigger: null,
        adminOpen: true,
        profileOpen: false,
        profileTrigger: null,
        theme: document.documentElement.classList.contains('theme-dark') ? 'dark' : 'light',
        toggleTheme() {
            this.theme = this.theme === 'light' ? 'dark' : 'light';
            try { localStorage.setItem('elingap-theme', this.theme); } catch {}
            document.documentElement.classList.toggle('theme-dark', this.theme === 'dark');
            document.documentElement.classList.toggle('theme-light', this.theme === 'light');
            document.documentElement.style.colorScheme = this.theme;
        }
    }" :class="theme === 'dark' ? 'theme-dark' : 'theme-light'" @keydown.escape.window="if (profileOpen) profileTrigger?.focus(); else if (sidebarOpen) sidebarTrigger?.focus(); profileOpen = false; sidebarOpen = false" @resize.window="sidebarOpen = false">
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
                    ['Senior Registry', 'senior-citizens.index', 'users', 'senior-citizens.*'],
                    ['Registrations', 'registrations.index', 'clipboard-list', 'registrations.*'],
                    ['Payouts', 'payouts.index', 'archive', 'payouts.*'],
                    ['Messaging', 'sms.blasts.index', 'message-square', 'sms.*'],
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

            <button class="sidebar-footer" type="button" @click="profileTrigger = $event.currentTarget; profileOpen = !profileOpen" aria-controls="osca-account-panel" :aria-expanded="profileOpen">
                <span class="profile-avatar">{{ mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1) }}</span>
                <span class="profile-copy">
                    <strong>{{ $user->first_name }} - {{ $user->role->name->label() }}</strong>
                    <small>{{ $portalLabel }}</small>
                </span>
                <span class="icon-button sidebar-logout" aria-label="Open account menu" title="Open account menu">
                    <x-admin.icon name="chevron-right" size="17" />
                </span>
            </button>
        </aside>

        <button type="button" class="sidebar-backdrop" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false; sidebarTrigger?.focus()" aria-label="Close navigation"></button>

        <div class="profile-menu-backdrop" x-show="profileOpen" x-transition.opacity @click="profileOpen = false" style="display: none;"></div>
        <aside id="osca-account-panel" class="profile-menu" x-trap.inert.noscroll="profileOpen" aria-label="Account details" x-show="profileOpen" x-transition @click.stop @click.outside="profileOpen = false" style="display: none;">
            <div class="profile-menu-header">
                <div class="profile-menu-avatar">{{ mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1) }}</div>
                <div>
                    <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>
                    <span class="role-badge">{{ $user->role->name->label() }}</span>
                    <small>{{ $user->username }}</small>
                </div>
            </div>
            <div class="profile-details">
                <div>
                    <a href="{{ route('password.change') }}" class="text-button">Change password</a>
                    <small>Assigned Office</small>
                    <strong>{{ $officeDetails['office_name'] ?? 'Office of the Senior Citizen Affairs' }}</strong>
                </div>
                <div>
                    <small>Last Active / Login</small>
                    <strong>{{ $lastLoginAt?->format('M j, Y g:i A') ?? 'Never' }}</strong>
                </div>
                <div class="account-status">
                    <small>Account Status</small>
                    <span><i></i> {{ $user->is_active ? 'Active' : 'Suspended' }}</span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="profile-logout">
                    <x-admin.icon name="log-out" size="16" />
                    <span>Log out</span>
                </button>
            </form>
        </aside>

        <div class="admin-main">
            <header class="admin-header">
                <div class="header-title">
                    <button type="button" class="icon-button mobile-menu-button" @click="sidebarTrigger = $event.currentTarget; sidebarOpen = !sidebarOpen; if (sidebarOpen) $nextTick(() => document.querySelector('#osca-navigation a')?.focus())" aria-label="Toggle navigation" aria-controls="osca-navigation" :aria-expanded="sidebarOpen">
                        <x-admin.icon name="menu" size="20" />
                    </button>
                    <div>
                        <span class="eyebrow">{{ $portalLabel }}</span>
                        <h1>{{ match (true) {
                            request()->routeIs('administration.users.*') => 'User Accounts',
                            request()->routeIs('senior-id.*') => 'Senior Citizen IDs',
                            request()->routeIs('senior-citizens.*') => request('from') === 'registrations' ? 'Registrations' : 'Senior Registry',
                            request()->routeIs('registrations.*') => 'Registrations',
                            request()->routeIs('applications.*') => 'Historical Applications',
                            request()->routeIs('payouts.*', 'payout-schedules.*') => 'Payouts',
                            request()->routeIs('sms.*') => 'Messaging',
                            request()->routeIs('reports.*') => 'Reports',
                            request()->routeIs('programs.*') => 'Benefits & Programs',
                            request()->routeIs('benefits.*') => 'Benefits Workspace',
                            default => 'Dashboard',
                        } }}</h1>
                    </div>
                </div>
                <div class="header-actions">
                    <span class="header-date"><x-admin.icon name="calendar-days" size="16" />{{ now()->format('l, F j, Y') }}</span>
                    <button type="button" class="theme-toggle icon-button" @click="toggleTheme()" :aria-label="theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'" :aria-pressed="theme === 'dark'">
                        <span x-show="theme === 'light'"><x-admin.icon name="moon" size="18" /></span>
                        <span x-show="theme === 'dark'" x-cloak><x-admin.icon name="sun" size="18" /></span>
                    </button>
                    <button type="button" class="profile-avatar" @click="profileTrigger = $event.currentTarget; profileOpen = !profileOpen" aria-label="Open account menu" aria-controls="osca-account-panel" :aria-expanded="profileOpen">{{ mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1) }}</button>
                </div>
            </header>

            <main id="main-content" class="admin-content" tabindex="-1">
                @include('components.alert')
                @if (request()->routeIs('senior-citizens.*', 'senior-id.*', 'registrations.*'))
                    <x-workspace-navigation section="records" />
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
