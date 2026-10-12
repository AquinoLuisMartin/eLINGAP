@extends('layouts.app')

@section('content')
    <h2 class="mb-6 text-3xl font-semibold">Edit senior citizen</h2>
    <form method="POST" action="{{ route('senior-citizens.update', $seniorCitizen) }}" enctype="multipart/form-data" class="grid max-w-3xl gap-4 rounded-lg border bg-white p-5">
        @method('PUT')
        @include('senior-citizens._form')
    </form>
@endsection
