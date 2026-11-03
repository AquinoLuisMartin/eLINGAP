<div>
    @php($canPrint = auth()->user()->can('configureId', \App\Models\SeniorCitizen::class))
    <x-staff.page-header title="Registrations" description="Find approved senior citizens and existing records, including issued IDs and inactive registrations.">
        @can('create', \App\Models\SeniorCitizen::class)
            <livewire:senior-citizens.application-launcher />
        @endcan
    </x-staff.page-header>

    <div class="staff-summary-grid mb-5 grid grid-cols-1 gap-px overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--line)] sm:grid-cols-3" role="group" aria-label="Filter registrations by record stage">
        @foreach (['VERIFIED' => ['Approved / ID pending', 'verified'], 'ISSUED' => ['ID issued', 'issued'], 'INACTIVE' => ['Inactive records', 'inactive']] as $value => [$label, $column])
            <button type="button" class="staff-summary-item flex flex-col items-start gap-1 bg-[var(--white)] p-4 text-left" wire:key="registration-stage-{{ $value }}" wire:click="$set('stage', '{{ $value }}')" aria-pressed="{{ $stage === $value ? 'true' : 'false' }}">
                <span class="staff-summary-label text-sm text-[var(--muted)]">{{ $label }}</span>
                <span class="staff-summary-value text-2xl font-semibold">{{ number_format($counts->{$column} ?? 0) }}</span>
            </button>
        @endforeach
    </div>

    <section class="staff-filter-panel mb-5 rounded-lg border border-[var(--line)] bg-[var(--white)] p-5" aria-labelledby="registration-filters-title">
        <h3 id="registration-filters-title" class="mb-4 text-base font-semibold">Find registered seniors</h3>
        <div class="staff-filter-grid grid gap-4 md:grid-cols-3">
            <label class="field-label">Search term
                <input wire:model.live.debounce.300ms="search" type="search" data-uppercase-search class="uppercase placeholder:normal-case" maxlength="100" autocomplete="off" placeholder="OSCA ID No. or name">
            </label>
            <div class="field-label"><label data-select-label>Record stage</label><x-select label="Record stage" wire:model.live="stage"><option value="">All records</option><option value="VERIFIED">Approved / ID pending</option><option value="ISSUED">ID issued</option><option value="INACTIVE">Inactive records</option></x-select>
            </div>
            <div class="field-label"><label data-select-label>Barangay</label><x-select label="Barangay" wire:model.live="barangayId">
                    <option value="">All barangays</option>
                    @foreach ($barangays as $barangay)
                        <option wire:key="registration-filter-barangay-{{ $barangay->id }}" value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                    @endforeach
                </x-select>
            </div>
        </div>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <a href="{{ route('registrations.index') }}" class="secondary-button">Reset filters</a>
            <span class="text-sm text-[var(--muted)]" role="status" wire:loading wire:target="search,searchBy,stage,barangayId">Updating records...</span>
        </div>
    </section>

    <section class="staff-results-panel overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--white)]" aria-labelledby="registration-results-title" x-data="{ selected: [], printLayout: 'batch' }" wire:key="registration-print-{{ md5(json_encode([$search, $searchBy, $stage, $barangayId, $this->getPage()])) }}">
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
            @if ($canPrint)
                <details class="border-b border-[var(--line)] px-5" @if ($errors->hasAny(['senior_ids', 'senior_ids.*', 'paper', 'purpose'])) open @endif>
                    <summary class="w-fit cursor-pointer rounded py-3 text-sm text-[var(--blue)] dark:text-blue-300">ID printing <span class="ml-2 text-[var(--muted)]" x-cloak x-show="selected.length" x-text="selected.length + ' of ' + (printLayout === 'single' ? 1 : 4) + ' selected'"></span></summary>
                    <form id="batch-id-print" method="POST" action="{{ route('senior-id.batch-print') }}" target="_blank" class="pt-1 pb-2">
                        @csrf
                        <input type="hidden" name="purpose" value="FIRST_PRINT">
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="field-label w-64 max-w-full"><label data-select-label>Print layout</label><x-select label="Print layout" name="layout" x-model="printLayout" @change="selected = []"><option value="single">Single ID</option><option value="batch" selected>Batch (4 per A4/Letter page)</option></x-select></div>
                            <div class="field-label w-40 max-w-full"><label data-select-label>Paper size</label><x-select label="Paper size" name="paper"><option value="short">Short</option><option value="long">Long</option><option value="a4">A4</option></x-select>
                            </div>
                            <button type="submit" class="secondary-button shrink-0 disabled:cursor-not-allowed disabled:opacity-50" :disabled="selected.length === 0">Print selected IDs</button>
                        </div>
                        <p id="registration-print-help" class="mt-2 mb-2 text-sm text-[var(--muted)]">Select up to 4 issued IDs in the table. Four complete IDs fit on one sheet at actual size.</p>
                    </form>
                </details>
            @endif
        <x-staff.table label="Senior citizen registrations" wire:loading.class="opacity-60" wire:target="search,searchBy,stage,barangayId">
            <table class="w-full">
                <thead>
                    <tr>
                        @if ($canPrint)<th scope="col" class="w-16">Print</th>@endif
                        <th scope="col" class="whitespace-nowrap">OSCA ID No.</th>
                        <th scope="col">Last name</th>
                        <th scope="col">First name</th>
                        <th scope="col">Middle name</th>
                        <th scope="col">Barangay</th>
                        <th scope="col">Registered</th>
                        <th scope="col">Stage</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registrations as $senior)
                        <tr wire:key="registration-record-{{ $senior->id }}">
                                @if ($canPrint)
                                    <td>
                                        <input type="checkbox" form="batch-id-print" name="senior_ids[]" value="{{ $senior->id }}" x-model="selected" @disabled($senior->status->value !== 'VERIFIED' || $senior->id_generated_at) :disabled="{{ $senior->status->value !== 'VERIFIED' || $senior->id_generated_at ? 'true' : 'false' }} || (selected.length >= (printLayout === 'single' ? 1 : 4) && !selected.includes($el.value))" class="size-4 cursor-pointer accent-[var(--blue)] disabled:cursor-not-allowed disabled:opacity-50" aria-label="Print ID for {{ $senior->full_name }}" aria-describedby="registration-print-help">
                                    </td>
                                @endif
                            <td class="whitespace-nowrap tabular-nums">{{ $senior->osca_id_number ?: 'Not issued' }}</td>
                            <td>{{ $senior->last_name }}</td>
                            <td>{{ $senior->first_name }}</td>
                            <td>{{ $senior->middle_name ?: '—' }}</td>
                            <td>{{ $senior->barangay->name }}</td>
                            <td class="whitespace-nowrap">{{ $senior->created_at->format('M j, Y') }}</td>
                            <td>
                                <x-milestone-badge :senior="$senior" />
                                @if ($senior->status->value === 'VERIFIED')
                                    {{ $senior->id_generated_at ? 'ID issued' : 'Approved / ID pending' }}
                                @else
                                    {{ ucfirst(strtolower($senior->status->value)) }}
                                @endif
                            </td>
                            <td><div class="flex flex-col items-start gap-2">
                                @if (auth()->user()->can('manageId', $senior) && in_array($senior->status->value, ['VERIFIED', 'ARCHIVED']))
                                    <a class="text-button" href="{{ route('senior-id.show', [$senior, 'from' => 'registrations', 'search' => $search, 'search_by' => $searchBy, 'stage' => $stage, 'barangay_id' => $barangayId, 'page' => $this->getPage()]) }}">{{ $senior->id_generated_at ? 'Manage ID' : 'Prepare ID' }}</a>
                                @endif
                                <a class="text-button" href="{{ route('senior-citizens.show', [$senior, 'from' => 'registrations', 'search' => $search, 'search_by' => $searchBy, 'stage' => $stage, 'barangay_id' => $barangayId, 'page' => $this->getPage()]) }}">View record <x-admin.icon name="chevron-right" size="15" /></a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canPrint ? 9 : 8 }}"><p class="py-6 text-center text-[var(--muted)]">No matching registration records. Adjust your filters.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-staff.table>
        @if ($registrations->hasPages())
            {{ $registrations->onEachSide(1)->links('components.pagination.livewire', ['label' => 'Registrations pagination', 'scrollTarget' => 'registration-results-title']) }}
        @endif
    </section>
</div>
