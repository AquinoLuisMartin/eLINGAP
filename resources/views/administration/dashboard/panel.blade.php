@php
    $pageTitles = [
        'dashboard' => 'Dashboard',
        'records' => 'Senior Records Registry',
        'programs' => 'Benefits & Programs',
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
        profileOpen: @js($errors->has('photo')),
        profileTrigger: null,
        notificationsOpen: false,
        ...workspaceTheme(),
    }"
    @storage.window="syncTheme($event)" @keydown.escape.window="if (profileOpen) profileTrigger?.focus(); else if (sidebarOpen) sidebarTrigger?.focus(); profileOpen = false; notificationsOpen = false; sidebarOpen = false" @resize.window="sidebarOpen = false">

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

    </aside>

    <button type="button" class="sidebar-backdrop" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false; sidebarTrigger?.focus()" aria-label="Close navigation"></button>

    <div class="profile-menu-backdrop" x-show="profileOpen" @click="profileOpen = false" style="display: none;"></div>

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
                <div class="header-date">
                    <x-admin.icon name="calendar-days" size="16" />
                    <span>{{ now()->format('l, F j, Y') }}</span>
                </div>



                <div class="notification-wrap">
                    <button type="button" class="notification-button icon-button" aria-label="Notifications" @click="notificationsOpen = true">
                        <x-admin.icon name="bell" size="19" />
                        @if ($this->summary['pending'] > 0)
                            <b>{{ number_format($this->summary['pending']) }}</b>
                        @endif
                    </button>
                </div>

                <x-theme-toggle />
                <div class="header-account" @click.outside="profileOpen = false">
                    <button type="button" class="profile-avatar" @click="profileTrigger = $event.currentTarget; profileOpen = !profileOpen" aria-label="Open account menu" aria-controls="admin-account-panel" :aria-expanded="profileOpen"><x-profile-photo :user="$this->currentUser" /></button>
                    <aside id="admin-account-panel" class="profile-menu" x-trap.inert.noscroll="profileOpen" aria-label="Account details" x-show="profileOpen" @click.stop style="display: none;">
                        <x-account-menu :user="$this->currentUser" :office="$this->configuration->firstWhere('key', 'office_name')?->value ?? 'Not configured'" :last-login="$this->currentUser->last_login_at" :admin-dashboard="true" />
                    </aside>
                </div>
            </div>
        </header>

        <main id="main-content" class="admin-content" tabindex="-1">
            @if ($active === 'records')
                @include('administration.dashboard.records')
            @elseif ($active === 'programs')
                @include('administration.dashboard.programs')
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
