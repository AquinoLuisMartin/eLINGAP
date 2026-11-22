@extends('layouts.app')

@section('content')
    <div class="w-full min-w-0 max-w-full">
        <a class="mb-6 inline-block text-sm text-slate-600 underline-offset-4 hover:underline dark:text-slate-300" href="{{ route('administration.dashboard') }}">Back to administration</a>
        <header class="mb-6">
            <h2 class="text-2xl font-semibold">Create user account</h2>

        </header>
        <form class="account-form rounded-lg border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800" method="POST" action="{{ route('administration.users.store') }}">
            @include('administration.users._form')
        </form>
    </div>
@endsection
