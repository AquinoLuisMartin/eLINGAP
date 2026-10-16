<div class="mx-auto max-w-6xl space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <x-record-return-link :senior="$senior" />
            <h2 class="mt-3 text-3xl font-medium text-osca-primary dark:text-slate-100">Senior citizen ID</h2>
            <p class="mt-2 text-slate-500">{{ $senior->full_name }} <span class="ml-2">{{ $senior->osca_id_number ?? $senior->registration_number }}</span></p>
        </div>
        @can('configureId', \App\Models\SeniorCitizen::class)
            <button type="button" wire:click="$toggle('officialsOpen')" class="secondary-button" aria-controls="id-official-settings" aria-expanded="{{ $officialsOpen ? 'true' : 'false' }}">Official card settings</button>
        @endcan
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
    @if ($notice)<p role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ $notice }}</p>@endif
    <x-benefit-errors />
    <nav aria-label="ID preparation progress" class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
        <ol class="grid gap-4 sm:grid-cols-3">
            @foreach ([1 => 'Physical ID preparation', 2 => 'Review and issuance', 3 => 'Print ID'] as $number => $label)
                <li wire:key="id-step-{{ $number }}" @if($step === $number) aria-current="step" @endif class="flex items-center gap-3 text-sm {{ $step === $number ? 'text-osca-primary dark:text-blue-300' : 'text-slate-500' }}">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full border {{ $step === $number ? 'border-osca-primary bg-osca-primary text-white' : 'border-slate-200' }}">{{ $number }}</span>{{ $label }}
                </li>
            @endforeach
        </ol>
    </nav>
    @if (!$card)
        <x-senior-id.panel title="ID unavailable" description="This record is deceased or transferred. Its QR no longer verifies as valid, and the ID cannot be printed." />
    @elseif ($step === 1)
        <x-senior-id.panel title="Physical photo and thumbprint" description="The PDF leaves a square 1 x 1 inch (2.54 x 2.54 cm) photo placeholder. Attach the physical photograph and collect the thumbprint after printing.">
            <p class="text-sm text-slate-500">No digital photo or senior signature is required. Official signatures are managed through Official card settings.</p>
        </x-senior-id.panel>
        <footer class="flex justify-end"><button type="button" wire:click="next" wire:loading.attr="disabled" class="primary-button">Continue to review</button></footer>
    @elseif ($step === 2)
        <x-senior-id.panel title="Review the card" description="Check the spelling, address, birthdate and photo placeholder against the approved record. Previewing does not issue an ID.">
            <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div><dt class="text-sm text-slate-500">Full name</dt><dd class="mt-1">{{ $senior->full_name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Birthdate / age today</dt><dd class="mt-1">{{ $senior->birth_date->format('M j, Y') }} / {{ $card['age'] }}</dd></div>
                <div><dt class="text-sm text-slate-500">Original issuance date</dt><dd class="mt-1">{{ $senior->id_generated_at?->format('M j, Y') ?? 'Assigned on issuance' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Address</dt><dd class="mt-1">{{ $senior->address }}, {{ $senior->barangay->name }}, Santa Maria, Bulacan</dd></div>
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
        </x-senior-id.panel>
        <footer class="flex flex-wrap justify-between gap-3">
            <button type="button" wire:click="back" class="secondary-button">Back to preparation</button>
            @if ($senior->id_generated_at && $senior->verified_at && in_array($senior->status->value, ['VERIFIED', 'ARCHIVED']))<button type="button" wire:click="prepareExisting" wire:loading.attr="disabled" class="primary-button">Confirm and continue to print</button>
            @elseif ($senior->status->value === 'VERIFIED')<button type="button" wire:click="issue" wire:loading.attr="disabled" class="primary-button">Issue OSCA ID</button>
            @else<p class="text-sm text-amber-800">Staff must verify the registration before ID issuance.</p>@endif
        </footer>
    @else
        <x-senior-id.panel title="Print the ID" description="Front and back are placed side by side. Select up to four records in the masterlist for a full sheet. Cut and join them before lamination. Print at actual size (100%) and turn off fit-to-page.">
            <form method="POST" action="{{ route('senior-id.print', $senior) }}" target="_blank" class="space-y-6">
                @csrf
                <div class="grid gap-6 sm:grid-cols-2">
                    <label for="id-paper" class="block text-sm">Paper size<select id="id-paper" name="paper" class="mt-2 block w-full"><option value="short">Short (8.5 x 11 inches)</option><option value="long">Long (8.5 x 13 inches)</option><option value="a4">A4 (210 x 297 mm)</option></select></label>
                    <label for="id-purpose" class="block text-sm">Print purpose<select id="id-purpose" name="purpose" class="mt-2 block w-full"><option value="FIRST_PRINT" @selected(!$existingId)>First print</option><option value="REPRINT" @selected($existingId)>Reprint</option><option value="REPLACEMENT">Replacement</option></select></label>
                </div>
                <p class="text-sm text-slate-500">Card: 85.60 x 53.98 mm per side. Reprints and replacements keep the same number, QR and original issuance date. Age is calculated when the PDF is generated.</p>
                <button class="primary-button" @disabled(!$credentialExists)>Open print-ready PDF</button>
            </form>
        </x-senior-id.panel>
        <button type="button" wire:click="back" class="secondary-button">Back to review</button>
    @endif
    <x-senior-id.panel title="ID activity" description="Latest 20 actions. PDF generation is recorded separately from physical printing.">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th scope="col" class="pb-3 pr-4 font-normal">Date</th><th scope="col" class="pb-3 pr-4 font-normal">Action</th><th scope="col" class="pb-3 font-normal">Recorded by</th></tr></thead><tbody>
            @forelse ($history as $event)<tr wire:key="id-event-{{ $event->id }}" class="border-b last:border-0"><td class="py-3 pr-4">{{ $event->created_at->format('M j, Y g:i A') }}</td><td class="py-3 pr-4">{{ \App\Support\SystemEventLabel::for($event->action) }}@if($event->action === 'senior_citizen.id_print_generated')<p class="text-xs text-slate-500">{{ ucfirst(strtolower($event->new_values['purpose'] ?? 'print')) }} / {{ strtoupper($event->new_values['paper'] ?? '') }}</p>@endif</td><td class="py-3">{{ $event->user?->username ?? 'System' }}</td></tr>@empty<tr><td colspan="3" class="py-6 text-slate-500">No ID activity recorded.</td></tr>@endforelse
        </tbody></table></div>
    </x-senior-id.panel>
</div>
