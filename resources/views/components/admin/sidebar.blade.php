@props(['id' => 'admin-navigation', 'subtitle' => 'Administration'])

<aside id="{{ $id }}" class="admin-sidebar" x-trap.inert.noscroll="sidebarOpen && window.matchMedia('(max-width: 760px)').matches" :class="{ 'is-expanded': sidebarOpen }" @mouseenter="enterSidebar()" @mouseleave="leaveSidebar()" @pointerdown="sidebarKeyboardFocus = false" @focusin="focusSidebar($event)" @focusout="blurSidebar($event)">
    <div class="sidebar-brand">
        <div class="brand-mark"><img class="brand-logo" src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP Logo"></div>
        <div class="brand-copy"><strong>eLINGAP</strong><small>{{ $subtitle }}</small></div>
    </div>
    {{ $slot }}
</aside>
