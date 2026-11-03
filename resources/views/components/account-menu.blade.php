@props(['user', 'office', 'lastLogin' => null, 'adminDashboard' => false])

<div class="account-menu-content">
    <div class="profile-menu-header">
        <x-profile-photo-upload :user="$user" />
        <div>
            <strong>{{ $user->full_name }}</strong>
            <span class="role-badge">{{ $user->role->name->label() }}</span>
            <small>{{ $user->employee_id }}</small>
        </div>
    </div>
    <div class="account-photo-controls"><x-profile-photo-actions :user="$user" /></div>
    <dl class="account-menu-details">
        <div><dt>Assigned office</dt><dd>{{ $office }}</dd></div>
        <div><dt>Last sign-in</dt><dd>{{ $lastLogin?->format('M j, Y g:i A') ?? 'Never' }}</dd></div>
        <div class="account-menu-status"><dt>Account status</dt><dd class="{{ $user->is_active ? 'is-active' : 'is-suspended' }}"><span aria-hidden="true"></span>{{ $user->is_active ? 'Active' : 'Suspended' }}</dd></div>
    </dl>
    <div class="account-menu-actions">
        @if ($adminDashboard)
            <button type="button" @click="profileOpen = false; $wire.set('profileModal', 'profile')"><x-admin.icon name="user-cog" size="18" /><span>Profile &amp; activity</span><x-admin.icon name="chevron-right" size="16" /></button>
            <button type="button" @click="profileOpen = false; $wire.set('profileModal', 'password')"><x-admin.icon name="shield-check" size="18" /><span>Change password</span><x-admin.icon name="chevron-right" size="16" /></button>
        @else
            <a href="{{ route('password.change') }}"><x-admin.icon name="shield-check" size="18" /><span>Change password</span><x-admin.icon name="chevron-right" size="16" /></a>
        @endif
        <button type="button" @click="toggleTheme()" :aria-pressed="theme === 'dark'">
            <span class="profile-action-icon"><span x-show="theme === 'light'"><x-admin.icon name="moon" size="18" /></span><span x-show="theme === 'dark'" x-cloak><x-admin.icon name="sun" size="18" /></span></span>
            <span>Dark mode</span><span class="account-theme-state" x-text="theme === 'dark' ? 'On' : 'Off'"></span>
        </button>
    </div>
    <div class="account-menu-footer">
        @if ($adminDashboard)
            <button type="button" class="profile-logout" @click="profileOpen = false; $wire.set('logoutConfirmOpen', true)"><x-admin.icon name="log-out" size="18" /><span>Log out</span></button>
        @else
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="profile-logout"><x-admin.icon name="log-out" size="18" /><span>Log out</span></button></form>
        @endif
    </div>
</div>
