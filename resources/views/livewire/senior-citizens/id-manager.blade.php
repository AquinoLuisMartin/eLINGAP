<div class="mx-auto w-full min-w-0 max-w-6xl space-y-5">
    <header class="space-y-3">
        <a href="{{ route(request('from') === 'registrations' ? 'registrations.index' : 'senior-citizens.index', request()->only(request('from') === 'registrations' ? ['search', 'search_by', 'stage', 'barangay_id', 'page'] : ['search', 'search_by', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])) }}" x-on:click="if (window.history.length > 1) { $event.preventDefault(); window.history.back(); }" class="text-button text-sm">&larr; Back</a>
        <div class="flex flex-col items-start gap-4 md:flex-row md:justify-between">
            <div class="min-w-0">
                <h2 class="text-2xl font-medium text-osca-primary dark:text-slate-100">Senior citizen ID</h2>
                <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm"><span class="min-w-0 wrap-anywhere font-medium text-slate-800 dark:text-slate-100">{{ $senior->full_name }}</span><span class="ui-label tabular-nums">{{ $senior->osca_id_number ? 'OSCA ID '.$senior->osca_id_number : 'OSCA ID not issued' }}</span></p>
            </div>
            @can('configureId', \App\Models\SeniorCitizen::class)
                <button type="button" wire:click="$toggle('officialsOpen')" class="secondary-button shrink-0" aria-controls="id-official-settings" aria-expanded="{{ $officialsOpen ? 'true' : 'false' }}"><x-admin.icon name="settings" size="16" /> Official card settings</button>
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
                <a wire:key="id-review-document-{{ $document->id }}" href="{{ route('senior-citizens.document', [$senior, $document->id]) }}" class="secondary-button">{{ ucfirst(strtolower(str_replace('_', ' ', $document->document_type))) }}</a>
            @empty
                <p class="text-sm text-slate-500">No supporting documents uploaded. Check the presented originals.</p>
            @endforelse
        </div></details>
    @endif
    @if ($notice)<x-feedback>{{ $notice }}</x-feedback>@endif
    @if ($card && ! $card['address']['fits'])
        <p role="alert" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">The address is too long to fit legibly on the ID. Review the address in the senior record before printing.</p>
    @endif
    <nav aria-label="ID preparation progress" class="rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
        <ol class="grid gap-2 md:grid-cols-3">
            @foreach ([1 => 'Physical ID preparation', 2 => 'Review and issuance', 3 => 'Print ID'] as $number => $label)
                <li wire:key="id-step-{{ $number }}" class="min-w-0">
                    <button type="button" wire:click="goToStep({{ $number }})" wire:loading.attr="disabled" wire:target="goToStep" @if($step === $number) aria-current="step" @endif @class(['flex min-h-11 w-full cursor-pointer items-center gap-3 rounded-lg p-2 text-left text-sm transition-colors hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary disabled:cursor-wait dark:hover:bg-slate-800 dark:focus-visible:outline-blue-300', 'bg-blue-50 font-medium text-osca-primary dark:bg-slate-800 dark:text-blue-300' => $step === $number, 'text-slate-700 dark:text-slate-200' => $step > $number, 'text-slate-500 dark:text-slate-400' => $step < $number])>
                        <span @class(['flex size-8 shrink-0 items-center justify-center rounded-full text-sm', 'bg-osca-primary text-white' => $step === $number, 'text-osca-primary ring-2 ring-osca-primary ring-inset dark:text-blue-300 dark:ring-blue-300' => $step > $number, 'ring-1 ring-slate-300 ring-inset dark:ring-slate-600' => $step < $number])>@if ($step > $number)<x-admin.icon name="check" size="16" /><span class="sr-only">Completed:</span>@else{{ $number }}@endif</span><span class="min-w-0 wrap-anywhere">{{ $label }}</span>
                    </button>
                </li>
            @endforeach
        </ol>
    </nav>
    @if (!$card)
        <x-senior-id.panel title="ID unavailable" description="This record is deceased or transferred. Its QR no longer verifies as valid, and the ID cannot be printed." />
    @elseif ($step === 1)
        <x-senior-id.panel title="Physical photo and thumbprint" description="Complete these on the printed card. No digital photo or senior signature is required.">
            <ul class="grid gap-3 lg:grid-cols-3">
                @foreach (['Photo' => 'Attach a physical photo to the square 1 x 1 inch (2.54 x 2.54 cm) placeholder.', 'Thumbprint' => 'Collect the senior\'s thumbprint after printing.', 'Official signatures' => 'Printed from Official card settings.'] as $item => $detail)
                    <li wire:key="id-preparation-{{ $loop->index }}" class="min-w-0 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800"><p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $item }}</p><p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $detail }}</p></li>
                @endforeach
            </ul>
            <div class="mt-5 flex justify-end border-t border-slate-200 pt-4 dark:border-slate-700"><button type="button" wire:click="next" wire:loading.attr="disabled" class="primary-button w-full sm:w-auto">Continue to review</button></div>
        </x-senior-id.panel>
    @elseif ($step === 2)
        <x-senior-id.panel title="Review the card" description="Check the spelling, address, birthdate and photo placeholder against the approved record. Previewing does not issue an ID.">
            <dl class="grid gap-6 wrap-anywhere sm:grid-cols-2 lg:grid-cols-3">
                <div><dt class="text-sm text-slate-500">Full name</dt><dd class="mt-1">{{ $senior->full_name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Birthdate / age today</dt><dd class="mt-1">{{ $senior->birth_date->format('M j, Y') }} / {{ $card['age'] }}</dd></div>
                <div><dt class="text-sm text-slate-500">Original issuance date</dt><dd class="mt-1">{{ $senior->id_generated_at?->format('M j, Y') ?? 'Assigned on issuance' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Address</dt><dd class="mt-1">{{ implode(', ', array_filter([$senior->address, $senior->barangay->name, $officeDetails['municipality'], $officeDetails['province']])) }}</dd></div>
                <div><dt class="text-sm text-slate-500">Registration</dt><dd class="mt-1">{{ ucfirst(strtolower($senior->status->value)) }}</dd></div>
            </dl>
            @include('senior-citizens.ids.styles')
            <div class="id-workspace-preview mt-6 grid gap-6 md:grid-cols-2" aria-label="Front and back preview">
                <figure class="min-w-0 overflow-x-auto"><figcaption class="mb-3 text-sm text-slate-500">Front</figcaption><x-senior-id.front :card="$card" /></figure>
                <figure class="min-w-0 overflow-x-auto"><figcaption class="mb-3 text-sm text-slate-500">Back</figcaption><x-senior-id.back :card="$card" /></figure>
            </div>
            @if (empty($card['officials']['osca_head_name']) || empty($card['officials']['municipal_mayor_name']) || !$card['seal'])
                <p class="mt-4 text-sm text-amber-800 dark:text-amber-200">Official names or the municipal seal are missing. Open Official card settings above to complete them here.</p>
            @endif
            <label class="mt-6 flex items-start gap-3 text-sm"><input type="checkbox" wire:model="reviewed" class="mt-1"> I reviewed the card preview and confirmed the details for issuance or reprinting.</label>
            @error('reviewed')<p class="mt-2 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
            <div class="mt-5 grid gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:flex sm:flex-wrap sm:items-center sm:justify-between">
                <button type="button" wire:click="back" class="secondary-button">Back to preparation</button>
                @if ($senior->id_generated_at && $senior->verified_at && in_array($senior->status->value, ['VERIFIED', 'ARCHIVED']))<button type="button" wire:click="prepareExisting" wire:loading.attr="disabled" class="primary-button">Confirm and continue to print</button>
                @elseif ($senior->status->value === 'VERIFIED')<button type="button" wire:click="issue" wire:loading.attr="disabled" class="primary-button">Issue OSCA ID</button>
                @else<p class="text-sm text-amber-800 dark:text-amber-200">Staff must verify the registration before ID issuance.</p>@endif
            </div>
        </x-senior-id.panel>
    @else
        <x-senior-id.panel title="Print the ID" description="Front and back are placed side by side. Select up to four records in Registrations for a full sheet. Cut and join them before lamination. Print at actual size (100%) and turn off fit-to-page.">
            <form method="POST" action="{{ route('senior-id.print', $senior) }}" target="_blank" class="grid gap-6">
                @csrf
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="field-label block text-sm"><label data-select-label>Paper size</label><x-select label="Paper size" id="id-paper" name="paper" class="mt-2 block w-full"><option value="short">Short (8.5 x 11 inches)</option><option value="long">Long (8.5 x 13 inches)</option><option value="a4">A4 (210 x 297 mm)</option></x-select></div>
                    <div class="field-label block text-sm"><label data-select-label>Print purpose</label><x-select label="Print purpose" id="id-purpose" name="purpose" class="mt-2 block w-full"><option value="FIRST_PRINT" @selected(!$existingId)>First print</option><option value="REPRINT" @selected($existingId)>Reprint</option><option value="REPLACEMENT">Replacement</option></x-select></div>
                </div>
                <p class="text-sm text-slate-500">Card: 85.60 x 53.98 mm per side. Reprints and replacements keep the same number, QR and original issuance date. Age is calculated when the PDF is generated.</p>
                <div class="grid gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:flex sm:flex-wrap sm:items-center sm:justify-between">
                    <button type="button" wire:click="back" class="secondary-button">Back to review</button>
                    <button type="submit" class="primary-button" @disabled(!$credentialExists)><x-admin.icon name="download" size="16" /> Open print-ready PDF</button>
                </div>
            </form>
        </x-senior-id.panel>
    @endif
    <x-senior-id.panel title="ID activity" description="Latest 20 actions. PDF generation is recorded separately from physical printing.">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th scope="col" class="pb-3 pr-4 font-normal">Date</th><th scope="col" class="pb-3 pr-4 font-normal">Action</th><th scope="col" class="pb-3 font-normal">Recorded by</th></tr></thead><tbody>
            @forelse ($history as $event)<tr wire:key="id-event-{{ $event->id }}" class="border-b last:border-0"><td class="py-3 pr-4">{{ $event->created_at->format('M j, Y g:i A') }}</td><td class="py-3 pr-4">{{ \App\Support\SystemEventLabel::for($event->action) }}@if($event->action === 'senior_citizen.id_print_generated')<p class="text-xs text-slate-500">{{ ucfirst(strtolower($event->new_values['purpose'] ?? 'print')) }} / {{ strtoupper($event->new_values['paper'] ?? '') }}</p>@endif</td><td class="py-3">{{ $event->user?->employee_id ?? 'System' }}</td></tr>@empty<tr><td colspan="3" class="py-6 text-slate-500">No ID activity recorded.</td></tr>@endforelse
        </tbody></table></div>
    </x-senior-id.panel>
</div>
