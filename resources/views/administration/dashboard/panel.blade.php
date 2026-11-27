@php
    $pages = [
        'dashboard' => ['Dashboard', 'layout-dashboard'],
        'records' => ['Senior Records Registry', 'contact-round'],
        'programs' => ['Benefits & Programs', 'hand-heart'],
        'users' => ['User Accounts', 'users'],
        'system-logs' => ['System Logs', 'clipboard-list'],
        'configuration' => ['System Configuration', 'settings'],
        'help' => ['Help & Support', 'circle-help'],
    ];
    [$currentPageTitle, $currentPageIcon] = $pages[$active] ?? $pages['dashboard'];
    $headerDate = now();
@endphp

<div class="admin-shell admin-workspace"
    :class="theme === 'dark' ? 'theme-dark' : 'theme-light'"
    x-data="{
        profileOpen: @js($errors->has('photo')),
        profileTrigger: null,
        ...workspaceSidebar(),
        ...workspaceTheme(),
    }"
    x-init="initSidebar()" @mousemove.window="trackSidebarPointer($event)" @storage.window="syncTheme($event)" @keydown.escape.window="if (profileOpen) profileTrigger?.focus(); else if (sidebarOpen) sidebarTrigger?.focus(); profileOpen = false; sidebarOpen = false" @resize.window="syncSidebarViewport()">

    {{-- Collapsible Sidebar --}}
    <x-admin.sidebar :subtitle="$this->configuration->firstWhere('key', 'organization_name')?->value ?? 'Administration'">
        <x-admin.navigation :active="$active" :dashboard="true" />
    </x-admin.sidebar>

    <button type="button" class="sidebar-backdrop" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false; sidebarTrigger?.focus()" aria-label="Close navigation"></button>

    <div class="profile-menu-backdrop" x-show="profileOpen" @click="profileOpen = false" style="display: none;"></div>

    {{-- Main Workspace --}}
    <div class="admin-main">
        <header class="admin-header">
            <div class="header-title">
                <button type="button" class="mobile-menu-button icon-button" @click="sidebarTrigger = $event.currentTarget; sidebarOpen = !sidebarOpen; if (sidebarOpen) $nextTick(() => document.querySelector('#admin-navigation a')?.focus())" aria-label="Toggle navigation" aria-controls="admin-navigation" :aria-expanded="sidebarOpen">
                    <x-admin.icon name="menu" size="20" />
                </button>
                <span class="admin-page-icon"><x-admin.icon :name="$currentPageIcon" size="20" /></span>
                <div>
                    <span class="eyebrow">System Administration</span>
                    <h1>{{ $currentPageTitle }}</h1>
                </div>
            </div>

            <div class="header-actions">
                <div class="header-date">
                    <x-admin.icon name="calendar-days" size="16" />
                    <time datetime="{{ $headerDate->toDateString() }}">{{ $headerDate->format('l, F j, Y') }}</time>
                </div>

                <livewire:task-notifications />

                <div class="header-account" @click.outside="profileOpen = false">
                    <button type="button" class="profile-avatar" @click="profileTrigger = $event.currentTarget; profileOpen = !profileOpen" aria-label="Open account menu" aria-controls="admin-account-panel" :aria-expanded="profileOpen"><x-profile-photo :user="$this->currentUser" /></button>
                    <aside id="admin-account-panel" class="profile-menu" x-trap.inert.noscroll="profileOpen" aria-label="Account details" x-show="profileOpen" @click.stop style="display: none;">
                        <x-account-menu :user="$this->currentUser" :office="$this->configuration->firstWhere('key', 'office_name')?->value ?? 'Not configured'" :last-login="$this->currentUser->last_login_at" :admin-dashboard="true" />
                    </aside>
                </div>
            </div>
        </header>

        <main id="main-content" @class(['admin-content', 'admin-content-configuration' => $active === 'configuration']) tabindex="-1">
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

    {{-- Toast Notification --}}
    @if ($toast)
        <div class="toast" role="status" x-data="{ show: true }" x-init="setTimeout(() => { show = false; $wire.dismissToast(); }, 3000)" x-show="show" x-transition>
            <x-admin.icon name="check" size="16" /> {{ $toast }}
        </div>
    @endif
</div>
