@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <header><h2 class="text-2xl font-semibold">Edit user account</h2><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Update the account details and assigned access.</p></header>
        <form class="panel space-y-8" method="POST" action="{{ route('administration.users.update', $user) }}">
            @method('PUT')
            @include('administration.users._form')
        </form>
        <form class="panel space-y-6" method="POST" action="{{ route('administration.users.password.update', $user) }}">
            @csrf
            @method('PATCH')
            <h3 class="text-base font-medium">Reset password</h3>
            <div class="grid gap-6 sm:grid-cols-2">@include('administration.users._password-fields')</div>
            <div class="flex justify-end"><button class="inline-flex min-h-11 items-center rounded-lg bg-osca-primary px-5 text-sm text-white hover:bg-osca-primary-dark" type="submit">Reset password</button></div>
        </form>
    </div>
@endsection
