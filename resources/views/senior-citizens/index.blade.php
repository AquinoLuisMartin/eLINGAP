@extends('layouts.app')

@section('content')
    <section class="page-intro">
        <div>
            <p class="section-kicker">Registry management</p>
            <h2>Senior citizen masterlist</h2>
            <p class="section-subtitle">Manage senior citizen records and registration status across OSCA Santa Maria.</p>
        </div>
        <a class="primary-button" href="{{ route('senior-citizens.create') }}">
            <x-admin.icon name="plus" size="17" /> Register senior citizen
        </a>
    </section>

    <div class="stat-strip">
        @foreach ($counts as $label => $count)
            <div class="mini-stat">
                <span class="mini-stat-dot {{ ['blue', 'mint', 'violet', 'amber'][$loop->index % 4] }}" aria-hidden="true"></span>
                <div><small>{{ $label }}</small><strong>{{ number_format($count) }}</strong></div>
            </div>
        @endforeach
    </div>

    <section class="panel">
        <form method="GET" action="{{ route('senior-citizens.index') }}" class="registry-filters">
            <label class="field-label">Search
                <input name="search" value="{{ request('search') }}" placeholder="OSCA ID or name">
            </label>
            <label class="field-label">Barangay
                <select name="barangay_id">
                    <option value="">All barangays</option>
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field-label">Age
                <select name="age">
                    <option value="">All ages</option>
                    @foreach (['60-69', '70-79', '80+'] as $age)
                        <option value="{{ $age }}" @selected(request('age') === $age)>{{ $age }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field-label">Status
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach (\App\Enums\SeniorCitizenStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-2">
                <button type="submit" class="primary-button"><x-admin.icon name="sliders-horizontal" size="16" /> Filter</button>
                <a href="{{ route('senior-citizens.index') }}" class="secondary-button">Reset</a>
            </div>
        </form>

        <p class="section-subtitle mb-3" role="status">{{ number_format($seniorCitizens->total()) }} results</p>
        <div class="table-scroll">
            <table class="min-w-160" aria-label="Senior citizen masterlist">
                <thead>
                    <tr>
                        @foreach (['OSCA ID', 'Last name', 'First name', 'Middle name', 'Age', 'Barangay', 'Status', 'Actions'] as $heading)
                            <th scope="col">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($seniorCitizens as $seniorCitizen)
                        <tr>
                            <td>{{ $seniorCitizen->osca_id_number ?: '—' }}</td>
                            <td>{{ $seniorCitizen->last_name }}</td>
                            <td>{{ $seniorCitizen->first_name }}</td>
                            <td>{{ $seniorCitizen->middle_name ?: '—' }}</td>
                            <td>{{ $seniorCitizen->birth_date->age }}</td>
                            <td>{{ $seniorCitizen->barangay->name }}</td>
                            <td><x-badge :status="$seniorCitizen->status" /></td>
                            <td><a class="text-button" href="{{ route('senior-citizens.show', $seniorCitizen) }}" aria-label="View {{ $seniorCitizen->full_name }}">View <x-admin.icon name="chevron-right" size="15" /></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-6 text-center">No senior citizen records match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $seniorCitizens->links() }}</div>
    </section>
@endsection
