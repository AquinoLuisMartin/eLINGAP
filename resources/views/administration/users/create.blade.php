@extends('layouts.app')

@section('content')
    <h1>Create user account</h1>
    @include('components.alert')
    <form method="POST" action="{{ route('administration.users.store') }}">
        @include('administration.users._form')
    </form>
@endsection
