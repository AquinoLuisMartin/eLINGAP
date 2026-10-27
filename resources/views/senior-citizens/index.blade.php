@extends('layouts.app')

@section('content')
    @php
        $canPrint = auth()->user()->can('configureId', \App\Models\SeniorCitizen::class);
        $additionalFilters = filled(request('program_id')) || filled(request('benefit'))
            || request('sort', 'id') !== 'id' || request('direction', 'desc') !== 'desc';
    @endphp

    <div class="registry-workspace flex min-w-0 flex-col gap-5">
        <x-staff.page-header class="mb-0" title="Senior Registry" description="Process senior citizen ID applications, review requirements, and prepare IDs.">
            @can('create', \App\Models\SeniorCitizen::class)
                <livewire:senior-citizens.application-launcher />
            @endcan
        </x-staff.page-header>

        <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--line)] md:grid-cols-4" aria-label="Registry totals for current filters">
            @foreach ($counts as $label => $count)
                <div class="flex flex-col gap-1 bg-[var(--white)] px-5 py-4">
                    <dt class="text-sm text-[var(--muted)]">{{ $label === 'Total registry' ? 'Matching records' : $label }}</dt>
                    <dd class="text-2xl font-semibold tabular-nums text-[var(--ink)]">{{ number_format($count) }}</dd>
                </div>
            @endforeach
        </dl>

        <section class="rounded-lg border border-[var(--line)] bg-[var(--white)]" aria-labelledby="registry-filters-title">
            <form method="GET" action="{{ route('senior-citizens.index') }}" class="p-5">
                <h3 id="registry-filters-title" class="mb-4 text-base font-semibold">Find applications and records</h3>
                <div class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-12">
                    <div class="field-label xl:col-span-2"><label data-select-label>Search by</label><x-select label="Search by" name="search_by">
                            @foreach (['all' => 'ID No. or names', 'osca_id' => 'OSCA ID No.', 'name' => 'Names'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('search_by', 'all') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <label class="field-label xl:col-span-4">Search term
                        <input type="search" name="search" maxlength="100" value="{{ request('search') }}" placeholder="OSCA ID No. or name" aria-describedby="registry-search-help">
                    </label>
                    <div class="min-w-0 xl:col-span-2">
                        @include('senior-citizens._barangay-filter', ['barangays' => $barangays])
                    </div>
                    <div class="field-label xl:col-span-2"><label data-select-label>Age group</label><x-select label="Age group" name="age">
                            <option value="">All ages</option>
                            @foreach (['60-69', '70-79', '80+'] as $age)
                                <option value="{{ $age }}" @selected(request('age') === $age)>{{ $age }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="field-label xl:col-span-2"><label data-select-label>Registration status</label><x-select label="Registration status" name="status">
                            <option value="">All statuses</option>
                            @foreach (\App\Enums\SeniorCitizenStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>

                <p id="registry-search-help" class="mt-3 text-sm text-[var(--muted)]">OSCA ID No. requires the complete number. Names accepts first, middle, or last names together, in any order.</p>

                <details class="mt-4 border-t border-[var(--line)] pt-1" @if ($additionalFilters) open @endif>
                    <summary class="w-fit cursor-pointer rounded py-3 text-sm text-[var(--blue)] dark:text-blue-300">More filters and sorting</summary>
                    <div class="grid gap-4 pt-1 pb-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="field-label"><label data-select-label>Program or benefit</label><x-select label="Program or benefit" name="program_id">
                                <option value="">All programs</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="field-label"><label data-select-label>Pension / assignment</label><x-select label="Pension / assignment" name="benefit">
                                <option value="">All benefits</option>
                                @foreach (['PENSIONER' => 'Pensioner', 'NATIONAL_PENSION' => 'National pension', 'LOCAL_PENSION' => 'Local pension', 'NONE' => 'No assigned benefits'] as $value => $label)
                                    <option value="{{ $value }}" @selected(request('benefit') === $value)>{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="field-label"><label data-select-label>Sort by</label><x-select label="Sort by" name="sort">
                                @foreach (['id' => 'Registration', 'last_name' => 'Last name', 'first_name' => 'First name', 'middle_name' => 'Middle name', 'benefits' => 'Programs and benefits'] as $value => $label)
                                    <option value="{{ $value }}" @selected(request('sort', 'id') === $value)>{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="field-label"><label data-select-label>Order</label><x-select label="Order" name="direction">
                                <option value="asc" @selected(request('direction') === 'asc')>Ascending</option>
                                <option value="desc" @selected(request('direction', 'desc') === 'desc')>Descending</option>
                            </x-select>
                        </div>
                    </div>
                </details>

                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <button type="submit" class="primary-button">Apply filters</button>
                    <a href="{{ route('senior-citizens.index') }}" class="secondary-button">Reset</a>
                </div>
            </form>
        </section>

        <section class="min-w-0 rounded-lg border border-[var(--line)] bg-[var(--white)]" aria-labelledby="registry-results-title" x-data="{ selected: [] }">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--line)] px-5 py-4">
                <h3 id="registry-results-title" class="text-base font-semibold">Senior records</h3>
                <p class="text-sm text-[var(--muted)]" role="status">
                    @if ($seniorCitizens->total())
                        Showing {{ number_format($seniorCitizens->firstItem()) }}–{{ number_format($seniorCitizens->lastItem()) }} of {{ number_format($seniorCitizens->total()) }} results
                    @else
                        0 results
                    @endif
                </p>
            </div>

            @if ($canPrint)
                <details class="border-b border-[var(--line)] px-5" @if ($errors->hasAny(['senior_ids', 'senior_ids.*', 'paper', 'purpose'])) open @endif>
                    <summary class="w-fit cursor-pointer rounded py-3 text-sm text-[var(--blue)] dark:text-blue-300">ID printing <span class="ml-2 text-[var(--muted)]" x-cloak x-show="selected.length" x-text="selected.length + ' of 4 selected'"></span></summary>
                    <form id="batch-id-print" method="POST" action="{{ route('senior-id.batch-print') }}" target="_blank" class="grid items-end gap-4 pt-1 pb-5 sm:grid-cols-2 lg:grid-cols-[10rem_12rem_auto]">
                        @csrf
                        <div class="field-label"><label data-select-label>Paper size</label><x-select label="Paper size" name="paper"><option value="short">Short</option><option value="long">Long</option><option value="a4">A4</option></x-select>
                        </div>
                        <div class="field-label"><label data-select-label>Print purpose</label><x-select label="Print purpose" name="purpose"><option value="FIRST_PRINT">First print</option><option value="REPRINT">Reprint</option><option value="REPLACEMENT">Replacement</option></x-select>
                        </div>
                        <button type="submit" class="secondary-button justify-self-start disabled:cursor-not-allowed disabled:opacity-50" :disabled="selected.length === 0">Print selected IDs</button>
                        <p id="registry-print-help" class="text-sm text-[var(--muted)] sm:col-span-2 lg:col-span-3">Select up to 4 issued IDs in the table. Four complete IDs fit on one sheet at actual size.</p>
                    </form>
                </details>
            @endif

            <div class="table-scroll" role="region" aria-label="Senior records table" tabindex="0">
                <table class="min-w-[64rem]" aria-label="Senior citizen masterlist">
                    <thead>
                        <tr>
                            @if ($canPrint)<th scope="col" class="w-16">Print</th>@endif
                            @foreach (['OSCA ID No.', 'Last name', 'First name', 'Middle name', 'Age', 'Barangay', 'Programs and benefits', 'Status', 'Actions'] as $heading)
                                <th scope="col">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($seniorCitizens as $seniorCitizen)
                            <tr wire:key="registry-record-{{ $seniorCitizen->id }}">
                                @if ($canPrint)
                                    <td>
                                        @if ($seniorCitizen->id_generated_at && $seniorCitizen->verified_at && in_array($seniorCitizen->status->value, ['VERIFIED', 'ARCHIVED']))
                                            <input type="checkbox" form="batch-id-print" name="senior_ids[]" value="{{ $seniorCitizen->id }}" x-model="selected" :disabled="selected.length >= 4 && !selected.includes($el.value)" class="size-4 cursor-pointer accent-[var(--blue)] disabled:cursor-not-allowed" aria-label="Print ID for {{ $seniorCitizen->full_name }}" aria-describedby="registry-print-help">
                                        @else
                                            <span class="text-[var(--muted)]" aria-label="ID not available for printing">—</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="whitespace-nowrap tabular-nums">{{ $seniorCitizen->osca_id_number ?: 'Not issued' }}</td>
                                <td>{{ $seniorCitizen->last_name }}</td>
                                <td>{{ $seniorCitizen->first_name }}</td>
                                <td>{{ $seniorCitizen->middle_name ?: '—' }}</td>
                                <td>{{ $seniorCitizen->birth_date->age }}</td>
                                <td>{{ $seniorCitizen->barangay->name }}</td>
                                <td>
                                    @foreach ($seniorCitizen->activeAssignments as $assignment)<p>{{ $assignment->program->name }}</p>@endforeach
                                    @if ($seniorCitizen->has_national_pension || $seniorCitizen->has_local_pension)<p>Pensioner ({{ implode(', ', array_filter([$seniorCitizen->has_national_pension ? 'National' : null, $seniorCitizen->has_local_pension ? 'Local' : null])) }})</p>
                                    @elseif ($seniorCitizen->activeAssignments->isEmpty())<span class="text-slate-500">None assigned</span>@endif
                                </td>
                                <td><x-badge :status="$seniorCitizen->status" /></td>
                                <td>
                                    <div class="flex flex-col items-start gap-2">
                                        @if (auth()->user()->can('manageId', $seniorCitizen) && in_array($seniorCitizen->status->value, ['PENDING', 'VERIFIED']))
                                            <a class="text-button" href="{{ route('senior-id.show', [$seniorCitizen, 'from' => 'registry', ...request()->only(['search', 'search_by', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])]) }}">{{ $seniorCitizen->status->value === 'PENDING' ? 'Review requirements' : ($seniorCitizen->id_generated_at ? 'Manage ID' : 'Prepare ID') }}</a>
                                        @endif
                                        <a class="text-button" href="{{ route('senior-citizens.show', [$seniorCitizen, 'from' => 'registry', ...request()->only(['search', 'search_by', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])]) }}" aria-label="View {{ $seniorCitizen->full_name }}">View record <x-admin.icon name="chevron-right" size="15" /></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canPrint ? 10 : 9 }}" class="p-6 text-center">No senior citizen records match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($seniorCitizens->hasPages())
                <div class="border-t border-[var(--line)] px-5 py-4">{{ $seniorCitizens->links() }}</div>
            @endif
        </section>
    </div>
@endsection
