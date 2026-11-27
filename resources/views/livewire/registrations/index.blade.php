<div class="min-w-0" data-print-workspace x-data="{}" @focus.window="$wire.$refresh()">
    @php
        $canPrint = auth()->user()->can('configureId', \App\Models\SeniorCitizen::class);
        $hasFilters = $search !== '' || $searchBy !== 'all' || $stage !== '' || $barangayId !== '';
        $navigationFilters = ['from' => 'registrations', 'search' => $search, 'search_by' => $searchBy, 'stage' => $stage, 'barangay_id' => $barangayId, 'page' => $this->getPage()];
    @endphp
    <x-staff.page-header title="Registrations">
        @can('create', \App\Models\SeniorCitizen::class)
            <livewire:senior-citizens.application-launcher />
            <a class="secondary-button gap-2" href="{{ route('senior-id.reprint-approvals') }}">
                <x-admin.icon name="clipboard-list" size="16" />
                Reprint Approvals
            </a>
        @endcan
    </x-staff.page-header>

    <dl class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4" aria-label="Registration metrics">
        @foreach (['verified' => ['Approved / ID pending', 'clock-3'], 'issued' => ['ID issued', 'file-check-2'], 'inactive' => ['Inactive records', 'archive']] as $column => [$label, $icon])
            <div class="grid min-w-0 content-start gap-2 rounded-lg border border-[var(--line)] bg-[var(--white)] p-3 sm:p-4" wire:key="registration-metric-{{ $column }}">
                <dt class="flex items-start gap-2 text-sm font-medium text-[var(--muted)]">
                    <x-admin.icon :name="$icon" class="mt-0.5 shrink-0" />
                    {{ $label }}
                </dt>
                <dd class="text-2xl leading-none font-semibold tracking-tight tabular-nums wrap-anywhere sm:text-[2rem]">
                    {{ number_format($counts->{$column} ?? 0) }}
                </dd>
            </div>
        @endforeach
    </dl>

    <section class="staff-results-panel" aria-labelledby="registration-results-title">
        <div class="staff-panel-heading flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <h3 id="registration-results-title">Registration records</h3>
            <p class="mt-0! tabular-nums" role="status">
                @if ($registrations->total())
                    Showing {{ number_format($registrations->firstItem()) }}–{{ number_format($registrations->lastItem()) }} of {{ number_format($registrations->total()) }} results
                @else
                    0 results
                @endif
            </p>
        </div>
        <div class="border-b border-[var(--line)] px-4 py-3 sm:px-5" role="group" aria-label="Registration filters">
            <div class="grid items-end gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)]">
                <label class="field-label sm:col-span-2 xl:col-span-1">Search
                    <span class="relative">
                        <x-admin.icon name="search" size="17" class="pointer-events-none absolute inset-y-0 start-3 my-auto text-[var(--muted)]" />
                        <input wire:model.live.debounce.300ms="search" type="search" data-uppercase-search class="ps-9! uppercase placeholder:normal-case" maxlength="100" autocomplete="off" placeholder="OSCA ID No. or name">
                    </span>
                </label>
                <div class="field-label min-w-0">
                    <label data-select-label>Registration stage</label>
                    <x-select label="Registration stage" wire:model.live="stage">
                        <option value="">Active registrations</option>
                        <option value="VERIFIED">Awaiting ID issuance</option>
                        <option value="ISSUED">Awaiting ID printing</option>
                        <option value="HISTORY">ID printed</option>
                        <option value="INACTIVE">Inactive records</option>
                    </x-select>
                </div>
                <div class="field-label min-w-0">
                    <label data-select-label>Barangay</label>
                    <x-select label="Barangay" wire:model.live="barangayId" searchable>
                        <option value="">All barangays</option>
                        @foreach ($barangays as $barangay)
                            <option wire:key="registration-filter-barangay-{{ $barangay->id }}" value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>
            <div>
                @if ($hasFilters)
                    <button type="button" class="text-button mt-1 hover:underline" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters">Reset filters</button>
                @endif
                <span class="mt-1 text-sm text-[var(--muted)]" role="status" wire:loading wire:target="search,searchBy,stage,barangayId,resetFilters">Updating records...</span>
            </div>
        </div>
        <div x-data="{ selected: [], printLayout: 'batch' }" @id-print-updated.window="selected = []" wire:key="registration-print-{{ md5(json_encode([$search, $searchBy, $stage, $barangayId, $this->getPage()])) }}">
            @if ($canPrint)
                <details class="group border-b border-[var(--line)]" @if ($errors->hasAny(['senior_ids', 'senior_ids.*', 'layout', 'paper', 'purpose', 'reviewed'])) open @endif>
                    <summary class="flex min-h-11 w-full! list-none flex-wrap items-center justify-between gap-x-3 gap-y-1 px-4 py-2 text-sm hover:bg-[var(--paper)] focus-visible:outline-offset-[-2px]! sm:px-5 [&::-webkit-details-marker]:hidden">
                        <span class="inline-flex items-center gap-2 font-medium text-[var(--ink)]">
                            <x-lucide-printer width="16" height="16" class="shrink-0 text-[var(--muted)]" aria-hidden="true" />
                            ID printing
                        </span>
                        <span class="inline-flex items-center gap-2 text-xs text-[var(--muted)]">
                            <span class="tabular-nums" role="status" x-text="selected.length + ' of ' + (printLayout === 'single' ? 1 : 4) + ' selected'">0 of 4 selected</span>
                            <x-admin.icon name="chevron-down" size="16" class="group-open:rotate-180" />
                        </span>
                    </summary>
                    <form id="batch-id-print" method="POST" action="{{ route('senior-id.batch-print') }}" target="_blank" data-id-print data-confirm data-confirm-review data-confirm-action="Issue and print selected IDs" class="flex flex-col gap-3 border-t border-[var(--line)] bg-[var(--paper)] px-4 py-4 sm:px-5">
                        @csrf
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="field-label w-64 max-w-full"><label data-select-label>Print layout</label><x-select label="Print layout" name="layout" x-model="printLayout" @change="selected = []"><option value="single">Single ID</option><option value="batch" selected>Batch (4 per A4/Letter page)</option></x-select></div>
                            <div class="field-label w-40 max-w-full"><label data-select-label>Paper size</label><x-select label="Paper size" name="paper"><option value="short">Short</option><option value="long">Long</option><option value="a4">A4</option></x-select>
                            </div>
                            <button type="submit" class="primary-button shrink-0 gap-2" :disabled="selected.length === 0">
                                <x-lucide-printer width="16" height="16" aria-hidden="true" />
                                Print selected IDs
                            </button>
                        </div>
                        <p id="registration-print-help" class="text-sm text-[var(--muted)]">Select rows using the Print column. Reprints require approval. Print at 100% and record each result.</p>
                        <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="reviewed" value="1" required class="mt-1 shrink-0"><span>I reviewed the selected records and verified that all card details are correct before issuance and printing. <span class="field-required" aria-hidden="true">*</span></span></label>
                        @error('reviewed')<p class="mt-2 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                    </form>
                </details>
            @endif
            <x-staff.table label="Senior citizen registrations" wire:loading.class="opacity-60" wire:target="search,searchBy,stage,barangayId,resetFilters">
                <table class="w-full min-w-204! [&_td]:px-3.5! [&_td]:py-2.5! [&_td]:align-middle! [&_th]:px-3.5! [&_th]:py-2.5!">
                    <thead>
                        <tr>
                            @if ($canPrint)<th scope="col" class="w-14 text-center">Print</th>@endif
                            <th scope="col">Senior name <span class="font-normal">/ OSCA ID No.</span></th>
                            <th scope="col">Barangay</th>
                            <th scope="col">Registered</th>
                            <th scope="col">Registration status</th>
                            <th scope="col">ID status</th>
                            <th scope="col" class="border-l border-[var(--line)] xl:sticky xl:right-0 xl:z-10">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($registrations as $senior)
                            @php
                                $registrationIcon = match ($senior->status->value) {
                                    'VERIFIED' => 'user-round-check',
                                    'ARCHIVED' => 'archive',
                                    'DECEASED' => 'user-round-minus',
                                    'TRANSFERRED' => 'arrow-right',
                                    default => 'clock-3',
                                };
                                $idStatus = $senior->idPrintingStatus();
                                $idIcon = match ($idStatus) {
                                    'Printed' => 'check',
                                    'Approved' => 'file-check-2',
                                    'Rejected' => 'x',
                                    default => 'clock-3',
                                };
                            @endphp
                            <tr class="group focus-within:bg-blue-50/60 hover:bg-blue-50/60 dark:focus-within:bg-blue-950/30 dark:hover:bg-blue-950/30" wire:key="registration-record-{{ $senior->id }}">
                                @if ($canPrint)
                                    @php($printDisabled = $senior->status->value !== 'VERIFIED' || ! $senior->verified_at || $senior->died_on || $senior->death_declared_at || ! $senior->canGenerateIdPrint() || ($senior->id_prints_exists && ! $senior->latestIdPrintAttempt))
                                    <td class="text-center">
                                        <input type="checkbox" form="batch-id-print" name="senior_ids[]" value="{{ $senior->id }}" data-senior-name="{{ $senior->full_name }}" x-model="selected" @disabled($printDisabled) :disabled="{{ $printDisabled ? 'true' : 'false' }} || (selected.length >= (printLayout === 'single' ? 1 : 4) && !selected.includes($el.value))" class="size-4 cursor-pointer accent-[var(--blue)] disabled:cursor-not-allowed disabled:opacity-50" aria-label="Print ID for {{ $senior->full_name }}" aria-describedby="registration-print-help">
                                    </td>
                                @endif
                                <td class="min-w-42 whitespace-normal!">
                                    <span class="block wrap-anywhere font-semibold">{{ $senior->surname_first_name }}</span>
                                    <span class="block text-xs text-[var(--muted)] tabular-nums">{{ $senior->osca_id_number ?: 'Not issued' }}</span>
                                </td>
                                <td class="min-w-24 whitespace-normal! wrap-anywhere">{{ $senior->barangay->name }}</td>
                                <td class="whitespace-nowrap tabular-nums">{{ $senior->created_at->format('M j, Y') }}</td>
                                <td class="min-w-36 whitespace-normal!">
                                    <div class="flex flex-col items-start gap-0.5">
                                        <span class="inline-flex items-center gap-1.5 font-medium">
                                            <x-admin.icon :name="$registrationIcon" size="15" class="shrink-0 text-[var(--muted)]" />
                                            {{ ucfirst(strtolower($senior->status->value)) }}
                                        </span>
                                        <x-milestone-badge :senior="$senior" compact />
                                    </div>
                                </td>
                                <td>
                                    <span class="ui-label gap-1.5">
                                        <x-admin.icon :name="$idIcon" size="14" class="shrink-0 text-[var(--muted)]" />
                                        {{ ucfirst(strtolower($idStatus)) }}
                                    </span>
                                    @if ($senior->lastConfirmedIdPrint)<p class="mt-1 whitespace-normal text-xs text-[var(--muted)]">{{ $senior->lastConfirmedIdPrint->confirmed_at->format('M j, Y g:i A') }}<br>{{ $senior->lastConfirmedIdPrint->confirmedBy?->full_name }}</p>@endif
                                </td>
                                <td class="w-44 border-l border-[var(--line)] bg-[var(--white)] group-even:bg-[var(--paper)] xl:sticky xl:right-0 xl:z-10">
                                    <div class="flex w-max flex-col items-start">
                                        <a class="text-button min-h-7! font-semibold! hover:underline" href="{{ route('senior-citizens.show', [$senior, ...$navigationFilters]) }}" aria-label="View record for {{ $senior->full_name }}">View record <x-admin.icon name="chevron-right" size="14" /></a>
                                        @if (auth()->user()->can('manageId', $senior) && in_array($senior->status->value, ['VERIFIED', 'ARCHIVED']))
                                            <div class="flex gap-2.5">
                                                <button type="button" class="text-button min-h-7! text-xs! text-[var(--muted)]! hover:text-[var(--blue)]! hover:underline dark:hover:text-blue-300!" wire:click="$dispatch('manage-id-print', { seniorId: {{ $senior->id }} })" aria-label="Manage ID for {{ $senior->full_name }}">Manage ID</button>
                                                <a class="text-button min-h-7! text-xs! text-[var(--muted)]! hover:text-[var(--blue)]! hover:underline dark:hover:text-blue-300!" href="{{ route('senior-id.show', [$senior, ...$navigationFilters]) }}">{{ $senior->id_generated_at ? 'ID printing' : 'Prepare ID' }}</a>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canPrint ? 7 : 6 }}">
                                    <p class="flex items-center gap-2 py-4 text-[var(--muted)]">
                                        <x-admin.icon name="search" size="18" />
                                        No matching registration records.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-staff.table>
        </div>
        @if ($registrations->hasPages())
            {{ $registrations->onEachSide(1)->links('components.pagination.livewire', ['label' => 'Registrations pagination', 'scrollTarget' => 'registration-results-title']) }}
        @endif
    </section>
    @if ($canPrint)<livewire:senior-citizens.id-print-management />@endif
</div>
