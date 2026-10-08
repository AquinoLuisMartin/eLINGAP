<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'eLINGAP — OSCA Portal' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/eLINGAP.png') }}">
    <script>
        (function () {
            var theme = localStorage.getItem('elingap-theme') === 'dark' ? 'dark' : 'light';
            document.documentElement.classList.add(theme === 'dark' ? 'theme-dark' : 'theme-light');
            document.documentElement.style.colorScheme = theme;
        })();
    </script>
    @vite(['resources/css/admin.css', 'resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    @php($user = auth()->user()->loadMissing('role'))
    @php($lastLoginAt = array_key_exists('last_login_at', $user->getAttributes()) ? $user->last_login_at : null)
    <div class="admin-shell staff-shell" x-data="{
        sidebarOpen: false,
        profileOpen: false,
        theme: localStorage.getItem('elingap-theme') === 'dark' ? 'dark' : 'light',
        toggleTheme() {
            this.theme = this.theme === 'light' ? 'dark' : 'light';
            localStorage.setItem('elingap-theme', this.theme);
            document.documentElement.classList.toggle('theme-dark', this.theme === 'dark');
            document.documentElement.classList.toggle('theme-light', this.theme === 'light');
            document.documentElement.style.colorScheme = this.theme;
        }
    }" :class="theme === 'dark' ? 'theme-dark' : 'theme-light'" @keydown.escape.window="profileOpen = false; sidebarOpen = false">
        <aside id="osca-navigation" class="admin-sidebar" :class="{ 'is-expanded': sidebarOpen }" @mouseenter="if (window.matchMedia('(min-width: 761px)').matches) sidebarOpen = true" @mouseleave="if (window.matchMedia('(min-width: 761px)').matches) sidebarOpen = false" @focusin="sidebarOpen = true" @focusout="if (!$el.contains($event.relatedTarget)) sidebarOpen = false">
            <div class="sidebar-brand">
                <div class="brand-mark">
                    <img class="brand-logo" src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP Logo">
                </div>
                <div class="brand-copy">
                    <strong>eLINGAP</strong>
                    <small>OSCA Santa Maria</small>
                </div>
            </div>

            <nav class="sidebar-nav" aria-label="OSCA Staff navigation">
                <p class="nav-label">Workspace</p>
                @foreach ([
                    ['Dashboard', 'dashboard', 'layout-dashboard', 'dashboard'],
                    ['Senior Records Registry', 'senior-citizens.index', 'users', 'senior-citizens.*'],
                    ['Registrations', 'applications.index', 'clipboard-list', 'applications.*'],
                    ['Payouts', 'payouts.index', 'archive', 'payouts.*'],
                    ['Messaging', 'sms.blasts.index', 'message-square', 'sms.*'],
                    ['Reports', 'reports.index', 'bar-chart-3', 'reports.*'],
                ] as [$label, $route, $icon, $pattern])
                    <a class="nav-item {{ request()->routeIs($pattern) ? 'is-active' : '' }}" href="{{ route($route) }}" title="{{ $label }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>
                        <x-admin.icon :name="$icon" size="18" />
                        <span>{{ $label }}</span>
                        @if (request()->routeIs($pattern)) <i class="active-dot"></i> @endif
                    </a>
                @endforeach

            </nav>

            <button class="sidebar-footer" type="button" aria-expanded="false" aria-haspopup="menu" @click="profileOpen = !profileOpen" :aria-expanded="profileOpen">
                <div class="profile-avatar">{{ mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1) }}</div>
                <div class="profile-copy">
                    <strong>{{ $user->first_name }} - {{ $user->role->name->label() }}</strong>
                    <small>OSCA Staff Portal</small>
                </div>
                <span class="icon-button sidebar-logout" aria-label="Open account menu" title="Open account menu">
                    <x-admin.icon name="chevron-right" size="17" />
                </span>
            </button>
        </aside>

        <div class="profile-menu-backdrop" x-show="profileOpen" x-transition.opacity @click="profileOpen = false" style="display: none;"></div>
        <aside class="profile-menu" role="menu" x-show="profileOpen" x-transition @click.stop style="display: none;">
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
                    <small>Assigned Office</small>
                    <strong>OSCA Santa Maria Municipal Hall</strong>
                </div>
                <div>
                    <small>Last Active / Login</small>
                    <strong>{{ $lastLoginAt?->format('M j, Y g:i A') ?? 'Never' }}</strong>
                </div>
                <div class="account-status">
                    <small>Account Status</small>
                    <span><i></i> Active</span>
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
                    <button type="button" class="icon-button mobile-menu-button" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle navigation" aria-controls="osca-navigation" :aria-expanded="sidebarOpen">
                        <x-admin.icon name="menu" size="20" />
                    </button>
                    <div>
                        <span class="eyebrow">OSCA Staff Portal</span>
                        <h1>{{ match (true) {
                            request()->routeIs('senior-citizens.*') => 'Senior Records Registry',
                            request()->routeIs('applications.*') => 'Registrations',
                            request()->routeIs('payouts.*') => 'Payouts',
                            request()->routeIs('sms.*') => 'Messaging',
                            request()->routeIs('reports.*') => 'Reports',
                            request()->routeIs('programs.*') => 'Benefits & Programs',
                            default => 'Dashboard',
                        } }}</h1>
                    </div>
                </div>
                <div class="header-actions">
                    <span class="header-date"><x-admin.icon name="calendar-days" size="16" />{{ now()->format('l, F j, Y') }}</span>
                    <span class="online-status"><i></i>Online</span>
                    <button type="button" class="theme-toggle icon-button" @click="toggleTheme()" :aria-label="theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'">
                        <span x-show="theme === 'light'"><x-admin.icon name="moon" size="18" /></span>
                        <span x-show="theme === 'dark'" x-cloak><x-admin.icon name="sun" size="18" /></span>
                    </button>
                    <button type="button" class="profile-avatar" @click="profileOpen = !profileOpen" aria-label="Open account menu" aria-haspopup="menu" :aria-expanded="profileOpen">{{ mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1) }}</button>
                </div>
            </header>

            <main class="admin-content">
                @include('components.alert')
                @yield('content')
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
