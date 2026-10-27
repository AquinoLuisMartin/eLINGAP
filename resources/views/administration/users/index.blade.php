@extends('layouts.app')

@section('content')
    <h1>User accounts</h1>
    @include('components.alert')
    <a href="{{ route('administration.dashboard') }}">Dashboard</a>
    <a href="{{ route('administration.users.create') }}">Create user</a>
    <a href="{{ route('administration.password-reset-requests.index') }}">Password Reset Requests ({{ $pendingRecoveryRequests }} pending)</a>
    <form method="GET" action="{{ route('administration.users.index') }}">
        <label for="search">Search users</label>
        <input id="search" name="search" value="{{ request('search') }}">
        <label for="role">Role</label>
        <x-select id="role" name="role">
            <option value="">All roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </x-select>
        <button type="submit">Search</button>
    </form>
    <table>
        <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->full_name }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->role->name->label() }}</td>
                    <td>{{ $user->is_active ? 'Active' : 'Suspended' }}</td>
                    <td>
                        @can('update', $user)<a href="{{ route('administration.users.edit', $user) }}">Edit</a>@endcan
                        @can('manageAccess', $user)
                            <form method="POST" action="{{ route('administration.users.status.update', $user) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                <button type="submit">{{ $user->is_active ? 'Suspend' : 'Activate' }}</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $users->links() }}
@endsection
