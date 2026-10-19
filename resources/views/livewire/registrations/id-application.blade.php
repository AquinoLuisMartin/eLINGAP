<div @class(['fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/50 p-3 sm:p-6' => $modal])
    x-data="{
        draftKey: {{ \Illuminate\Support\Js::from('elingap-id-names-'.auth()->id()) }},
        nameFields: ['first_name', 'middle_name', 'last_name', 'name_suffix'],
        clearNames() {
            try { sessionStorage.removeItem(this.draftKey); } catch {}
        },
        restoreNames() {
            try {
                const draft = JSON.parse(sessionStorage.getItem(this.draftKey) || '{}');
                for (const field of this.nameFields) {
                    if (typeof draft[field] === 'string') this.$wire.$set('form.' + field, draft[field].toUpperCase().slice(0, field === 'name_suffix' ? 20 : 100), false);
                }
            } catch {}
        },
        saveNames(event) {
            if (!this.nameFields.includes(event.target.dataset.draftName)) return;
            try {
                const draft = {};
                for (const field of this.nameFields) draft[field] = this.$wire.form[field];
                draft[event.target.dataset.draftName] = event.target.value;
                sessionStorage.setItem(this.draftKey, JSON.stringify(draft));
            } catch {}
        }
    }" x-init="restoreNames()" @input="saveNames($event)" @registration-cancelled.window="clearNames()"
    @if ($modal) role="dialog" aria-modal="true" aria-labelledby="id-application-title" x-trap.inert.noscroll="true" @keydown.escape.window="$wire.cancel()" @endif>
    <div class="mx-auto w-full max-w-4xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900" @if ($modal) style="max-height: 90dvh; overflow-y: auto" @endif>
        <header class="flex items-start justify-between gap-4 border-b border-slate-200 p-5 sm:p-6 dark:border-slate-700">
            <div><p class="section-kicker">Central senior registration</p><h2 id="id-application-title" class="mt-1 text-xl font-semibold sm:text-2xl">New ID Application</h2><p class="mt-2 max-w-xl text-sm text-slate-500">Check requirements, register the senior, and continue to ID preparation.</p></div>
            <button type="button" wire:click="cancel" class="icon-button shrink-0" aria-label="Close ID application"><x-admin.icon name="x" size="20" /></button>
        </header>
        <nav aria-label="ID application progress" class="border-b border-slate-200 px-3 py-5 sm:px-8 dark:border-slate-700">
            <ol class="flex max-w-2xl gap-2">
                @foreach ([0 => 'Requirements', 1 => 'Personal Info', 2 => 'Address & Applicant', 3 => 'Confirm & Save'] as $number => $label)
                    <li wire:key="registration-step-{{ $number }}" class="relative flex flex-1 flex-col items-center gap-2 text-center" @if ($step === $number) aria-current="step" @endif>
                        @if ($number < 3)<span aria-hidden="true" @class(['absolute left-1/2 top-5 h-px w-full', 'bg-osca-primary' => $step > $number, 'bg-slate-200 dark:bg-slate-700' => $step <= $number])></span>@endif
                        <span @class(['relative z-10 flex size-10 items-center justify-center rounded-full border-2 text-sm font-medium', 'border-osca-primary bg-osca-primary text-white' => $step >= $number, 'border-slate-200 bg-white text-slate-400 dark:border-slate-700 dark:bg-slate-900' => $step < $number])>{{ $number + 1 }}</span>
                        <span @class(['relative px-1 text-xs font-semibold sm:text-sm', 'text-osca-primary dark:text-blue-300' => $step === $number, 'text-slate-500' => $step !== $number])>{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        </nav>
        <form wire:submit="{{ $step === 3 ? 'submit' : 'next' }}" novalidate>
            <div class="space-y-5 p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="text-lg font-semibold">{{ [0 => 'Check requirements first', 1 => 'Personal information', 2 => 'Address and applicant', 3 => 'Documents and confirmation'][$step] }}</h3><p class="text-xs text-slate-500">Step {{ $step + 1 }} of 4</p></div>
                @if ($step === 0)
                    <p class="max-w-2xl text-sm text-slate-500">Check the presented documents before entering the senior's information. Flag a record only when a document needs further review.</p>
                    <fieldset class="space-y-3">
                        <legend class="mb-3 text-sm font-semibold">Document checks</legend>
                        @foreach (['identity' => 'Identity and age requirement checked', 'residency' => 'Residency requirement checked', 'authorization' => 'Representative authorization checked (when applicable)'] as $key => $label)
                            <div wire:key="requirements-check-{{ $key }}"><label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="form.requirements_checks.{{ $key }}" class="mt-1"> {{ $label }}</label>@error('form.requirements_checks.'.$key)<p role="alert" class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror</div>
                        @endforeach
                    </fieldset>
                    <x-registration-field name="form.requirements_status" label="Document assessment" required>
                        <select id="form-requirements_status" wire:model.live="form.requirements_status" class="w-full sm:max-w-md"><option value="">Select assessment</option><option value="CHECKED">Requirements checked - ready to register</option><option value="REVIEW">Needs review - document concern</option></select>
                    </x-registration-field>
                    @if (($form['requirements_status'] ?? '') === 'REVIEW')
                        <x-registration-field name="form.review_reason" label="Reason for review" required hint="Explain which document needs checking or correction."><textarea id="form-review_reason" wire:model="form.review_reason" rows="3" minlength="5" maxlength="500" class="w-full max-w-2xl"></textarea></x-registration-field>
                    @endif
                @elseif ($step === 1)
                    <p class="text-sm text-slate-500">Enter the senior's details as shown on their identification documents. Applicants must be at least 60 years old.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach (['first_name' => 'First name', 'last_name' => 'Last name', 'middle_name' => 'Middle name', 'name_suffix' => 'Name suffix'] as $field => $label)
                            <x-registration-field wire:key="personal-field-{{ $field }}" :name="'form.'.$field" :label="$label" :required="in_array($field, ['first_name', 'last_name'])"><input id="form-{{ $field }}" data-draft-name="{{ $field }}" wire:model="form.{{ $field }}" class="uppercase" maxlength="{{ $field === 'name_suffix' ? 20 : 100 }}" autocomplete="{{ ['first_name' => 'given-name', 'last_name' => 'family-name', 'middle_name' => 'additional-name'][$field] ?? 'off' }}"></x-registration-field>
                        @endforeach
                        <x-registration-field name="form.birth_date" label="Birth date" required>
                            <input id="form-birth_date" type="date" wire:model.live="form.birth_date" max="{{ today()->subYears(60)->toDateString() }}" aria-describedby="registration-age">
                            <span id="registration-age" class="text-sm font-normal" role="status" aria-live="polite">
                                @if ($this->age !== null)
                                    Age: {{ $this->age }} years.
                                    @if ($this->age >= 60) Meets the 60-year age requirement.
                                    @else The applicant must be at least 60 years old. @endif
                                @else
                                    Enter a valid birth date to calculate the senior's age.
                                @endif
                            </span>
                        </x-registration-field>
                        <x-registration-field name="form.sex" label="Sex" required><select id="form-sex" wire:model="form.sex"><option value="">Select sex</option><option value="MALE">Male</option><option value="FEMALE">Female</option><option value="OTHER">Other</option></select></x-registration-field>
                        <x-registration-field name="form.civil_status" label="Civil status"><select id="form-civil_status" wire:model="form.civil_status"><option value="">Select civil status</option><option value="SINGLE">Single</option><option value="MARRIED">Married</option><option value="WIDOWED">Widowed</option></select></x-registration-field>
                    </div>
                @elseif ($step === 2)
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-registration-field name="form.barangay_id" label="Barangay" required><select id="form-barangay_id" wire:model="form.barangay_id"><option value="">Select barangay</option>@foreach ($barangays as $barangay)<option wire:key="registration-barangay-{{ $barangay->id }}" value="{{ $barangay->id }}">{{ $barangay->name }}</option>@endforeach</select></x-registration-field>
                        <x-registration-field name="form.contact_number" label="Senior mobile number" required hint="Enter 10 digits starting with 9. The +63 country code is included."><x-senior-mobile-input id="form-contact_number" wire:model="form.contact_number" required /></x-registration-field>
                        <x-registration-field name="form.address" label="House and street address" required class="sm:col-span-2"><textarea id="form-address" class="resize-none uppercase" wire:model="form.address" maxlength="1000" rows="2" autocomplete="street-address"></textarea></x-registration-field>
                        <x-registration-field name="form.applicant_type" label="Who is applying?" required class="sm:col-span-2"><select id="form-applicant_type" wire:model.live="form.applicant_type"><option value="SELF">Senior applying personally</option><option value="REPRESENTATIVE">Authorized representative</option></select></x-registration-field>
                    </div>
                    @if ($form['applicant_type'] === 'SELF')<label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="representativeContact"> Add an authorized representative for SMS or future claiming</label>@endif
                    @if ($representativeContact || $this->needsRepresentative())
                        <fieldset class="space-y-4 rounded-lg border border-slate-200 p-4 dark:border-slate-700"><legend class="px-2 text-sm font-semibold">Authorized representative</legend><p class="text-sm text-slate-500">Authorization proof will be uploaded in the next step.</p><div class="grid gap-4 sm:grid-cols-2">
                            @foreach (['representative_name' => 'Full name', 'representative_relationship' => 'Relationship to senior', 'representative_contact' => 'Mobile number'] as $field => $label)
                                <x-registration-field wire:key="representative-field-{{ $field }}" :name="'form.'.$field" :label="$label" required>
                                    @if ($field === 'representative_contact')
                                        <x-senior-mobile-input id="form-representative_contact" wire:model="form.representative_contact" required />
                                    @else
                                        <input id="form-{{ $field }}" wire:model="form.{{ $field }}" class="uppercase" maxlength="{{ $field === 'representative_name' ? 180 : 100 }}">
                                    @endif
                                </x-registration-field>
                            @endforeach
                        </div></fieldset>
                    @endif
                @else
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-800">
                        <h3 class="font-semibold">Application summary</h3><dl class="mt-3 grid gap-3 sm:grid-cols-2"><div><dt class="text-slate-500">Senior</dt><dd>{{ $form['first_name'] }} {{ $form['middle_name'] }} {{ $form['last_name'] }} {{ $form['name_suffix'] }}</dd></div><div><dt class="text-slate-500">Birth date</dt><dd>{{ $form['birth_date'] }}</dd></div><div><dt class="text-slate-500">Barangay</dt><dd>{{ $barangays->firstWhere('id', (int) $form['barangay_id'])?->name }}</dd></div><div><dt class="text-slate-500">Applicant</dt><dd>{{ $form['applicant_type'] === 'REPRESENTATIVE' ? $form['representative_name'] : 'Senior personally' }}</dd></div></dl>
                    </div>
                    <p class="text-sm text-slate-500">No photo upload is needed. Attach the senior's printed 1 x 1 inch photo to the ID after printing.</p>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-registration-field name="identificationFile" label="Supporting identification" hint="Optional identification or birth-date proof. PDF, JPEG or PNG, up to 5 MB."><input id="identificationFile" type="file" wire:model="identificationFile" accept=".pdf,.jpg,.jpeg,.png"></x-registration-field>
                        @if ($this->needsRepresentative())<x-registration-field name="authorizationFile" label="Proof of authorization" required hint="Required for the recorded representative. PDF, JPEG or PNG, up to 5 MB." class="sm:col-span-2"><input id="authorizationFile" type="file" wire:model="authorizationFile" accept=".pdf,.jpg,.jpeg,.png"></x-registration-field>@endif
                    </div>
                    <p wire:loading wire:target="authorizationFile,identificationFile" class="text-sm text-slate-500" role="status">Uploading document...</p>
                    <p class="text-sm text-slate-500">{{ $form['requirements_status'] === 'CHECKED' ? 'Requirements checked. Saving verifies this registration and continues to ID preparation.' : 'Needs review: '.$form['review_reason'].'. ID issuance stays blocked until the concern is resolved.' }}</p>
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="informationReviewed" class="mt-1"> <span>I confirmed that the entered information matches the presented documents and the assessment above.</span></label>
                    @error('informationReviewed')<p class="text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                @endif
            </div>
            <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6 dark:border-slate-700 dark:bg-slate-800">
                <button type="button" class="secondary-button" wire:click="{{ $step > 0 ? 'back' : 'cancel' }}" wire:loading.attr="disabled">{{ $step > 0 ? 'Back' : 'Cancel' }}</button>
                <div class="flex flex-wrap items-center gap-3"><span wire:loading wire:target="next,submit" class="text-sm text-slate-500" role="status">{{ $step === 3 ? 'Saving application...' : 'Checking details...' }}</span><button type="submit" class="primary-button disabled:cursor-not-allowed disabled:opacity-50" wire:loading.attr="disabled" @disabled($step === 1 && ($this->age === null || $this->age < 60))>{{ $step === 3 ? ($form['requirements_status'] === 'CHECKED' ? 'Save and prepare ID' : 'Save for review') : 'Continue' }}<x-admin.icon name="chevron-right" size="16" /></button></div>
            </footer>
        </form>
    </div>
</div>
