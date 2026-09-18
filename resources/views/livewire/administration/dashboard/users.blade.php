{{-- User Accounts Management Page --}}
<section class="page-intro">
    <div>
        <p class="section-kicker">Administration</p>
        <h2>User Accounts</h2>
        <p class="section-subtitle">Manage system users, roles, and access permissions.</p>
    </div>
    <button type="button" class="primary-button" wire:click="openModal('user')">
        <x-admin.icon name="plus" size="17" /> Add User
    </button>
</section>

<section class="metric-grid user-metrics">
    <div class="metric-card">
        <div class="metric-icon blue">
            <x-admin.icon name="users" size="19" />
        </div>
        <div class="metric-label">Total Users</div>
        <div class="metric-value">{{ $this->userCounts['total'] }}</div>
        <div class="metric-bottom">
            <span class="trend">+1</span>
            <span>registered accounts</span>
        </div>
        <div class="metric-progress blue"><i style="width: 100%;"></i></div>
    </div>

    <div class="metric-card">
        <div class="metric-icon mint">
            <x-admin.icon name="shield-check" size="19" />
        </div>
        <div class="metric-label">Active</div>
        <div class="metric-value">{{ $this->userCounts['active'] }}</div>
        <div class="metric-bottom">
            <span class="trend">+1</span>
            <span>with access</span>
        </div>
        <div class="metric-progress mint"><i style="width: 85%;"></i></div>
    </div>

    <div class="metric-card">
        <div class="metric-icon amber">
            <x-admin.icon name="alert-triangle" size="19" />
        </div>
        <div class="metric-label">Suspended</div>
        <div class="metric-value">{{ $this->userCounts['suspended'] }}</div>
        <div class="metric-bottom">
            <span class="trend">0</span>
            <span>needs review</span>
        </div>
        <div class="metric-progress amber"><i style="width: 15%;"></i></div>
    </div>

    <div class="metric-card">
        <div class="metric-icon violet">
            <x-admin.icon name="clock-3" size="19" />
        </div>
        <div class="metric-label">Inactive</div>
        <div class="metric-value">{{ $this->userCounts['inactive'] }}</div>
        <div class="metric-bottom">
            <span class="trend">0</span>
            <span>no recent access</span>
        </div>
        <div class="metric-progress violet"><i style="width: 15%;"></i></div>
    </div>
</section>

<div class="panel table-panel user-directory">
    <div class="user-toolbar">
        <label class="user-search">
            <x-admin.icon name="search" size="16" />
            <input type="text" wire:model.live="userQuery" placeholder="Search users..." aria-label="Search users" />
        </label>
        <div class="filter-pills">
            @foreach (['All', 'Active', 'Suspended', 'Inactive'] as $filter)
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
                    <th>Assigned Barangay</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->filteredUsers as $user)
                    @php
                        $initials = collect(explode(' ', $user['name']))
                            ->map(fn($part) => mb_substr($part, 0, 1))
                            ->take(2)
                            ->implode('');
                    @endphp
                    <tr wire:key="user-{{ $user['email'] }}">
                        <td>
                            <div class="user-cell">
                                <span class="user-avatar">{{ $initials }}</span>
                                <span>
                                    <strong>{{ $user['name'] }}</strong>
                                    <small>{{ $user['email'] }}</small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge">{{ $user['role'] }}</span>
                        </td>
                        <td>{{ $user['barangay'] }}</td>
                        <td>
                            <span class="status-badge {{ strtolower($user['status']) }}">
                                {{ $user['status'] }}
                            </span>
                        </td>
                        <td>{{ $user['lastLogin'] }}</td>
                        <td>{{ $user['created'] }}</td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="icon-button" title="Edit user" aria-label="Edit {{ $user['name'] }}" wire:click="openModal('user', {{ json_encode($user) }})">
                                    <x-admin.icon name="pencil" size="15" />
                                </button>
                                <button type="button" class="small-action-button" wire:click="toggleUserStatus('{{ $user['email'] }}')">
                                    {{ $user['status'] === 'Active' ? 'Suspend' : 'Activate' }}
                                </button>
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

    <div class="table-footer">
        <span>Showing {{ count($this->filteredUsers) }} of {{ count($userList) }} users</span>
        <span>{{ $this->userCounts['active'] }} active accounts have access</span>
    </div>
</div>
