@extends('layouts.app')

@section('content')
    <div class="user-accounts flex flex-col gap-6">
        <a class="w-fit text-sm text-slate-600 underline-offset-4 hover:underline dark:text-slate-300" href="{{ route('administration.dashboard') }}">Back to administration</a>

        <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-2">
                <h1>User accounts</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Manage employee accounts and system access.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a class="secondary-button" href="{{ route('administration.password-reset-requests.index') }}">Password reset requests ({{ $pendingRecoveryRequests }} pending)</a>
                <a class="primary-button" href="{{ route('administration.users.create') }}">Create user</a>
            </div>
        </header>

        <section class="min-w-0 rounded-lg border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800" aria-labelledby="accounts-heading">
            <form class="grid items-end gap-4 border-b border-slate-200 p-5 md:grid-cols-[minmax(0,1fr)_minmax(12rem,16rem)_auto] dark:border-slate-700" method="GET" action="{{ route('administration.users.index') }}" role="search" aria-label="Filter user accounts">
                <div class="ui-field">
                    <label class="text-sm" for="search">Search users</label>
                    <input class="w-full" id="search" name="search" type="search" maxlength="100" placeholder="Name or employee ID" value="{{ request('search') }}">
                </div>
                <div class="ui-field">
                    <label class="text-sm" for="role">Role</label>
                    <x-select class="w-full" id="role" name="role">
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <x-button type="submit" variant="secondary">Search</x-button>
                    @if (request()->filled('search') || request()->filled('role'))
                        <a class="text-sm text-slate-600 underline underline-offset-4 dark:text-slate-300" href="{{ route('administration.users.index') }}">Clear filters</a>
                    @endif
                </div>
            </form>

            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-4">
                <h2 id="accounts-heading" class="text-base">{{ request()->filled('search') || request()->filled('role') ? 'Search results' : 'All accounts' }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($users->total()) }} {{ $users->total() === 1 ? 'account' : 'accounts' }}</p>
            </div>

            <div class="table-scroll" role="region" aria-labelledby="accounts-heading" tabindex="0">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Employee accounts with assigned roles, account status, and available actions.</caption>
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Employee ID</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="account-name">{{ $user->full_name }}@if ($user->id === auth()->id())<span class="ml-2 text-xs text-slate-500 dark:text-slate-400">(you)</span>@endif</td>
                                <td class="whitespace-nowrap tabular-nums">{{ $user->employee_id }}</td>
                                <td class="whitespace-nowrap">{{ $user->role->name->label() }}</td>
                                <td><span class="ui-label">{{ $user->is_active ? 'Active' : 'Suspended' }}</span></td>
                                <td>
                                    <div class="flex items-center justify-end gap-4 whitespace-nowrap">
                                        @can('update', $user)
                                            <a class="account-action" href="{{ route('administration.users.edit', $user) }}">Edit<span class="sr-only"> account {{ $user->employee_id }}</span></a>
                                        @endcan
                                        @can('manageAccess', $user)
                                            <form method="POST" action="{{ route('administration.users.status.update', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                                <button class="account-action {{ $user->is_active ? 'account-action-suspend' : '' }}" type="submit">{{ $user->is_active ? 'Suspend' : 'Activate' }}<span class="sr-only"> account {{ $user->employee_id }}</span></button>
                                            </form>
                                        @endcan
                                        @cannot('update', $user)
                                            @cannot('manageAccess', $user)
                                                <span class="text-xs text-slate-500 dark:text-slate-400">Unavailable</span>
                                            @endcannot
                                        @endcannot
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="flex flex-col items-center gap-2 py-10 text-center">
                                        <p>No users found.</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ request()->filled('search') || request()->filled('role') ? 'Try another name, employee ID, or role.' : 'Create a user account to get started.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-slate-200 p-5 dark:border-slate-700">{{ $users->links() }}</div>
            @endif
        </section>
    </div>
@endsection
