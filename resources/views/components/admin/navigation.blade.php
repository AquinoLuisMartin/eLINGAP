@props(['active' => 'dashboard', 'dashboard' => false])

<nav class="sidebar-nav" aria-label="System navigation" @click="if ($event.target.closest('a') && window.matchMedia('(max-width: 760px)').matches) { sidebarOpen = false; $nextTick(() => document.getElementById('main-content')?.focus()); }">
    <p class="nav-label">Workspace</p>
    @foreach ([
        ['dashboard', 'Dashboard', 'layout-dashboard'],
        ['records', 'Senior Records Registry', 'users'],
        ['programs', 'Benefits & Programs', 'heart-pulse'],
        ['sms', 'SMS Dispatcher', 'message-square'],
    ] as [$tab, $label, $icon])
        <a wire:key="admin-nav-{{ $tab }}" class="nav-item {{ $active === $tab ? 'is-active' : '' }}" href="{{ route('administration.dashboard', ['tab' => $tab]) }}" title="{{ $label }}" @if($dashboard) wire:click.prevent="navigate('{{ $tab }}')" @endif @if($active === $tab) aria-current="page" @endif>
            <x-admin.icon :name="$icon" size="18" /><span>{{ $label }}</span>
            @if ($active === $tab) <i class="active-dot"></i> @endif
        </a>
    @endforeach

    <button type="button" class="nav-item admin-toggle" @click="adminOpen = !adminOpen" aria-controls="admin-subnavigation" :aria-expanded="adminOpen" title="Administration">
        <x-admin.icon name="settings" size="18" /><span>Administration</span>
        <x-admin.icon name="chevron-down" size="15" ::class="adminOpen ? '' : 'rotate-[-90deg]'" />
    </button>
    <div id="admin-subnavigation" class="admin-subnav is-open" x-show="adminOpen">
        @foreach ([
            ['users', 'User Accounts', 'user-cog'],
            ['system-logs', 'System Logs', 'clipboard-list'],
            ['configuration', 'System Configuration', 'settings'],
        ] as [$tab, $label, $icon])
            <a wire:key="admin-nav-{{ $tab }}" class="nav-item nav-subitem {{ $active === $tab ? 'is-active' : '' }}" href="{{ route('administration.dashboard', ['tab' => $tab]) }}" title="{{ $label }}" @if($dashboard) wire:click.prevent="navigate('{{ $tab }}')" @endif @if($active === $tab) aria-current="page" @endif>
                <x-admin.icon :name="$icon" size="18" /><span>{{ $label }}</span>
                @if ($active === $tab) <i class="active-dot"></i> @endif
            </a>
        @endforeach
    </div>

    <p class="nav-label nav-label-lower">Support</p>
    <a class="nav-item {{ $active === 'help' ? 'is-active' : '' }}" href="{{ route('administration.dashboard', ['tab' => 'help']) }}" title="Help & Support" @if($dashboard) wire:click.prevent="navigate('help')" @endif @if($active === 'help') aria-current="page" @endif>
        <x-admin.icon name="circle-help" size="18" /><span>Help &amp; Support</span>
        @if ($active === 'help') <i class="active-dot"></i> @endif
    </a>
</nav>
