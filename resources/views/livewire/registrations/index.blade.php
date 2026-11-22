<div data-print-workspace x-data="{}" @focus.window="$wire.$refresh()">
    @php($canPrint = auth()->user()->can('configureId', \App\Models\SeniorCitizen::class))
    <x-staff.page-header title="Registrations">
        @can('create', \App\Models\SeniorCitizen::class)
            <livewire:senior-citizens.application-launcher />
            <a class="secondary-button" href="{{ route('senior-id.reprint-approvals') }}">Reprint Approvals</a>
        @endcan
    </x-staff.page-header>

    <div class="staff-summary-grid mb-5 grid grid-cols-1 gap-px overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--line)] sm:grid-cols-3" role="group" aria-label="Registration metrics">
        @foreach (['VERIFIED' => ['Approved / ID pending', 'verified'], 'ISSUED' => ['ID issued', 'issued'], 'INACTIVE' => ['Inactive records', 'inactive']] as $value => [$label, $column])
            <div class="staff-summary-item flex flex-col items-start gap-1 bg-[var(--white)] p-4 text-left" wire:key="registration-stage-{{ $value }}">
                <span class="staff-summary-label text-sm text-[var(--muted)]">{{ $label }}</span>
                <span class="staff-summary-value text-2xl font-semibold">{{ number_format($counts->{$column} ?? 0) }}</span>
            </div>
        @endforeach
    </div>

    <section class="staff-filter-panel mb-5 rounded-lg border border-[var(--line)] bg-[var(--white)] p-5" aria-labelledby="registration-filters-title">
        <h3 id="registration-filters-title" class="mb-4 text-base font-semibold">Find registered seniors</h3>
        <div class="staff-filter-grid grid gap-4 md:grid-cols-3">
            <label class="field-label">Search term
                <input wire:model.live.debounce.300ms="search" type="search" data-uppercase-search class="uppercase placeholder:normal-case" maxlength="100" autocomplete="off" placeholder="OSCA ID No. or name">
            </label>
            <div class="field-label"><label data-select-label>Record stage</label><x-select label="Record stage" wire:model.live="stage"><option value="">Active registrations</option><option value="VERIFIED">Approved / ID pending</option><option value="ISSUED">ID issued / pending printing</option><option value="HISTORY">Registration history / printed</option><option value="INACTIVE">Inactive records</option></x-select>
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
            @if ($search !== '' || $searchBy !== 'all' || $stage !== '' || $barangayId !== '')
                <button type="button" class="text-button hover:underline" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters">Reset filters</button>
            @endif
            <span class="text-sm text-[var(--muted)]" role="status" wire:loading wire:target="search,searchBy,stage,barangayId,resetFilters">Updating records...</span>
        </div>
    </section>

    <section class="staff-results-panel overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--white)]" aria-labelledby="registration-results-title" x-data="{ selected: [], printLayout: 'batch' }" @id-print-updated.window="selected = []" wire:key="registration-print-{{ md5(json_encode([$search, $searchBy, $stage, $barangayId, $this->getPage()])) }}">
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
                <details class="border-b border-[var(--line)] px-5" @if ($errors->hasAny(['senior_ids', 'senior_ids.*', 'paper', 'purpose', 'reviewed'])) open @endif>
                    <summary class="w-fit cursor-pointer rounded py-3 text-sm text-[var(--blue)] dark:text-blue-300">ID printing <span class="ml-2 text-[var(--muted)]" x-cloak x-show="selected.length" x-text="selected.length + ' of ' + (printLayout === 'single' ? 1 : 4) + ' selected'"></span></summary>
                    <form id="batch-id-print" method="POST" action="{{ route('senior-id.batch-print') }}" target="_blank" data-id-print data-confirm data-confirm-review data-confirm-action="Issue and print selected IDs" class="pt-1 pb-2">
                        @csrf
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="field-label w-64 max-w-full"><label data-select-label>Print layout</label><x-select label="Print layout" name="layout" x-model="printLayout" @change="selected = []"><option value="single">Single ID</option><option value="batch" selected>Batch (4 per A4/Letter page)</option></x-select></div>
                            <div class="field-label w-40 max-w-full"><label data-select-label>Paper size</label><x-select label="Paper size" name="paper"><option value="short">Short</option><option value="long">Long</option><option value="a4">A4</option></x-select>
                            </div>
                            <button type="submit" class="secondary-button shrink-0 disabled:cursor-not-allowed disabled:opacity-50" :disabled="selected.length === 0">Print selected IDs</button>
                        </div>
                        <p id="registration-print-help" class="mt-2 mb-2 text-sm text-[var(--muted)]">Maximum 4 IDs. Reprints require approval. Print at 100% and record each result.</p>
                        <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="reviewed" value="1" required class="mt-1 shrink-0"><span>I reviewed the selected records and verified that all card details are correct before issuance and printing. <span class="field-required" aria-hidden="true">*</span></span></label>
                        @error('reviewed')<p class="mt-2 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                    </form>
                </details>
            @endif
        <x-staff.table label="Senior citizen registrations" wire:loading.class="opacity-60" wire:target="search,searchBy,stage,barangayId,resetFilters">
            <table class="w-full">
                <thead>
                    <tr>
                        @if ($canPrint)<th scope="col" class="w-16">Print</th>@endif
                        <th scope="col" class="whitespace-nowrap">OSCA ID No.</th>
                        <th scope="col">Senior name</th>
                        <th scope="col">Barangay</th>
                        <th scope="col">Registered</th>
                        <th scope="col">Registration status</th>
                        <th scope="col">ID status</th>
                        <th scope="col" class="border-l border-[var(--line)] sm:sticky sm:right-0 sm:z-10">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registrations as $senior)
                        @php($printDisabled = $senior->status->value !== 'VERIFIED' || ! $senior->verified_at || $senior->died_on || $senior->death_declared_at || ! $senior->canGenerateIdPrint() || ($senior->id_prints_exists && ! $senior->latestIdPrintAttempt))
                        <tr class="group" wire:key="registration-record-{{ $senior->id }}">
                                @if ($canPrint)
                                    <td>
                                        <input type="checkbox" form="batch-id-print" name="senior_ids[]" value="{{ $senior->id }}" data-senior-name="{{ $senior->full_name }}" x-model="selected" @disabled($printDisabled) :disabled="{{ $printDisabled ? 'true' : 'false' }} || (selected.length >= (printLayout === 'single' ? 1 : 4) && !selected.includes($el.value))" class="size-4 cursor-pointer accent-[var(--blue)] disabled:cursor-not-allowed disabled:opacity-50" aria-label="Print ID for {{ $senior->full_name }}" aria-describedby="registration-print-help">
                                    </td>
                                @endif
                            <td class="whitespace-nowrap tabular-nums">{{ $senior->osca_id_number ?: 'Not issued' }}</td>
                            <td class="min-w-32 whitespace-normal! wrap-anywhere font-medium">{{ $senior->surname_first_name }}</td>
                            <td class="min-w-24 whitespace-normal! wrap-anywhere">{{ $senior->barangay->name }}</td>
                            <td class="whitespace-nowrap">{{ $senior->created_at->format('M j, Y') }}</td>
                            <td class="min-w-32 whitespace-normal!">
                                <div class="flex flex-col items-start gap-1">
                                    <span>{{ ucfirst(strtolower($senior->status->value)) }}</span>
                                    <x-milestone-badge :senior="$senior" />
                                </div>
                            </td>
                            <td><x-badge :status="$senior->idPrintingStatus()" />
                                @if ($senior->lastConfirmedIdPrint)<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $senior->lastConfirmedIdPrint->confirmed_at->format('M j, Y g:i A') }}<br>{{ $senior->lastConfirmedIdPrint->confirmedBy?->full_name }}</p>@endif
                            </td>
                            <td class="border-l border-[var(--line)] bg-[var(--white)] group-even:bg-[var(--paper)] sm:sticky sm:right-0 sm:z-10">
                                <div class="flex w-max max-w-52 flex-wrap gap-x-3 gap-y-0.5">
                                    <a class="text-button hover:underline" href="{{ route('senior-citizens.show', [$senior, 'from' => 'registrations', 'search' => $search, 'search_by' => $searchBy, 'stage' => $stage, 'barangay_id' => $barangayId, 'page' => $this->getPage()]) }}">View record</a>
                                    @if (auth()->user()->can('manageId', $senior) && in_array($senior->status->value, ['VERIFIED', 'ARCHIVED']))
                                        <button type="button" class="text-button hover:underline" wire:click="$dispatch('manage-id-print', { seniorId: {{ $senior->id }} })">Manage ID</button>
                                        <a class="text-button hover:underline" href="{{ route('senior-id.show', [$senior, 'from' => 'registrations', 'search' => $search, 'search_by' => $searchBy, 'stage' => $stage, 'barangay_id' => $barangayId, 'page' => $this->getPage()]) }}">{{ $senior->id_generated_at ? 'ID printing' : 'Prepare ID' }}</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canPrint ? 8 : 7 }}"><p class="py-6 text-center text-[var(--muted)]">No matching registration records.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-staff.table>
        @if ($registrations->hasPages())
            {{ $registrations->onEachSide(1)->links('components.pagination.livewire', ['label' => 'Registrations pagination', 'scrollTarget' => 'registration-results-title']) }}
        @endif
    </section>
    @if ($canPrint)<livewire:senior-citizens.id-print-management />@endif
</div>
