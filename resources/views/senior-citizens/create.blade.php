@extends('layouts.app')

@section('content')
    <h1>Register senior citizen</h1>
    @include('components.alert')
    <form method="POST" action="{{ route('senior-citizens.store') }}">
        @include('senior-citizens._form')
    </form>
@endsection
