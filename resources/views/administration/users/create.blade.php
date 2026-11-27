@extends('layouts.app')

@section('content')
    <div class="mx-auto flex w-full min-w-0 max-w-4xl flex-col gap-4">
        <header class="flex flex-col items-start gap-2">
            <a class="text-button gap-1.5 hover:underline" href="{{ route('administration.dashboard') }}">
                <x-admin.icon name="chevron-left" size="16" />
                Back to administration
            </a>
            <h1 class="text-2xl font-semibold tracking-tight text-[var(--ink)]">Create user account</h1>
        </header>
        <form class="account-form rounded-lg border border-[var(--line)] bg-[var(--white)]" method="POST" action="{{ route('administration.users.store') }}">
            @include('administration.users._form')
        </form>
    </div>
@endsection
