@props(['active' => 'dashboard', 'dashboard' => false])

@php
    $sections = [
        'Workspace' => [
            ['dashboard', 'Dashboard', 'layout-dashboard'],
            ['records', 'Senior Records Registry', 'contact-round'],
            ['programs', 'Benefits & Programs', 'hand-heart'],
        ],
        'Administration' => [
            ['users', 'User Accounts', 'users'],
            ['system-logs', 'System Logs', 'clipboard-list'],
            ['configuration', 'System Configuration', 'settings'],
        ],
        'Support' => [
            ['help', 'Help & Support', 'circle-help'],
        ],
    ];
@endphp

<nav class="sidebar-nav" aria-label="System navigation" @click="if ($event.target.closest('a') && window.matchMedia('(max-width: 760px)').matches) { sidebarOpen = false; $nextTick(() => document.getElementById('main-content')?.focus()); }">
    @foreach ($sections as $section => $links)
        <section class="admin-navigation-group" wire:key="admin-nav-group-{{ $loop->index }}" aria-labelledby="admin-nav-label-{{ $loop->index }}">
            <p id="admin-nav-label-{{ $loop->index }}" @class(['nav-label', 'nav-label-lower' => ! $loop->first])>{{ $section }}</p>
            @foreach ($links as [$tab, $label, $icon])
                <a wire:key="admin-nav-{{ $tab }}"
                    @class(['nav-item', 'is-active' => $active === $tab])
                    href="{{ route('administration.dashboard', ['tab' => $tab]) }}"
                    title="{{ $label }}"
                    @if ($dashboard) wire:click.prevent="navigate('{{ $tab }}')" @endif
                    @if ($active === $tab) aria-current="page" @endif>
                    <x-admin.icon :name="$icon" size="18" />
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </section>
    @endforeach
</nav>
