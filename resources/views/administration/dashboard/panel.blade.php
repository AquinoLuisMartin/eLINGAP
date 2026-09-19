@php
    $pageTitles = [
        'dashboard' => 'Dashboard',
        'records' => 'Senior Records Registry',
        'programs' => 'Benefits & Programs',
        'sms' => 'SMS Dispatcher',
        'users' => 'User Accounts',
        'system-logs' => 'System Logs',
        'configuration' => 'System Configuration',
        'help' => 'Help & Support',
    ];
    $currentPageTitle = $pageTitles[$active] ?? 'Dashboard';
@endphp

<div class="admin-shell"
    :class="theme === 'dark' ? 'theme-dark' : 'theme-light'"
    x-data="{
        sidebarOpen: false,
        adminOpen: true,
        profileOpen: false,
        notificationsOpen: false,
        theme: localStorage.getItem('elingap-theme') === 'dark' ? 'dark' : 'light',
        init() {
            this.applyTheme(this.theme);
        },
        applyTheme(value) {
            this.theme = value === 'dark' ? 'dark' : 'light';
            localStorage.setItem('elingap-theme', this.theme);
            document.documentElement.classList.toggle('theme-dark', this.theme === 'dark');
            document.documentElement.classList.toggle('theme-light', this.theme === 'light');
            document.documentElement.style.colorScheme = this.theme;
            document.body.classList.toggle('theme-dark', this.theme === 'dark');
        },
        toggleTheme() {
            this.applyTheme(this.theme === 'light' ? 'dark' : 'light');
        }
    }"
    @keydown.escape.window="profileOpen = false; notificationsOpen = false">

    {{-- Collapsible Sidebar --}}
    <aside class="admin-sidebar" :class="{ 'is-expanded': sidebarOpen }" @mouseenter="sidebarOpen = true" @mouseleave="sidebarOpen = false">
        <div class="sidebar-brand">
            <div class="brand-mark">
                <img class="brand-logo" src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP Logo" />
            </div>
            <div class="brand-copy">
                <strong>eLINGAP</strong>
                <small>OSCA Santa Maria</small>
            </div>
        </div>

        <nav class="sidebar-nav" aria-label="System navigation">
            <p class="nav-label">Workspace</p>

            <button type="button" class="nav-item {{ $active === 'dashboard' ? 'is-active' : '' }}" wire:click="navigate('dashboard')">
                <x-admin.icon name="layout-dashboard" size="18" />
                <span>Dashboard</span>
                @if ($active === 'dashboard') <i class="active-dot"></i> @endif
            </button>

            <button type="button" class="nav-item {{ $active === 'records' ? 'is-active' : '' }}" wire:click="navigate('records')">
                <x-admin.icon name="users" size="18" />
                <span>Senior Records Registry</span>
                @if ($active === 'records') <i class="active-dot"></i> @endif
            </button>

            <button type="button" class="nav-item {{ $active === 'programs' ? 'is-active' : '' }}" wire:click="navigate('programs')">
                <x-admin.icon name="heart-pulse" size="18" />
                <span>Benefits &amp; Programs</span>
                @if ($active === 'programs') <i class="active-dot"></i> @endif
            </button>

            <button type="button" class="nav-item {{ $active === 'sms' ? 'is-active' : '' }}" wire:click="navigate('sms')">
                <x-admin.icon name="message-square" size="18" />
                <span>SMS Dispatcher</span>
                @if ($active === 'sms') <i class="active-dot"></i> @endif
            </button>

            {{-- Administration section --}}
            <button type="button" class="nav-item admin-toggle" @click="adminOpen = !adminOpen">
                <x-admin.icon name="settings" size="18" />
                <span>Administration</span>
                <x-admin.icon name="chevron-down" size="15" ::class="adminOpen ? '' : 'rotate-[-90deg]'" />
            </button>

            <div class="admin-subnav" :class="{ 'is-open': adminOpen }">
                <button type="button" class="nav-item nav-subitem {{ $active === 'users' ? 'is-active' : '' }}" wire:click="navigate('users')">
                    <x-admin.icon name="user-cog" size="18" />
                    <span>User Accounts</span>
                    @if ($active === 'users') <i class="active-dot"></i> @endif
                </button>

                <button type="button" class="nav-item nav-subitem {{ $active === 'system-logs' ? 'is-active' : '' }}" wire:click="navigate('system-logs')">
                    <x-admin.icon name="clipboard-list" size="18" />
                    <span>System Logs</span>
                    @if ($active === 'system-logs') <i class="active-dot"></i> @endif
                </button>

                <button type="button" class="nav-item nav-subitem {{ $active === 'configuration' ? 'is-active' : '' }}" wire:click="navigate('configuration')">
                    <x-admin.icon name="settings" size="18" />
                    <span>System Configuration</span>
                    @if ($active === 'configuration') <i class="active-dot"></i> @endif
                </button>
            </div>

            <p class="nav-label nav-label-lower">Support</p>
            <button type="button" class="nav-item {{ $active === 'help' ? 'is-active' : '' }}" wire:click="navigate('help')">
                <x-admin.icon name="circle-help" size="18" />
                <span>Help &amp; Support</span>
                @if ($active === 'help') <i class="active-dot"></i> @endif
            </button>
        </nav>

        {{-- Sidebar Footer Profile Toggle --}}
        <button class="sidebar-footer" type="button" aria-expanded="false" aria-haspopup="menu" @click="profileOpen = !profileOpen">
            <div class="profile-avatar">MA</div>
            <div class="profile-copy">
                <strong>Maria A.</strong>
                <small>System Administrator</small>
            </div>
            <span class="icon-button sidebar-logout" aria-label="Open account menu" title="Open account menu">
                <x-admin.icon name="chevron-right" size="17" />
            </span>
        </button>
    </aside>

    {{-- Profile Flyout Menu --}}
    <div class="profile-menu-backdrop" x-show="profileOpen" @click="profileOpen = false" style="display: none;"></div>
    <aside class="profile-menu" role="menu" x-show="profileOpen" @click.stop style="display: none;">
        <div class="profile-menu-header">
            <div class="profile-menu-avatar">MA</div>
            <div>
                <strong>Maria A.</strong>
                <span class="role-badge">System Administrator</span>
                <small>admin@elingap.gov.ph</small>
            </div>
        </div>
        <div class="profile-details">
            <div>
                <small>Assigned Office</small>
                <strong>OSCA Santa Maria Municipal Hall</strong>
            </div>
            <div>
                <small>Last Active / Login</small>
                <strong>Sep 14, 2024 · 07:48 AM</strong>
            </div>
            <div class="account-status">
                <small>Account Status</small>
                <span><i></i> Active</span>
            </div>
        </div>
        <div class="profile-actions">
            <button type="button" @click="profileOpen = false; $wire.set('profileModal', 'profile')">
                <x-admin.icon name="user-cog" size="16" />
                <span>View Full Profile &amp; Activity</span>
                <x-admin.icon name="chevron-right" size="15" />
            </button>
            <button type="button" @click="profileOpen = false; $wire.set('profileModal', 'password')">
                <x-admin.icon name="shield-check" size="16" />
                <span>Change Password</span>
                <x-admin.icon name="chevron-right" size="15" />
            </button>
            <button type="button" @click="toggleTheme()">
                <span class="profile-action-icon">
                    <span x-show="theme === 'light'">
                        <x-admin.icon name="moon" size="16" />
                    </span>
                    <span x-show="theme === 'dark'" x-cloak>
                        <x-admin.icon name="sun" size="16" />
                    </span>
                </span>
                <span>System Preferences</span>
                <small x-text="theme === 'light' ? 'Light mode' : 'Dark mode'"></small>
            </button>
        </div>
        <button type="button" class="profile-logout" @click="profileOpen = false; $wire.set('logoutConfirmOpen', true)">
            <x-admin.icon name="log-out" size="16" /> Log Out
        </button>
    </aside>

    {{-- Main Workspace --}}
    <main class="admin-main">
        <header class="admin-header">
            <div class="header-title">
                <button type="button" class="mobile-menu-button icon-button" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle navigation">
                    <x-admin.icon name="menu" size="20" />
                </button>
                <div>
                    <span class="eyebrow">System Administration</span>
                    <h1>{{ $currentPageTitle }}</h1>
                </div>
            </div>

            <div class="header-actions">
                <label class="global-search">
                    <x-admin.icon name="search" size="17" />
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name, OSCA ID, or barangay..." aria-label="Global search" />
                </label>

                <div class="header-date">
                    <x-admin.icon name="calendar-days" size="16" />
                    <span>{{ now()->format('l, F j, Y') }}</span>
                </div>

                <span class="online-status"><i></i> System Online</span>

                <div class="notification-wrap">
                    <button type="button" class="notification-button icon-button" aria-label="Notifications" @click="notificationsOpen = true; $wire.clearNotices()">
                        <x-admin.icon name="bell" size="19" />
                        @if ($noticeCount > 0)
                            <b>{{ $noticeCount }}</b>
                        @endif
                    </button>
                </div>

                <button type="button" class="theme-toggle icon-button" @click="toggleTheme()" :aria-label="theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'" :title="theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'">
                    <span x-show="theme === 'light'">
                        <x-admin.icon name="moon" size="18" />
                    </span>
                    <span x-show="theme === 'dark'" x-cloak>
                        <x-admin.icon name="sun" size="18" />
                    </span>
                    <span class="theme-toggle-label" x-text="theme === 'light' ? 'Dark' : 'Light'"></span>
                </button>
            </div>
        </header>

        <div class="admin-content">
            @if ($active === 'records')
                @include('administration.dashboard.records')
            @elseif ($active === 'programs')
                @include('administration.dashboard.programs')
            @elseif ($active === 'sms')
                @include('administration.dashboard.sms')
            @elseif ($active === 'users')
                @include('administration.dashboard.users')
            @elseif ($active === 'system-logs')
                @include('administration.dashboard.logs')
            @elseif ($active === 'configuration')
                @include('administration.dashboard.configuration')
            @elseif ($active === 'help')
                @include('administration.dashboard.help')
            @else
                @include('administration.dashboard.overview')
            @endif
        </div>
    </main>

    {{-- Overlays and Dialogs --}}
    @include('administration.dashboard.modals')
    @include('administration.dashboard.drawer')

    {{-- Toast Notification --}}
    @if ($toast)
        <div class="toast" x-data="{ show: true }" x-init="setTimeout(() => { show = false; $wire.dismissToast(); }, 3000)" x-show="show" x-transition>
            <x-admin.icon name="check" size="16" /> {{ $toast }}
        </div>
    @endif
</div>
