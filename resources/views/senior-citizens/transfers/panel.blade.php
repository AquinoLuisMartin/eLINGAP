<x-benefit-panel class="p-4!">
    <h3 class="text-lg font-semibold">Transfer history</h3>
    <div class="mt-4 space-y-4">
        @forelse ($seniorCitizen->transfers->sortByDesc('id') as $transfer)
            <article class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                <p class="font-medium">{{ $transfer->transferred_on->format('M j, Y') }} - {{ $transfer->type === 'EXTERNAL' ? 'External transfer' : 'Internal transfer' }}</p>
                <dl class="mt-3 space-y-2 text-sm">
                    <div><dt class="text-slate-500">Requested by</dt><dd>{{ $transfer->requester_name }} ({{ $transfer->requested_by === 'SELF' ? 'Senior citizen' : $transfer->requester_relationship }})</dd></div>
                    <div><dt class="text-slate-500">Previous location</dt><dd>{{ $transfer->previous_location }}</dd></div>
                    <div><dt class="text-slate-500">Destination</dt><dd>{{ $transfer->destination }}</dd></div>
                    @if ($transfer->reason)<div><dt class="text-slate-500">Reason</dt><dd>{{ $transfer->reason }}</dd></div>@endif
                    <div><dt class="text-slate-500">Recorded by</dt><dd>{{ $transfer->recorder->employee_id }} on {{ $transfer->created_at->format('M j, Y g:i A') }}</dd></div>
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
    @php($currentLocation = \App\Services\SeniorCitizens\TransferAddress::current($seniorCitizen->address, $seniorCitizen->barangay?->name, $officeDetails))
    <x-benefit-panel class="p-4!" x-data="transferConfirmation({{ \Illuminate\Support\Js::from(['currentLocation' => $currentLocation, 'seniorName' => $seniorCitizen->full_name, 'municipality' => $officeDetails['municipality'], 'province' => $officeDetails['province'], 'timezone' => config('app.timezone')]) }})" @pageshow.window="resetSubmission()">
        <h3 class="text-lg font-semibold">Record transfer</h3>
        <p class="mt-2 text-sm text-slate-500">An external transfer cancels the local registration and stops new benefit assignments, ID printing and QR validation. Existing history is retained.</p>
        <form method="POST" action="{{ route('senior-citizens.transfers.store', $seniorCitizen) }}" x-ref="transferForm" @submit="reviewTransfer($event)" class="mt-4 flex flex-col gap-6" x-data="{ type: {{ \Illuminate\Support\Js::from(old('type', 'INTERNAL')) }}, requester: {{ \Illuminate\Support\Js::from(old('requested_by', 'SELF')) }} }">
            @csrf
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="field-label"><label data-select-label>Transfer type</label><x-select label="Transfer type" name="type" x-model="type" required><option value="INTERNAL">Within the municipality</option><option value="EXTERNAL">Outside the municipality</option></x-select></div>
                <div class="field-label"><label data-select-label>Requested by</label><x-select label="Requested by" name="requested_by" x-model="requester" required><option value="SELF">Senior citizen</option><option value="OTHER">Authorized person</option></x-select></div>
            </div>
            <div class="grid gap-6 sm:grid-cols-2" x-show="requester === 'OTHER'" x-cloak>
                <label class="field-label">Requester name<input name="requester_name" value="{{ old('requester_name') }}" maxlength="180" :required="requester === 'OTHER'"></label>
                <label class="field-label">Relationship to senior<input name="requester_relationship" value="{{ old('requester_relationship') }}" maxlength="100" :required="requester === 'OTHER'"></label>
            </div>
            <p class="text-sm text-slate-500">Current location: {{ $currentLocation }}</p>
            <section aria-labelledby="transfer-destination-title" class="flex min-w-0 flex-col gap-6">
                <h4 id="transfer-destination-title" class="flex items-center gap-2 border-b border-slate-200 pb-3 text-base font-semibold dark:border-slate-700">Destination Address</h4>
                <div x-show="type === 'INTERNAL'">
                    <div class="field-label" :data-field-required="type === 'INTERNAL' ? 'true' : 'false'"><label data-select-label>Barangay</label><x-select label="Barangay" name="barangay_id" ::required="type === 'INTERNAL'"><option value="">Select barangay</option>@foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(old('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</x-select></div>
                </div>
                <div class="grid min-w-0 gap-6 md:grid-cols-3" x-show="type === 'EXTERNAL'" x-cloak>
                    @foreach (['destination_municipality' => 'Municipality / City', 'destination_province' => 'Province', 'destination_barangay' => 'Barangay'] as $field => $label)
                        <label class="field-label min-w-0">{{ $label }}<input name="{{ $field }}" value="{{ old($field) }}" maxlength="100" :required="type === 'EXTERNAL'"></label>
                    @endforeach
                </div>
                <div class="min-w-0">
                    <label class="field-label">House / Street / Purok / Sitio / Subdivision<textarea name="destination_address" data-uppercase required maxlength="1000" aria-describedby="transfer-address-help">{{ old('destination_address') }}</textarea></label>
                    <p id="transfer-address-help" class="mt-1.5 flex items-start gap-1.5 text-[12px] leading-relaxed text-slate-500"><span aria-hidden="true" class="mt-0.5 shrink-0"><x-admin.icon name="info" size="14" /></span></p>
                </div>
            </section>
            <label class="field-label">Reason (optional)<textarea name="reason" maxlength="1000">{{ old('reason') }}</textarea></label>
            <div class="flex justify-end">
                <button type="submit" class="primary-button" :disabled="submitting">Record transfer</button>
            </div>
        </form>
        @include('senior-citizens.transfers.confirmation')
    </x-benefit-panel>
@endcan
