{{-- User Accounts Management Page --}}
<section class="page-intro">
    <div>
        <p class="section-kicker">Administration</p>
        <h2>User Accounts</h2>
        <p class="section-subtitle">Manage system users, roles, and access permissions.</p>
    </div>
    <a class="primary-button" href="{{ route('administration.users.create') }}">
        <x-admin.icon name="plus" size="17" /> Add User
    </a>
</section>

<section class="metric-grid user-metrics">
    <div class="metric-card">
        <div class="metric-icon blue">
            <x-admin.icon name="users" size="19" />
        </div>
        <div class="metric-label">Total Users</div>
        <div class="metric-value">{{ $this->userCounts['total'] }}</div>
        <div class="metric-bottom">
            <span>registered accounts</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon mint">
            <x-admin.icon name="shield-check" size="19" />
        </div>
        <div class="metric-label">Active</div>
        <div class="metric-value">{{ $this->userCounts['active'] }}</div>
        <div class="metric-bottom">
            <span>with access</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon amber">
            <x-admin.icon name="alert-triangle" size="19" />
        </div>
        <div class="metric-label">Suspended</div>
        <div class="metric-value">{{ $this->userCounts['suspended'] }}</div>
        <div class="metric-bottom">
            <span>needs review</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon violet">
            <x-admin.icon name="clock-3" size="19" />
        </div>
        <div class="metric-label">Administrators</div>
        <div class="metric-value">{{ $this->userCounts['administrators'] }}</div>
        <div class="metric-bottom">
            <span>administrator accounts</span>
        </div>
    </div>
</section>

<div class="panel table-panel user-directory">
    <div class="user-toolbar">
        <label class="user-search">
            <x-admin.icon name="search" size="16" />
            <input type="text" wire:model.live="userQuery" placeholder="Search users..." aria-label="Search users" />
        </label>
        <div class="filter-pills">
            @foreach (['All', 'Active', 'Suspended'] as $filter)
                <button type="button" class="{{ $userStatusFilter === $filter ? 'selected' : '' }}" wire:click="$set('userStatusFilter', '{{ $filter }}')">
                    {{ $filter }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->filteredUsers as $user)
                    @php
                        $initials = collect(explode(' ', $user->full_name))
                            ->map(fn($part) => mb_substr($part, 0, 1))
                            ->take(2)
                            ->implode('');
                    @endphp
                    <tr wire:key="user-{{ $user->id }}">
                        <td>
                            <div class="user-cell">
                                <span class="user-avatar">{{ $initials }}</span>
                                <span>
                                    <strong>{{ $user->full_name }}</strong>
                                    <small>{{ $user->email }}</small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge">{{ $user->role->name->label() }}</span>
                        </td>
                        <td>{{ $user->username }}</td>
                        <td>
                            <span class="status-badge {{ $user->is_active ? 'active' : 'suspended' }}">
                                {{ $user->is_active ? 'Active' : 'Suspended' }}
                            </span>
                        </td>
                        <td>{{ $user->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</td>
                        <td>{{ $user->created_at->format('M j, Y') }}</td>
                        <td>
                            <div class="row-actions">
                                <a class="icon-button" title="Edit user" aria-label="Edit {{ $user->full_name }}" href="{{ route('administration.users.edit', $user) }}">
                                    <x-admin.icon name="pencil" size="15" />
                                </a>
                                @can('manageAccess', $user)
                                    <button type="button" class="small-action-button" wire:click="toggleUserStatus({{ $user->id }})">
                                        {{ $user->is_active ? 'Suspend' : 'Activate' }}
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--muted);">
                            No users match the selected criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->filteredUsers->links() }}

    <div class="table-footer">
        <span>Showing {{ count($this->filteredUsers) }} of {{ $this->filteredUsers->total() }} users</span>
        <span>{{ $this->userCounts['active'] }} active accounts have access</span>
    </div>
</div>
