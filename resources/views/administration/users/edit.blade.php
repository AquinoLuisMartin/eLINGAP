@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <header><h2 class="text-2xl font-semibold">Edit user account</h2><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Update the account details and assigned access.</p></header>
        <form class="panel space-y-8" method="POST" action="{{ route('administration.users.update', $user) }}">
            @method('PUT')
            @include('administration.users._form')
        </form>
        <form class="panel space-y-6" method="POST" action="{{ route('administration.password-reset-requests.store', $user) }}">
            @csrf
            <h3 class="text-base font-medium">Administrator-assisted password recovery</h3>
            <p class="text-sm text-slate-500">Start a request, personally verify the employee and reauthenticate before issuing a recovery code. You cannot set or retrieve the employee's permanent password.</p>
            <x-button type="submit">Start recovery request</x-button>
        </form>
    </div>
@endsection
