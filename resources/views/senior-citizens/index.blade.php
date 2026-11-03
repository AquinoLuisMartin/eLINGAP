@extends('layouts.app')

@section('content')
    <livewire:senior-citizens.index :initial-data="compact('seniorCitizens', 'counts', 'barangays', 'programs')" />
@endsection
