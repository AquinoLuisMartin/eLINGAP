@extends('layouts.app')

@section('content')
    <h1>Edit user account</h1>
    @include('components.alert')
    <form method="POST" action="{{ route('administration.users.update', $user) }}">
        @method('PUT')
        @include('administration.users._form')
    </form>
    <h2>Reset password</h2>
    <form method="POST" action="{{ route('administration.users.password.update', $user) }}">
        @csrf
        @method('PATCH')
        @include('administration.users._password-fields')
        <button type="submit">Reset password</button>
    </form>
@endsection
