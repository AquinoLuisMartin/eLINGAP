@extends('layouts.app')

@section('content')
    <h1>Edit senior citizen</h1>
    @include('components.alert')
    <form method="POST" action="{{ route('senior-citizens.update', $seniorCitizen) }}">
        @method('PUT')
        @include('senior-citizens._form')
    </form>
@endsection
