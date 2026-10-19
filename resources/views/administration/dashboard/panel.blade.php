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
        sidebarTrigger: null,
        adminOpen: true,
        profileOpen: false,
        profileTrigger: null,
        notificationsOpen: false,
        theme: document.documentElement.classList.contains('theme-dark') ? 'dark' : 'light',
        init() {
            this.applyTheme(this.theme);
        },
        applyTheme(value) {
            this.theme = value === 'dark' ? 'dark' : 'light';
            try { localStorage.setItem('elingap-theme', this.theme); } catch {}
            document.documentElement.classList.toggle('theme-dark', this.theme === 'dark');
            document.documentElement.classList.toggle('theme-light', this.theme === 'light');
            document.documentElement.style.colorScheme = this.theme;
            document.body.classList.toggle('theme-dark', this.theme === 'dark');
        },
        toggleTheme() {
            this.applyTheme(this.theme === 'light' ? 'dark' : 'light');
        }
    }"
    @keydown.escape.window="if (profileOpen) profileTrigger?.focus(); else if (sidebarOpen) sidebarTrigger?.focus(); profileOpen = false; notificationsOpen = false; sidebarOpen = false" @resize.window="sidebarOpen = false">

    {{-- Collapsible Sidebar --}}
    <aside id="admin-navigation" class="admin-sidebar" x-trap.inert.noscroll="sidebarOpen && window.matchMedia('(max-width: 760px)').matches" :class="{ 'is-expanded': sidebarOpen }" @mouseenter="if (window.matchMedia('(min-width: 761px)').matches) sidebarOpen = true" @mouseleave="if (window.matchMedia('(min-width: 761px)').matches && !$el.contains(document.activeElement)) sidebarOpen = false" @focusin="sidebarOpen = true" @focusout="if (window.matchMedia('(min-width: 761px)').matches && !$el.contains($event.relatedTarget) && !$el.matches(':hover')) sidebarOpen = false">
        <div class="sidebar-brand">
            <div class="brand-mark">
                <img class="brand-logo" src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP Logo" />
            </div>
            <div class="brand-copy">
                <strong>eLINGAP</strong>
                <small>{{ $this->configuration->firstWhere('key', 'organization_name')?->value ?? 'Administration' }}</small>
            </div>
        </div>

        <x-admin.navigation :active="$active" :dashboard="true" />

        {{-- Sidebar Footer Profile Toggle --}}
        <button class="sidebar-footer" type="button" aria-controls="admin-account-panel" :aria-expanded="profileOpen" @click="profileTrigger = $event.currentTarget; profileOpen = !profileOpen">
            <div class="profile-avatar">{{ mb_substr($this->currentUser->first_name, 0, 1).mb_substr($this->currentUser->last_name, 0, 1) }}</div>
            <div class="profile-copy">
                <strong>{{ $this->currentUser->full_name }}</strong>
                <small>{{ $this->currentUser->role->name->label() }}</small>
            </div>
            <span class="icon-button sidebar-logout" aria-label="Open account menu" title="Open account menu">
                <x-admin.icon name="chevron-right" size="17" />
            </span>
        </button>
    </aside>

    <button type="button" class="sidebar-backdrop" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false; sidebarTrigger?.focus()" aria-label="Close navigation"></button>

    {{-- Profile Flyout Menu --}}
    <div class="profile-menu-backdrop" x-show="profileOpen" @click="profileOpen = false" style="display: none;"></div>
    <aside id="admin-account-panel" class="profile-menu" x-trap.inert.noscroll="profileOpen" aria-label="Account details" x-show="profileOpen" @click.stop @click.outside="profileOpen = false" style="display: none;">
        <div class="profile-menu-header">
            <div class="profile-menu-avatar">{{ mb_substr($this->currentUser->first_name, 0, 1).mb_substr($this->currentUser->last_name, 0, 1) }}</div>
            <div>
                <strong>{{ $this->currentUser->full_name }}</strong>
                <span class="role-badge">{{ $this->currentUser->role->name->label() }}</span>
                <small>{{ $this->currentUser->email }}</small>
            </div>
        </div>
        <div class="profile-details">
            <div>
                <small>Assigned Office</small>
                <strong>{{ $this->configuration->firstWhere('key', 'office_name')?->value ?? 'Not configured' }}</strong>
            </div>
            <div>
                <small>Last Active / Login</small>
                <strong>{{ $this->currentUser->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</strong>
            </div>
            <div class="account-status">
                <small>Account Status</small>
                <span><i></i> {{ $this->currentUser->is_active ? 'Active' : 'Suspended' }}</span>
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
    <div class="admin-main">
        <header class="admin-header">
            <div class="header-title">
                <button type="button" class="mobile-menu-button icon-button" @click="sidebarTrigger = $event.currentTarget; sidebarOpen = !sidebarOpen; if (sidebarOpen) $nextTick(() => document.querySelector('#admin-navigation a')?.focus())" aria-label="Toggle navigation" aria-controls="admin-navigation" :aria-expanded="sidebarOpen">
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
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search records, programs, or events..." aria-label="Global search" />
                </label>

                <div class="header-date">
                    <x-admin.icon name="calendar-days" size="16" />
                    <span>{{ now()->format('l, F j, Y') }}</span>
                </div>



                <div class="notification-wrap">
                    <button type="button" class="notification-button icon-button" aria-label="Notifications" @click="notificationsOpen = true">
                        <x-admin.icon name="bell" size="19" />
                        @if ($this->summary['pending'] + $this->summary['sms_failed'] > 0)
                            <b>{{ number_format($this->summary['pending'] + $this->summary['sms_failed']) }}</b>
                        @endif
                    </button>
                </div>

                <button type="button" class="theme-toggle icon-button" @click="toggleTheme()" :aria-label="theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'" :aria-pressed="theme === 'dark'" :title="theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'">
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

        <main id="main-content" class="admin-content" tabindex="-1">
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
        </main>
    </div>

    {{-- Overlays and Dialogs --}}
    @include('administration.dashboard.modals')
    @include('administration.dashboard.drawer')

    {{-- Toast Notification --}}
    @if ($toast)
        <div class="toast" role="status" x-data="{ show: true }" x-init="setTimeout(() => { show = false; $wire.dismissToast(); }, 3000)" x-show="show" x-transition>
            <x-admin.icon name="check" size="16" /> {{ $toast }}
        </div>
    @endif
</div>
