<div data-print-workspace class="w-full min-w-0 space-y-4" x-data="{ printReadyMessage: '' }" @focus.window="$wire.$refresh()" @id-ready-to-print.window="printReadyMessage = $event.detail.message; $refs.printReady.showModal()">
    <dialog x-ref="printReady" class="confirm-dialog modal-card" aria-labelledby="print-ready-title" aria-describedby="print-ready-message" wire:ignore>
        <form method="dialog">
            <div class="flex items-center gap-3">
                <x-admin.icon name="check" size="22" class="shrink-0 text-[var(--muted)]" />
                <h2 id="print-ready-title">Ready to print</h2>
            </div>
            <p id="print-ready-message" x-text="printReadyMessage"></p>
            <div class="modal-actions"><button type="submit" class="primary-button" autofocus>Continue to print</button></div>
        </form>
    </dialog>
    <header class="flex flex-col items-start gap-2">
        <a href="{{ route(request('from') === 'registrations' ? 'registrations.index' : 'senior-citizens.index', request()->only(request('from') === 'registrations' ? ['search', 'search_by', 'stage', 'barangay_id', 'page'] : ['search', 'search_by', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])) }}" x-on:click="if (window.history.length > 1) { $event.preventDefault(); window.history.back(); }" class="text-button gap-1.5 hover:underline">
            <x-admin.icon name="chevron-left" size="16" />
            Back
        </a>
        <div class="flex w-full flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-tight text-[var(--ink)]">Senior citizen ID</h1>
                <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm">
                    <span class="min-w-0 wrap-anywhere font-medium text-[var(--ink)]">{{ $senior->full_name }}</span>
                    <span class="ui-label tabular-nums">{{ $senior->osca_id_number ? 'OSCA ID '.$senior->osca_id_number : 'OSCA ID not issued' }}</span>
                </p>
            </div>
            @can('configureId', \App\Models\SeniorCitizen::class)
                <button type="button" wire:click="$toggle('officialsOpen')" class="secondary-button shrink-0 gap-2" aria-controls="id-official-settings" aria-expanded="{{ $officialsOpen ? 'true' : 'false' }}"><x-admin.icon name="settings" size="16" /> Official card settings</button>
            @endcan
        </div>
    </header>
    @if ($officialsOpen)
        <section id="id-official-settings" class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
            <livewire:senior-citizens.id-official-settings :embedded="true" />
        </section>
    @endif
    @if ($senior->status->value === 'PENDING')
        <livewire:senior-citizens.requirements-review :senior-id="$senior->id" />
        <details class="rounded-lg border border-slate-200 p-4 dark:border-slate-700"><summary class="cursor-pointer text-sm font-medium">Supporting documents</summary><div class="mt-3 flex flex-wrap gap-3">
            @forelse ($documents as $document)
                <a wire:key="id-review-document-{{ $document->id }}" href="{{ route('senior-citizens.document', [$senior, $document->id]) }}" target="_blank" rel="noopener" class="secondary-button">{{ ucfirst(strtolower(str_replace('_', ' ', $document->document_type))) }}</a>
            @empty
                <p class="text-sm text-slate-500">No supporting documents uploaded. Check the presented originals.</p>
            @endforelse
        </div></details>
    @endif
    @if ($notice)<x-feedback>{{ $notice }}</x-feedback>@endif
    @if ($card && ! $card['address']['fits'])
        <p role="alert" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">The address is too long to fit legibly on the ID. Review the address in the senior record before printing.</p>
    @endif
    <nav aria-label="ID preparation progress" class="w-full rounded-lg border border-[var(--line)] bg-[var(--white)] px-3 sm:px-4">
        <ol class="flex gap-2 sm:gap-3">
            @foreach ([1 => 'Physical ID preparation', 2 => 'Review and issuance', 3 => 'Print ID'] as $number => $label)
                <li wire:key="id-step-{{ $number }}" class="min-w-0 flex-1">
                    <button type="button" wire:click="goToStep({{ $number }})" wire:loading.attr="disabled" wire:target="goToStep" @if($step === $number) aria-current="step" @endif @class(['flex h-full min-h-11 w-full cursor-pointer flex-col items-start justify-center gap-0.5 border-b-2 px-1.5 py-2 text-left text-sm transition-colors hover:bg-[var(--paper)] focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-[var(--blue)] disabled:cursor-wait sm:px-3', 'border-[var(--blue)] font-semibold text-[var(--blue)] dark:text-blue-300' => $step === $number, 'border-transparent font-medium text-[var(--muted)]' => $step !== $number])>
                        <span class="text-xs font-normal text-[var(--muted)]">Step {{ $number }}</span>
                        <span class="wrap-anywhere">{{ $label }}</span>
                    </button>
                </li>
            @endforeach
        </ol>
    </nav>
    @if (!$card)
        <x-senior-id.panel title="ID unavailable" description="This record is deceased or transferred. Its QR no longer verifies as valid, and the ID cannot be printed." />
    @elseif ($step === 1)
        <section class="min-w-0 rounded-lg border border-[var(--line)] bg-[var(--white)]" aria-labelledby="physical-preparation-title">
            <div class="staff-panel-heading">
                <h2 id="physical-preparation-title" class="text-lg font-semibold text-[var(--ink)]">Physical photo and thumbprint</h2>
                <p class="text-sm text-[var(--muted)]">Complete the photo and thumbprint after printing.</p>
            </div>
            <dl class="divide-y divide-[var(--line)] px-4 sm:px-5">
                @foreach (['Photo' => 'Attach a 1 × 1 inch (2.54 × 2.54 cm) photo to the square on the printed card.', 'Thumbprint' => 'Have the senior place their thumbprint in the space provided on the printed card.', 'Official signatures' => 'The card uses the signatures configured in Official card settings.'] as $item => $detail)
                    <div wire:key="id-preparation-{{ $loop->index }}" class="flex flex-col gap-1 py-2.5 text-sm sm:flex-row sm:gap-4 sm:py-3">
                        <dt class="font-medium leading-relaxed text-[var(--ink)] sm:w-36 sm:shrink-0">{{ $item }}</dt>
                        <dd class="m-0 min-w-0 max-w-prose leading-relaxed text-[var(--muted)]">{{ $detail }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="flex justify-end border-t border-[var(--line)] px-4 py-3 sm:px-5">
                <button type="button" wire:click="next" wire:loading.attr="disabled" class="primary-button w-full gap-2 sm:w-auto">
                    Continue to review <x-admin.icon name="arrow-right" size="16" />
                </button>
            </div>
        </section>
    @elseif ($step === 2)
        <x-senior-id.panel class="p-4! [&>div]:mt-3 [&>p]:max-w-none" title="Review the card">
            <dl class="grid gap-x-6 gap-y-3 wrap-anywhere text-sm text-(--navy) sm:grid-cols-2 lg:grid-cols-3 dark:text-slate-100">
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Full name</dt><dd class="mt-1 font-medium">{{ $senior->full_name }}</dd></div>
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Birth date</dt><dd class="mt-1 font-medium">{{ $senior->birth_date->format('M j, Y') }}</dd></div>
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Age today</dt><dd class="mt-1 font-medium">{{ $card['age'] }} years old</dd></div>
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Address</dt><dd class="mt-1 font-medium leading-relaxed">{{ mb_strtoupper(implode(', ', array_filter([$senior->address, $senior->barangay->name, $officeDetails['municipality'], $officeDetails['province']]))) }}</dd></div>
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Original issuance date</dt><dd class="mt-1 font-medium">{{ $senior->id_generated_at?->format('M j, Y') ?? 'Assigned on issuance' }}</dd></div>
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Registration status</dt><dd class="mt-1 font-medium">{{ ucfirst(strtolower($senior->status->value)) }}</dd></div>
            </dl>
            @include('senior-citizens.ids.styles')
            <div class="id-workspace-preview mt-4 grid gap-4 border-t border-slate-200 pt-4 dark:border-slate-700 lg:grid-cols-2" aria-label="Front and back preview">
                <figure class="min-w-0 overflow-x-auto"><figcaption class="mb-3 text-center text-sm font-semibold text-(--navy) dark:text-slate-100">Front of ID</figcaption><div class="mx-auto w-fit lg:[zoom:1.1]"><x-senior-id.front :card="$card" /></div></figure>
                <figure class="min-w-0 overflow-x-auto"><figcaption class="mb-3 text-center text-sm font-semibold text-(--navy) dark:text-slate-100">Back of ID</figcaption><div class="mx-auto w-fit lg:[zoom:1.1]"><x-senior-id.back :card="$card" /></div></figure>
            </div>
            @if (empty($card['officials']['osca_head_name']) || empty($card['officials']['municipal_mayor_name']))
                <p class="mt-4 text-sm text-amber-800 dark:text-amber-200">Open Official card settings above to add the missing official names.</p>
            @endif
            <label class="mt-6 flex cursor-pointer items-start gap-3 text-sm">
                <input type="checkbox" wire:model="reviewed" class="mt-1 shrink-0" aria-required="true" aria-describedby="card-review-description">
                <span class="flex min-w-0 flex-col gap-1">
                    <span class="font-semibold text-(--navy) dark:text-slate-100">Card details verified <span class="field-required" aria-hidden="true">*</span></span>
                    <span id="card-review-description" class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">I checked the front and back previews. The personal details and card information are correct.</span>
                </span>
            </label>
            @error('reviewed')<p class="mt-2 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
            <div class="mt-4 grid gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:flex sm:flex-wrap sm:items-center sm:justify-between">
                <button type="button" wire:click="back" class="secondary-button">Back to preparation</button>
                @if ($senior->id_generated_at && $senior->verified_at && in_array($senior->status->value, ['VERIFIED', 'ARCHIVED']))<button type="button" wire:click="prepareExisting" wire:loading.attr="disabled" class="primary-button">Continue to print</button>
                @elseif ($senior->status->value === 'VERIFIED')<button type="button" wire:click="issue" wire:loading.attr="disabled" class="primary-button">Issue ID and continue</button>
                @else<p class="text-sm text-amber-800 dark:text-amber-200">Staff must verify the registration before ID issuance.</p>@endif
            </div>
        </x-senior-id.panel>
    @else
        <x-senior-id.panel class="p-4! [&>div]:mt-3" title="Print the ID">
            <ol class="mb-4 grid gap-3 text-sm sm:grid-cols-3">
                <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800"><strong class="block font-semibold">1. Set up the printer</strong><span class="mt-1 block text-slate-500 dark:text-slate-400">Match the paper size. Use Actual Size (100%) and turn off Fit to page.</span></li>
                <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800"><strong class="block font-semibold">2. Print and check</strong><span class="mt-1 block text-slate-500 dark:text-slate-400">Front and back print side by side. Check both sides, then cut and join before lamination.</span></li>
                <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800"><strong class="block font-semibold">3. Record the result</strong><span class="mt-1 block text-slate-500 dark:text-slate-400">Select Printed successfully or Not printed for each ID, then save.</span></li>
            </ol>
            <div class="mb-4 flex flex-wrap items-center gap-3"><x-badge :status="$senior->idPrintingStatus()" /><button type="button" class="text-button" wire:click="$dispatch('manage-id-print', { seniorId: {{ $senior->id }} })">Manage ID</button></div>
            @if ($senior->lastConfirmedIdPrint)
                <div class="mb-3 flex flex-wrap items-center gap-3">
                    @if (! in_array($senior->latestIdReprintRequest?->status, ['PENDING', 'APPROVED']) && $senior->latestIdPrintAttempt?->status !== 'GENERATED')
                        <button type="button" class="secondary-button" wire:click="$dispatch('request-id-reprint', { seniorId: {{ $senior->id }} })">Request Reprint Approval</button>
                    @endif
                    <a class="text-button" href="{{ route('senior-id.reprint-approvals') }}">Reprint Approvals</a>
                </div>
            @endif
            @if ($printingLocked)
                <p class="text-sm text-slate-500 dark:text-slate-400">Printing is locked. {{ $senior->latestIdPrintAttempt?->status === 'GENERATED' ? 'Open Printing Completion to record the result of the current PDF before trying again.' : 'This ID has already been printed. Request reprint approval before printing another copy.' }}</p>
                @if ($senior->latestIdPrintAttempt?->status === 'GENERATED')<button type="button" class="secondary-button mt-3" @click="$dispatch('id-print-completion')">Printing Completion</button>@endif
            @else
            <form method="POST" action="{{ route('senior-id.print', $senior) }}" target="_blank" data-id-print data-confirm="The PDF will open in a new tab. Return here after printing to record the result." data-confirm-title="Open print-ready PDF?" data-confirm-label="Open PDF" class="grid gap-4">
                @csrf
                <div class="grid max-w-3xl gap-4 sm:grid-cols-2">
                    <div class="ui-field"><label for="id-paper" data-select-label class="text-sm text-slate-600 dark:text-slate-300">Paper size</label><x-select label="Paper size" id="id-paper" name="paper" class="w-full"><option value="short">Short (8.5 x 11 inches)</option><option value="long">Long (8.5 x 13 inches)</option><option value="a4">A4 (210 x 297 mm)</option></x-select></div>
                    <div class="flex flex-col gap-1 text-sm"><span class="text-slate-500 dark:text-slate-400">Print purpose</span><span class="font-medium">{{ $senior->lastConfirmedIdPrint ? 'Authorized reprint' : 'First print' }}</span></div>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">Card size: 85.60 x 53.98 mm.</p>
                <div class="grid gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:flex sm:flex-wrap sm:items-center sm:justify-between">
                    <button type="button" wire:click="back" class="secondary-button">Back to review</button>
                    <button type="submit" class="primary-button" @disabled(!$credentialExists)>Open print-ready PDF</button>
                </div>
            </form>
            @endif
        </x-senior-id.panel>
    @endif
    <livewire:senior-citizens.id-print-management :initial-senior-id="$printingLocked && $senior->latestIdPrintAttempt?->status !== 'GENERATED' ? $senior->id : null" />
    <livewire:senior-citizens.request-id-reprint />
</div>
