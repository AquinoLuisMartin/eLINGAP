@extends('layouts.app')

@section('content')
    <x-staff.page-header title="Reports" description="Choose a report, narrow the records with filters, and export the result as CSV." />
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach (\App\Services\Reports\StaffReport::TYPES as $type => $name)
            <article class="panel flex flex-col items-start">
                <h3 class="text-lg font-semibold">{{ $name }}</h3>
                <p class="mt-3 mb-5 text-sm leading-relaxed text-[var(--muted)]">
                    {{ match ($type) {
                        'masterlist' => 'Registered senior citizens, OSCA IDs, barangays, and registration status.',
                        'applications' => 'Program applications, review dates, and processing decisions.',
                        'payouts' => 'Recorded payout amounts, programs, and claim status.',
                        'milestones' => 'Senior citizens who meet the selected milestone age threshold.',
                        'proxy' => 'Released payouts collected by recorded representatives.',
                        'archived' => 'Deceased, archived, and transferred registration records.',
                        default => 'Preview and export matching records.',
                    } }}
                </p>
                <a href="{{ route('reports.workspace', $type) }}" class="secondary-button mt-auto">Open report</a>
            </article>
        @endforeach
    </div>
@endsection
