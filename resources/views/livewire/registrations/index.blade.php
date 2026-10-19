<div class="space-y-6">
    <section class="welcome-row"><div><p class="section-kicker">Senior ID applications</p><h2 class="text-2xl font-semibold">Applications and Registration</h2><p class="section-subtitle">One central registration, from application review to OSCA ID issuance.</p></div>@can('create', \App\Models\SeniorCitizen::class)<div class="flex flex-wrap gap-3"><a href="{{ route('registrations.import.create') }}" class="secondary-button">Import records</a><button type="button" class="primary-button" wire:click="apply"><x-admin.icon name="plus" size="17" /> New ID Application</button></div>@endcan</section>
    <div class="grid max-w-3xl gap-3 sm:grid-cols-3">
        @foreach (['PENDING' => ['Needs review', 'pending'], 'VERIFIED' => ['Verified / ID pending', 'verified'], 'ISSUED' => ['ID issued', 'issued']] as $value => [$label, $column])
            <button type="button" wire:key="registration-stage-{{ $value }}" wire:click="$set('stage', '{{ $value }}')" @class(['flex flex-col items-start gap-2 rounded-xl border border-slate-200 bg-white p-4 text-left font-normal shadow-sm dark:border-slate-700 dark:bg-slate-900', 'ring-2 ring-osca-primary' => $stage === $value]) aria-pressed="{{ $stage === $value ? 'true' : 'false' }}"><span class="text-sm font-normal text-slate-500">{{ $label }}</span><span class="text-2xl font-medium">{{ number_format($counts->{$column} ?? 0) }}</span></button>
        @endforeach
    </div>
    <x-benefit-panel>
        <div class="registration-filters mb-4 grid gap-3 sm:grid-cols-3">
            <label class="field-label">Search name or ID<input wire:model.live.debounce.300ms="search" type="search" maxlength="100" placeholder="Registration number, name or OSCA ID"></label>
            <label class="field-label">Application stage<select wire:model.live="stage"><option value="">All records</option><option value="PENDING">Needs review</option><option value="VERIFIED">Verified / ID pending</option><option value="ISSUED">ID issued</option></select></label>
            <label class="field-label">Barangay<select wire:model.live="barangayId"><option value="">All barangays</option>@foreach ($barangays as $barangay)<option wire:key="registration-filter-barangay-{{ $barangay->id }}" value="{{ $barangay->id }}">{{ $barangay->name }}</option>@endforeach</select></label>
        </div>
        <p wire:loading wire:target="search,stage,barangayId" class="mb-3 text-sm text-slate-500" role="status">Updating applications...</p>
        <div class="table-scroll"><table><thead><tr><th scope="col">Registration / Senior</th><th scope="col">Barangay</th><th scope="col">Applicant</th><th scope="col">Submitted</th><th scope="col">Stage</th><th scope="col">Action</th></tr></thead><tbody>
            @forelse ($registrations as $senior)
                <tr wire:key="registration-record-{{ $senior->id }}"><td>{{ $senior->full_name }}<p class="text-xs text-slate-500">{{ $senior->registration_number }}</p></td><td>{{ $senior->barangay->name }}</td><td>{{ $senior->applicant_type === 'REPRESENTATIVE' ? 'Authorized representative' : 'Senior personally' }}</td><td>{{ $senior->created_at->format('M j, Y') }}</td><td>@if ($senior->status->value === 'VERIFIED'){{ $senior->id_generated_at ? 'ID issued' : 'Verified / ID pending' }}@elseif ($senior->status->value === 'PENDING') Needs review @else {{ ucfirst(strtolower($senior->status->value)) }} @endif</td><td><a class="text-button" href="{{ route(auth()->user()->can('manageId', $senior) && in_array($senior->status->value, ['PENDING', 'VERIFIED']) ? 'senior-id.show' : 'senior-citizens.show', [$senior, 'from' => 'registrations', 'search' => $search, 'stage' => $stage, 'barangay_id' => $barangayId, 'page' => $this->getPage()]) }}">{{ auth()->user()->can('manageId', $senior) ? ($senior->status->value === 'PENDING' ? 'Review requirements' : ($senior->status->value === 'VERIFIED' ? ($senior->id_generated_at ? 'Reprint ID' : 'Prepare ID') : 'View record')) : 'View record' }}</a></td></tr>
            @empty
                <tr><td colspan="6"><p class="py-5 text-center text-slate-500">No matching ID applications. Adjust your filters.@can('create', \App\Models\SeniorCitizen::class) You can also start a new application.@endcan</p></td></tr>
            @endforelse
        </tbody></table></div>
        <div class="mt-4">{{ $registrations->links() }}</div>
    </x-benefit-panel>
    @if ($applicationOpen)<livewire:registrations.id-application :modal="true" :key="'new-id-application'" />@endif
</div>
