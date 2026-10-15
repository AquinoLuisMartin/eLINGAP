<x-benefit-panel class="p-4!">
    <h3 class="text-lg font-semibold">Transfer history</h3>
    <div class="mt-4 space-y-4">
        @forelse ($seniorCitizen->transfers->sortByDesc('id') as $transfer)
            <article class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                <p class="font-medium">{{ $transfer->transferred_on->format('M j, Y') }} - {{ $transfer->type === 'EXTERNAL' ? 'Outside Santa Maria' : 'Within Santa Maria' }}</p>
                <dl class="mt-3 space-y-2 text-sm">
                    <div><dt class="text-slate-500">Requested by</dt><dd>{{ $transfer->requester_name }} ({{ $transfer->requested_by === 'SELF' ? 'Senior citizen' : $transfer->requester_relationship }})</dd></div>
                    <div><dt class="text-slate-500">Previous location</dt><dd>{{ $transfer->previous_location }}</dd></div>
                    <div><dt class="text-slate-500">Destination</dt><dd>{{ $transfer->destination }}</dd></div>
                    @if ($transfer->reason)<div><dt class="text-slate-500">Reason</dt><dd>{{ $transfer->reason }}</dd></div>@endif
                    <div><dt class="text-slate-500">Recorded by</dt><dd>{{ $transfer->recorder->username }} on {{ $transfer->created_at->format('M j, Y g:i A') }}</dd></div>
                </dl>
                @if ($transfer->type === 'EXTERNAL')
                    @can('manageId', $seniorCitizen)
                        <form method="POST" action="{{ route('senior-citizens.transfers.certificate', [$seniorCitizen, $transfer->id]) }}" target="_blank" class="mt-4">@csrf<button type="submit" class="secondary-button">Certificate of Cancellation</button></form>
                    @endcan
                @endif
            </article>
        @empty
            <p class="text-sm text-slate-500">No transfers recorded.</p>
        @endforelse
    </div>
</x-benefit-panel>
@can('transfer', $seniorCitizen)
    <x-benefit-panel class="p-4!">
        <h3 class="text-lg font-semibold">Record transfer</h3>
        <p class="mt-2 text-sm text-slate-500">A transfer outside Santa Maria cancels the local registration and stops new benefit assignments, ID printing and QR validation. Existing history is retained.</p>
        <form method="POST" action="{{ route('senior-citizens.transfers.store', $seniorCitizen) }}" class="mt-4 space-y-4" x-data="{ type: {{ \Illuminate\Support\Js::from(old('type', 'INTERNAL')) }}, requester: {{ \Illuminate\Support\Js::from(old('requested_by', 'SELF')) }} }">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="field-label">Transfer type<select name="type" x-model="type" required><option value="INTERNAL">Within Santa Maria</option><option value="EXTERNAL">Outside Santa Maria</option></select></label>
                <label class="field-label">Requested by<select name="requested_by" x-model="requester" required><option value="SELF">Senior citizen</option><option value="OTHER">Another person</option></select></label>
            </div>
            <div class="grid gap-4 sm:grid-cols-2" x-show="requester === 'OTHER'" x-cloak>
                <label class="field-label">Requester name<input name="requester_name" value="{{ old('requester_name') }}" maxlength="180" :required="requester === 'OTHER'"></label>
                <label class="field-label">Relationship to senior<input name="requester_relationship" value="{{ old('requester_relationship') }}" maxlength="100" :required="requester === 'OTHER'"></label>
            </div>
            <p class="text-sm text-slate-500">Previous location: {{ $seniorCitizen->address }}, {{ $seniorCitizen->barangay->name }}, Santa Maria, Bulacan</p>
            <div x-show="type === 'INTERNAL'">
                <label class="field-label">Destination barangay<select name="barangay_id" :required="type === 'INTERNAL'"><option value="">Select barangay</option>@foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(old('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select></label>
            </div>
            <div class="grid gap-4 sm:grid-cols-3" x-show="type === 'EXTERNAL'" x-cloak>
                @foreach (['destination_municipality' => 'Destination municipality / city', 'destination_province' => 'Province', 'destination_barangay' => 'Barangay'] as $field => $label)
                    <label class="field-label">{{ $label }}<input name="{{ $field }}" value="{{ old($field) }}" maxlength="100" :required="type === 'EXTERNAL'"></label>
                @endforeach
            </div>
            <label class="field-label">Destination house / street address<textarea name="destination_address" required maxlength="1000">{{ old('destination_address') }}</textarea></label>
            <label class="field-label">Transfer date<input type="date" name="transferred_on" value="{{ old('transferred_on', today()->toDateString()) }}" min="{{ $seniorCitizen->created_at->toDateString() }}" max="{{ today()->toDateString() }}" required></label>
            <label class="field-label">Reason (optional)<textarea name="reason" maxlength="1000">{{ old('reason') }}</textarea></label>
            <button type="submit" class="primary-button">Record transfer</button>
        </form>
    </x-benefit-panel>
@endcan
