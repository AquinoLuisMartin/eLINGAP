@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl">
    <x-staff.page-header :title="$application->application_number" description="Review benefit application details and processing history.">
        <a class="secondary-button" href="{{ route('applications.index') }}">Back to applications</a>
    </x-staff.page-header>
    <section class="panel">
        <h3 class="mb-5 text-base font-semibold">Application details</h3>
        <dl class="workspace-detail-grid">
            <div><dt>Applicant</dt><dd>{{ $application->seniorCitizen->full_name }}</dd></div>
            <div><dt>Program</dt><dd>{{ $application->program->name }}</dd></div>
            <div><dt>Submitted</dt><dd>{{ $application->applied_on->format('M j, Y') }}</dd></div>
            <div><dt>Status</dt><dd><x-badge :status="$application->status" /></dd></div>
        </dl>
        @if ($application->remarks)<p class="mt-5 whitespace-pre-wrap text-sm leading-relaxed">{{ $application->remarks }}</p>@endif
        @can('review', $application)
            @if ($application->status->value === 'PENDING')
                <form class="mt-6 flex flex-col gap-5 border-t border-[var(--line)] pt-6" method="POST" action="{{ route('applications.status.update', $application) }}">
                    @csrf
                    @method('PATCH')
                    <h3 class="text-base font-semibold">Record a decision</h3>
                    <div class="ui-field">
                        <label for="application-decision">Decision</label>
                        <x-select class="w-full" id="application-decision" name="status" required>
                            <option value="APPROVED" @selected(old('status') === 'APPROVED')>Approve</option>
                            <option value="REJECTED" @selected(old('status') === 'REJECTED')>Reject</option>
                        </x-select>
                    </div>
                    <label class="ui-field">Remarks<textarea class="w-full" name="remarks" rows="4" maxlength="5000" aria-describedby="application-remarks-help">{{ old('remarks') }}</textarea></label>
                    <p id="application-remarks-help" class="field-helper">A reason is required when rejecting an application.</p>
                    <div class="staff-form-actions"><x-button type="submit">Save decision</x-button></div>
                </form>
            @endif
        @endcan
    </section>
    <section class="panel mt-6" aria-labelledby="application-history-heading">
        <h3 id="application-history-heading" class="mb-4 text-base font-semibold">Status history</h3>
        <ul class="divide-y divide-[var(--line)]">
            @forelse ($application->statusHistories as $history)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <x-badge :status="$history->to_status" />
                    <time class="text-sm text-[var(--muted)]" datetime="{{ $history->created_at->toIso8601String() }}">{{ $history->created_at->format('M j, Y g:i A') }}</time>
                </li>
            @empty
                <li class="text-sm text-[var(--muted)]">No status changes recorded.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
