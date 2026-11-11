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
    </div>
    <section class="account-appearance" aria-labelledby="account-appearance-heading">
        <h3 id="account-appearance-heading">Appearance</h3>
        <button type="button" class="account-dark-mode" role="switch" aria-checked="false" :aria-checked="theme === 'dark'" aria-labelledby="account-dark-mode-label" @click="toggleTheme()">
            <span id="account-dark-mode-label">Dark mode</span>
            <span class="account-dark-mode-control" aria-hidden="true">
                <span class="account-dark-mode-state" x-text="theme === 'dark' ? 'On' : 'Off'">Off</span>
                <span class="account-dark-mode-track"><span></span></span>
            </span>
        </button>
    </section>
    <div class="account-menu-footer">
        @if ($adminDashboard)
            <button type="button" class="profile-logout" @click="profileOpen = false; $wire.set('logoutConfirmOpen', true)"><x-admin.icon name="log-out" size="18" /><span>Log out</span></button>
        @else
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="profile-logout"><x-admin.icon name="log-out" size="18" /><span>Log out</span></button></form>
        @endif
    </div>
</div>
