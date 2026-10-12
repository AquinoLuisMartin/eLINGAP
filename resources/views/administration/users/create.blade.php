@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl">
        <a class="mb-6 inline-flex items-center gap-2 text-sm text-slate-600 hover:text-osca-primary dark:text-slate-300" href="{{ route('administration.dashboard') }}"><x-admin.icon name="chevron-left" size="16" /> Back to administration</a>
        <header class="mb-6">
            <h2 class="text-2xl font-semibold">Create user account</h2>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Add an authorized user and assign their system access.</p>
        </header>
        <form class="panel space-y-8" method="POST" action="{{ route('administration.users.store') }}">
            @include('administration.users._form')
        </form>
    </div>
@endsection
