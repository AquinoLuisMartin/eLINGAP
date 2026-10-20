<div>
    <x-staff.page-header title="Applications and Registration" description="Review registration requirements, prepare senior citizen IDs, and track issued records.">
        @can('create', \App\Models\SeniorCitizen::class)
            <a href="{{ route('registrations.import.create') }}" class="secondary-button">Import records</a>
            <button type="button" class="primary-button" wire:click="apply" wire:loading.attr="disabled" wire:target="apply">New ID Application</button>
        @endcan
    </x-staff.page-header>

    <div class="staff-summary-grid mb-5 grid grid-cols-1 gap-px overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--line)] sm:grid-cols-3" role="group" aria-label="Filter registrations by application stage">
        @foreach (['PENDING' => ['Needs review', 'pending'], 'VERIFIED' => ['Verified / ID pending', 'verified'], 'ISSUED' => ['ID issued', 'issued']] as $value => [$label, $column])
            <button type="button" class="staff-summary-item flex flex-col items-start gap-1 bg-[var(--white)] p-4 text-left" wire:key="registration-stage-{{ $value }}" wire:click="$set('stage', '{{ $value }}')" aria-pressed="{{ $stage === $value ? 'true' : 'false' }}">
                <span class="staff-summary-label text-sm text-[var(--muted)]">{{ $label }}</span>
                <span class="staff-summary-value text-2xl font-semibold">{{ number_format($counts->{$column} ?? 0) }}</span>
            </button>
        @endforeach
    </div>

    <section class="staff-filter-panel mb-5 rounded-lg border border-[var(--line)] bg-[var(--white)] p-5" aria-labelledby="registration-filters-title">
        <h3 id="registration-filters-title" class="mb-4 text-base font-semibold">Find applications</h3>
        <div class="staff-filter-grid grid gap-4 sm:grid-cols-2 xl:grid-cols-12">
            <label class="field-label sm:col-span-2 xl:col-span-6">Search name or ID
                <input wire:model.live.debounce.300ms="search" type="search" maxlength="100" placeholder="Registration number, name or OSCA ID">
            </label>
            <label class="field-label xl:col-span-3">Application stage
                <select wire:model.live="stage"><option value="">All records</option><option value="PENDING">Needs review</option><option value="VERIFIED">Verified / ID pending</option><option value="ISSUED">ID issued</option></select>
            </label>
            <label class="field-label xl:col-span-3">Barangay
                <select wire:model.live="barangayId">
                    <option value="">All barangays</option>
                    @foreach ($barangays as $barangay)
                        <option wire:key="registration-filter-barangay-{{ $barangay->id }}" value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-[var(--muted)]" role="status">
                <span wire:loading.remove wire:target="search,stage,barangayId">Results update as you search or change filters.</span>
                <span wire:loading wire:target="search,stage,barangayId">Updating applications...</span>
            </p>
            <a href="{{ route('registrations.index') }}" class="secondary-button">Reset filters</a>
        </div>
    </section>

    <section class="staff-results-panel overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--white)]" aria-labelledby="registration-results-title">
        <div class="staff-panel-heading flex flex-wrap items-center justify-between gap-3 border-b border-[var(--line)] px-5 py-4">
            <h3 id="registration-results-title">Registration records</h3>
            <p role="status">
                @if ($registrations->total())
                    Showing {{ number_format($registrations->firstItem()) }}–{{ number_format($registrations->lastItem()) }} of {{ number_format($registrations->total()) }} results
                @else
                    0 results
                @endif
            </p>
        </div>
        <x-staff.table label="Senior citizen registrations" wire:loading.class="opacity-60" wire:target="search,stage,barangayId">
            <table>
                <thead><tr><th scope="col">Registration / Senior</th><th scope="col">Barangay</th><th scope="col">Applicant</th><th scope="col">Submitted</th><th scope="col">Stage</th><th scope="col">Action</th></tr></thead>
                <tbody>
                    @forelse ($registrations as $senior)
                        <tr wire:key="registration-record-{{ $senior->id }}">
                            <td><span class="font-medium">{{ $senior->full_name }}</span><p class="mt-1 text-xs text-[var(--muted)]">{{ $senior->registration_number }}</p></td>
                            <td>{{ $senior->barangay->name }}</td>
                            <td>{{ $senior->applicant_type === 'REPRESENTATIVE' ? 'Authorized representative' : 'Senior personally' }}</td>
                            <td class="whitespace-nowrap">{{ $senior->created_at->format('M j, Y') }}</td>
                            <td>
                                @if ($senior->status->value === 'VERIFIED')
                                    {{ $senior->id_generated_at ? 'ID issued' : 'Verified / ID pending' }}
                                @elseif ($senior->status->value === 'PENDING')
                                    Needs review
                                @else
                                    {{ ucfirst(strtolower($senior->status->value)) }}
                                @endif
                            </td>
                            <td><a class="text-button" href="{{ route(auth()->user()->can('manageId', $senior) && in_array($senior->status->value, ['PENDING', 'VERIFIED']) ? 'senior-id.show' : 'senior-citizens.show', [$senior, 'from' => 'registrations', 'search' => $search, 'stage' => $stage, 'barangay_id' => $barangayId, 'page' => $this->getPage()]) }}">{{ auth()->user()->can('manageId', $senior) ? ($senior->status->value === 'PENDING' ? 'Review requirements' : ($senior->status->value === 'VERIFIED' ? ($senior->id_generated_at ? 'Reprint ID' : 'Prepare ID') : 'View record')) : 'View record' }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><p class="py-6 text-center text-[var(--muted)]">No matching ID applications. Adjust your filters.@can('create', \App\Models\SeniorCitizen::class) You can also start a new application.@endcan</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-staff.table>
        @if ($registrations->hasPages())<div class="staff-pagination">{{ $registrations->links() }}</div>@endif
    </section>
    @if ($applicationOpen)<livewire:registrations.id-application :modal="true" :key="'new-id-application'" />@endif
</div>
