@php
    $accounts = $this->userCounts;
    $users = $this->filteredUsers;
@endphp

<x-staff.page-header title="User accounts">
    <a class="secondary-button gap-2" href="{{ route('administration.password-reset-requests.index') }}">
        <x-admin.icon name="key-round" size="16" />
        Password reset requests <span class="text-[var(--muted)]">({{ number_format($this->pendingRecoveryRequests) }} pending)</span>
    </a>
    <a class="primary-button gap-2" href="{{ route('administration.users.create') }}">
        <x-admin.icon name="user-round-plus" size="18" /> Create account
    </a>
</x-staff.page-header>

<dl class="mb-5 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" aria-label="Account summary">
    @foreach (['total' => ['Total accounts', 'users'], 'active' => ['Active accounts', 'user-round-check'], 'suspended' => ['Suspended accounts', 'user-round-x'], 'administrators' => ['Administrators', 'user-cog']] as $key => [$label, $icon])
        <div wire:key="account-metric-{{ $key }}" class="grid min-w-0 content-start gap-2 rounded-lg border border-[var(--line)] bg-[var(--white)] p-3 sm:p-4">
            <dt class="flex items-start gap-2 text-sm font-medium text-[var(--muted)]">
                <x-admin.icon :name="$icon" class="mt-0.5 shrink-0" />
                {{ $label }}
            </dt>
            <dd class="text-2xl leading-none font-semibold tracking-tight tabular-nums sm:text-[2rem]">{{ number_format($accounts[$key]) }}</dd>
        </div>
    @endforeach
</dl>

<section class="min-w-0 rounded-lg border border-[var(--line)] bg-[var(--white)]" aria-labelledby="account-directory-heading">
    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-[var(--line)] px-4 py-3 sm:px-5">
        <h3 id="account-directory-heading" class="text-base font-semibold">Account directory</h3>
        <p class="text-sm text-[var(--muted)] tabular-nums" role="status">
            @if ($users->total())
                Showing {{ number_format($users->firstItem()) }}–{{ number_format($users->lastItem()) }} of {{ number_format($users->total()) }} accounts
            @else
                0 accounts
            @endif
        </p>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--line)] px-4 py-3 sm:px-5" role="group" aria-label="Account filters">
        <label class="user-search min-h-11 bg-[var(--paper)]! focus-within:outline-2 focus-within:outline-[var(--blue)]">
            <x-admin.icon name="search" size="17" class="shrink-0" />
            <input class="bg-transparent! outline-none!" type="search" wire:model.live.debounce.300ms="userQuery" placeholder="Search users" aria-label="Search users" />
        </label>
        <div class="inline-flex flex-wrap gap-1 rounded-md border border-[var(--line)] p-1" role="group" aria-label="Account status">
            @foreach (['All', 'Active', 'Suspended'] as $filter)
                <button type="button" wire:key="user-status-{{ $filter }}" @class(['min-h-9 cursor-pointer rounded-sm px-3 text-sm hover:bg-[var(--paper)]', 'bg-[var(--paper)] font-semibold text-[var(--blue)] dark:text-blue-300' => $userStatusFilter === $filter, 'text-[var(--muted)]' => $userStatusFilter !== $filter]) aria-pressed="{{ $userStatusFilter === $filter ? 'true' : 'false' }}" wire:click="$set('userStatusFilter', '{{ $filter }}')">
                    {{ $filter }}
                </button>
            @endforeach
        </div>
    </div>

    <x-staff.table label="User accounts" wire:loading.class="opacity-60" wire:target="userQuery,userStatusFilter">
        <table class="w-full min-w-200 [&_td]:align-middle [&_th]:bg-[var(--paper)]">
            <thead>
                <tr>
                    <th scope="col">User</th>
                    <th scope="col">Role</th>
                    <th scope="col">Employee ID</th>
                    <th scope="col">Status</th>
                    <th scope="col">Last sign-in</th>
                    <th scope="col">Created</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td>
                            <span class="block font-medium text-[var(--ink)]">{{ $user->full_name }}</span>
                            @if ($user->email)<span class="block text-xs text-[var(--muted)]">{{ $user->email }}</span>@endif
                        </td>
                        <td>{{ $user->role->name->label() }}</td>
                        <td class="tabular-nums">{{ $user->employee_id }}</td>
                        <td>
                            <span class="ui-label gap-1.5">
                                <x-admin.icon :name="$user->is_active ? 'user-round-check' : 'user-round-x'" size="14" class="shrink-0 text-[var(--muted)]" />
                                {{ $user->is_active ? 'Active' : 'Suspended' }}
                            </span>
                        </td>
                        <td class="tabular-nums">{{ $user->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</td>
                        <td class="tabular-nums">{{ $user->created_at->format('M j, Y') }}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                @can('update', $user)
                                    <a class="text-button min-h-9 gap-1.5 hover:underline" aria-label="Edit {{ $user->full_name }}" href="{{ route('administration.users.edit', $user) }}">
                                        <x-admin.icon name="pencil" size="15" /> Edit
                                    </a>
                                @endcan
                                @can('manageAccess', $user)
                                    <button type="button" class="secondary-button min-h-9! px-2.5! py-1.5! text-xs!" wire:click="toggleUserStatus({{ $user->id }})" wire:loading.attr="disabled" wire:target="toggleUserStatus({{ $user->id }})">
                                        {{ $user->is_active ? 'Suspend' : 'Activate' }}
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8! text-center text-[var(--muted)]">No users match the selected criteria.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-staff.table>

    @if ($users->hasPages())
        <div class="border-t border-[var(--line)] px-4 sm:px-5">{{ $users->links('administration.dashboard.pagination') }}</div>
    @endif
</section>
