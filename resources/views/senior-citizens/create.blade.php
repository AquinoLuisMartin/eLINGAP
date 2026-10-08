@extends('layouts.app')

@section('content')
    <h1 class="mb-6 text-3xl font-bold">Register senior citizen</h1>
    <form method="POST" action="{{ route('senior-citizens.store') }}" enctype="multipart/form-data" class="grid max-w-3xl gap-4 rounded-lg border bg-white p-5">
        @include('senior-citizens._form')
    </form>
@endsection
