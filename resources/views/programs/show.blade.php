@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl">
    <x-staff.page-header :title="$program->name" description="Program details and application activity.">
        <a class="secondary-button" href="{{ route('programs.index') }}">Back to programs</a>
    </x-staff.page-header>
    <section class="panel">
        <dl class="workspace-detail-grid">
            <div><dt>Agency</dt><dd>{{ $program->agency }}</dd></div>
            <div><dt>Status</dt><dd><x-badge :status="$program->status" /></dd></div>
            <div><dt>Budget</dt><dd>{{ \App\Support\PhilippineCurrency::format($program->budget) }}</dd></div>
            <div><dt>Application activity</dt><dd>Applications: {{ $program->applications_count }}</dd></div>
            <div><dt>Starts on</dt><dd>{{ $program->starts_on?->format('M j, Y') ?? 'Not set' }}</dd></div>
            <div><dt>Ends on</dt><dd>{{ $program->ends_on?->format('M j, Y') ?? 'Not set' }}</dd></div>
        </dl>
        @if ($program->description)
            <div class="mt-6 border-t border-[var(--line)] pt-5">
                <h3 class="mb-3 text-base font-semibold">Description</h3>
                <p class="whitespace-pre-wrap text-sm leading-relaxed">{{ $program->description }}</p>
            </div>
        @endif
        <div class="staff-form-actions"><a class="secondary-button" href="{{ route('programs.beneficiaries.index', $program) }}">View beneficiaries</a></div>
    </section>
</div>
@endsection
