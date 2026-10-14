@extends('layouts.app')

@section('content')
    <x-workspace-navigation section="benefits" />
    @if (request()->routeIs('benefits.programs'))
        <livewire:benefits.programs />
    @else
        <livewire:benefits.workspace />
    @endif
@endsection
