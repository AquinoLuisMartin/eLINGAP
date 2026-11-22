<div @class(['fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/50 p-3 sm:p-6' => $modal])
    x-data="{}" x-init="try { sessionStorage.removeItem({{ \Illuminate\Support\Js::from('elingap-id-names-'.auth()->id()) }}); } catch {}"
    @if ($modal) role="dialog" aria-modal="true" aria-labelledby="id-application-title" x-trap.inert.noscroll="true" @keydown.escape.window="$wire.cancel()" @endif>
    <div @class(['w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900', 'mx-auto max-w-4xl' => $modal, 'min-w-0 max-w-full' => ! $modal]) @if ($modal) style="max-height: 90dvh; overflow-y: auto" @endif>
        <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-4 py-4 sm:px-5 dark:border-slate-700">
            <div><h2 id="id-application-title" class="text-xl font-semibold sm:text-2xl">New ID Application</h2></div>
            <button type="button" wire:click="cancel" class="icon-button shrink-0" aria-label="Close ID application"><x-admin.icon name="x" size="20" /></button>
        </header>
        <nav aria-label="ID application progress" class="border-b border-slate-200 px-4 py-3 sm:px-5 dark:border-slate-700">
            <ol class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([0 => 'Requirements', 1 => 'Personal Info', 2 => 'Address & Contact', 3 => 'Confirm & Save'] as $number => $label)
                    <li wire:key="registration-step-{{ $number }}" @class(['border-b-2 pb-2', 'border-osca-primary' => $step === $number, 'border-slate-200 dark:border-slate-700' => $step !== $number]) @if ($step === $number) aria-current="step" @endif>
                        <button type="button" wire:click="goToStep({{ $number }})" @disabled($number !== $step && ! $this->canVisitStep($number)) wire:loading.attr="disabled" class="w-full text-left disabled:cursor-not-allowed">
                            <span class="flex items-center gap-1.5 text-xs text-slate-500">Step {{ $number + 1 }} @if (!empty($completedSteps[$number]))<span class="text-green-600 dark:text-green-400" aria-label="Completed"><x-admin.icon name="check" size="14" /></span>@endif</span>
                            <span @class(['mt-1 block text-sm font-medium', 'text-osca-primary dark:text-blue-300' => $step === $number, 'text-slate-500' => $step !== $number])>{{ $label }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </nav>
        <form wire:submit="{{ $step === 3 ? 'submit' : 'next' }}" autocomplete="off" novalidate @if ($step === 3) data-confirm data-confirm-review data-confirm-action="Save & Prepare ID" data-confirm-record="{{ trim($form['first_name'].' '.$form['middle_name'].' '.$form['last_name']) }}" @endif>
            <div class="flex flex-col gap-3 px-4 py-4 sm:px-5">
                <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="text-lg font-semibold">{{ [0 => 'Check requirements first', 1 => 'Personal information', 2 => 'Address and contact details', 3 => 'Documents and confirmation'][$step] }}</h3><p class="text-xs text-slate-500">Step {{ $step + 1 }} of 4</p></div>
                @if ($step === 0)

                    <fieldset>
                        <legend class="mb-2 text-base font-semibold text-slate-900 dark:text-slate-100">Document checks</legend>
                        <div class="flex flex-col gap-3">
                        @foreach ([
                            'identity' => ['Birth Certificate / Proof of Age', 'Verify that the applicant is at least 60 years old.'],
                            'residency' => ['Valid Government-Issued ID', "Must show the applicant's current address in Santa Maria, Bulacan."],
                            'authorization' => ['Authorization Letter & Representative ID (Optional / If applicable)', 'Required only if an authorized representative is applying on behalf of the senior citizen.'],
                        ] as $key => [$title, $description])
                            <div wire:key="requirements-check-{{ $key }}">
                                <label class="flex cursor-pointer items-start gap-3 text-sm">
                                    <input type="checkbox" wire:model.live="form.requirements_checks.{{ $key }}" data-error-display="outline" aria-invalid="{{ $errors->has('form.requirements_checks.'.$key) ? 'true' : 'false' }}" aria-required="{{ $key !== 'authorization' ? 'true' : 'false' }}" class="mt-1 shrink-0">
                                    <span class="flex flex-col gap-1"><span class="font-semibold text-slate-900 dark:text-slate-100">{{ $title }}@if ($key !== 'authorization')<span class="field-required" aria-hidden="true">*</span>@endif</span><span class="text-slate-500">{{ $description }}</span></span>
                                </label>
                            </div>
                        @endforeach
                        </div>
                    </fieldset>
                @elseif ($step === 1)
                    <p class="max-w-2xl text-sm text-slate-500">Applicants must be at least 60 years old.</p>
                    <div class="grid items-start gap-x-4 gap-y-3 sm:grid-cols-2">
                        @foreach (['first_name' => 'First name', 'last_name' => 'Last name', 'middle_name' => 'Middle name', 'name_suffix' => 'Name suffix'] as $field => $label)
                            <x-registration-field wire:key="personal-field-{{ $field }}" :name="'form.'.$field" :label="$label" :required="in_array($field, ['first_name', 'last_name'])"><input id="form-{{ $field }}" data-senior-name wire:model="form.{{ $field }}" class="uppercase" pattern="[\p{L}\p{M} ]+" title="Letters and spaces only" maxlength="{{ $field === 'name_suffix' ? 20 : 100 }}" autocomplete="off"></x-registration-field>
                        @endforeach
                        <x-registration-field name="form.birth_date" label="Birth date" required>
                            <input id="form-birth_date" type="date" wire:model.live="form.birth_date" max="{{ today()->subYears(60)->toDateString() }}" aria-describedby="registration-age">
                            <span id="registration-age" class="field-helper" role="status" aria-live="polite">
                                @if ($this->age !== null)
                                    Age: {{ $this->age }} years.
                                    @if ($this->age >= 60) Meets the 60-year age requirement.
                                    @else The applicant must be at least 60 years old. @endif
                                @else
                                    Enter a valid birth date to calculate the senior's age.
                                @endif
                            </span>
                        </x-registration-field>
                        <x-registration-field name="form.sex" label="Sex" required><x-select id="form-sex" wire:model="form.sex"><option value="">Select sex</option><option value="MALE">Male</option><option value="FEMALE">Female</option></x-select></x-registration-field>
                        <x-registration-field name="form.civil_status" label="Civil status"><x-select id="form-civil_status" wire:model="form.civil_status"><option value="">Select civil status</option><option value="SINGLE">Single</option><option value="MARRIED">Married</option><option value="WIDOWED">Widowed</option></x-select></x-registration-field>
                    </div>
                @elseif ($step === 2)
                    <div class="grid items-start gap-x-4 gap-y-3 sm:grid-cols-2">
                        <x-registration-field name="form.barangay_id" label="Barangay" required><x-select id="form-barangay_id" wire:model="form.barangay_id"><option value="">Select barangay</option>@foreach ($barangays as $barangay)<option wire:key="registration-barangay-{{ $barangay->id }}" value="{{ $barangay->id }}">{{ $barangay->name }}</option>@endforeach</x-select></x-registration-field>
                        <x-registration-field name="form.contact_number" label="Mobile number" required><x-senior-mobile-input id="form-contact_number" wire:model="form.contact_number" :spaced="false" fixed-local required /></x-registration-field>
                        <x-registration-field name="locality.municipality" label="Municipality"><input id="locality-municipality" value="{{ $office['municipality'] ?? 'Not configured' }}" readonly disabled tabindex="-1" aria-readonly="true" class="pointer-events-none cursor-not-allowed select-none bg-slate-100! text-slate-600! opacity-100! dark:bg-slate-800! dark:text-slate-300!"></x-registration-field>
                        <x-registration-field name="locality.province" label="Province"><input id="locality-province" value="{{ $office['province'] ?? 'Not configured' }}" readonly disabled tabindex="-1" aria-readonly="true" class="pointer-events-none cursor-not-allowed select-none bg-slate-100! text-slate-600! opacity-100! dark:bg-slate-800! dark:text-slate-300!"></x-registration-field>
                        <x-registration-field name="form.address" label="House No. / Street / Purok / Sitio / Subdivision" required class="sm:col-span-2"><textarea id="form-address" class="resize-none uppercase" wire:model="form.address" maxlength="1000" rows="2" autocomplete="off"></textarea></x-registration-field>
                    </div>
                    @if ($this->needsRepresentative())
                        <section aria-labelledby="registration-representative-title" class="min-w-0 flex flex-col gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                            <h3 id="registration-representative-title" class="text-lg font-semibold">Authorized Representative Information</h3>
                            <div class="grid items-start gap-x-4 gap-y-3 sm:grid-cols-2">
                            @foreach (['representative_name' => 'Representative full name', 'representative_relationship' => 'Relationship to senior', 'representative_contact' => 'Representative mobile number'] as $field => $label)
                                <x-registration-field wire:key="representative-field-{{ $field }}" :name="'form.'.$field" :label="$label" required>
                                    @if ($field === 'representative_contact')
                                        <x-senior-mobile-input id="form-representative_contact" wire:model="form.representative_contact" :spaced="false" fixed-local required />
                                    @else
                                        <input id="form-{{ $field }}" wire:model="form.{{ $field }}" class="uppercase" maxlength="{{ $field === 'representative_name' ? 180 : 100 }}">
                                    @endif
                                </x-registration-field>
                            @endforeach
                            </div>
                        </section>
                    @endif
                @else
                    @php
                        $mobileNumber = preg_replace('/^\+63(9[0-9]{2})([0-9]{3})([0-9]{4})$/', '(+63) $1-$2-$3', \App\Services\SeniorCitizens\SeniorRegistrationInput::normalize(['contact_number' => $form['contact_number']])['contact_number']);
                    @endphp
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-800">
                        <h3 class="font-semibold">Application summary</h3>
                        <dl class="mt-3 grid gap-x-4 gap-y-3 sm:grid-cols-2">
                            @foreach (['first_name' => 'First name', 'last_name' => 'Last name', 'middle_name' => 'Middle name', 'name_suffix' => 'Name suffix', 'birth_date' => 'Birth date', 'sex' => 'Sex', 'civil_status' => 'Civil status', 'contact_number' => 'Mobile number'] as $field => $label)
                                <div wire:key="application-summary-{{ $field }}" class="min-w-0"><dt class="text-slate-500">{{ $label }}</dt><dd class="whitespace-pre-line wrap-anywhere">{{ $field === 'contact_number' ? $mobileNumber : (filled($form[$field]) ? $form[$field] : 'Not provided') }}</dd></div>
                            @endforeach
                            <div class="min-w-0"><dt class="text-slate-500">Barangay</dt><dd class="wrap-anywhere">{{ mb_strtoupper($barangays->firstWhere('id', (int) $form['barangay_id'])?->name ?? '') }}</dd></div>
                            <div class="min-w-0"><dt class="text-slate-500">Municipality</dt><dd class="wrap-anywhere">{{ filled($office['municipality']) ? mb_strtoupper($office['municipality']) : 'Not configured' }}</dd></div>
                            <div class="min-w-0"><dt class="text-slate-500">Province</dt><dd class="wrap-anywhere">{{ filled($office['province']) ? mb_strtoupper($office['province']) : 'Not configured' }}</dd></div>
                            <div class="min-w-0 sm:col-span-2"><dt class="text-slate-500">Complete address</dt><dd class="whitespace-pre-line wrap-anywhere">{{ mb_strtoupper(collect([$form['address'], $barangays->firstWhere('id', (int) $form['barangay_id'])?->name, $office['municipality'], $office['province']])->filter(fn ($part) => filled($part))->implode(', ')) }}</dd></div>
                        </dl>
                        <dl class="mt-4 grid gap-x-4 gap-y-3 border-t border-slate-200 pt-4 sm:grid-cols-2 dark:border-slate-700">
                            @foreach (['identity' => 'Birth Certificate / Proof of Age', 'residency' => 'Valid Government-Issued ID', 'authorization' => 'Authorization Letter & Representative ID'] as $check => $label)
                                <div wire:key="application-summary-check-{{ $check }}" class="min-w-0"><dt class="text-slate-500">{{ $label }}</dt><dd>{{ $form['requirements_checks'][$check] ? 'Checked' : 'Not applicable' }}</dd></div>
                            @endforeach
                        </dl>
                    </div>
                    @if ($this->needsRepresentative())
                        <section class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <h3 class="font-semibold">Authorized Representative Information</h3>
                            <dl class="mt-3 grid gap-x-4 gap-y-3 sm:grid-cols-2">
                                @foreach (['representative_name' => 'Representative full name', 'representative_relationship' => 'Relationship to senior', 'representative_contact' => 'Representative mobile number'] as $field => $label)
                                    <div wire:key="representative-summary-{{ $field }}" class="min-w-0"><dt class="text-slate-500">{{ $label }}</dt><dd class="wrap-anywhere">{{ $form[$field] }}</dd></div>
                                @endforeach
                            </dl>
                        </section>
                    @endif
                    <section aria-labelledby="final-verification-title" class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-1">
                            <h3 id="final-verification-title" class="font-semibold text-(--navy) dark:text-slate-100">Final Verification</h3>

                        </div>
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" wire:model="informationReviewed" class="mt-1 shrink-0" aria-required="true" aria-describedby="application-verification-description">
                            <span class="flex min-w-0 flex-col gap-1">
                                <span class="font-semibold text-(--navy) dark:text-slate-100">Confirm Application Verification</span>
                                <span id="application-verification-description" class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">I confirm that the applicant's information is accurate and matches the verified supporting documents.</span>
                            </span>
                        </label>
                        @error('informationReviewed')<p class="text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                    </section>
                @endif
            </div>
            <footer class="sticky bottom-0 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5 dark:border-slate-700 dark:bg-slate-800">
                <button type="button" class="secondary-button" wire:click="{{ $step > 0 ? 'back' : 'cancel' }}" wire:loading.attr="disabled">{{ $step > 0 ? 'Back' : 'Cancel' }}</button>
                <div class="flex w-full flex-wrap items-center gap-3 sm:w-auto sm:justify-end">
                    <span wire:loading wire:target="next,submit" class="text-sm text-slate-500" role="status">{{ $step === 3 ? 'Saving application...' : 'Checking details...' }}</span>
                    @if ($step === 3)
                        <button type="button" wire:click="submit(false)" data-confirm data-confirm-review data-confirm-action="Save Registration" data-confirm-record="{{ trim($form['first_name'].' '.$form['middle_name'].' '.$form['last_name']) }}" class="secondary-button w-full disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto" wire:loading.attr="disabled">Save Registration</button>
                    @endif
                    <button type="submit" class="primary-button w-full disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto" wire:loading.attr="disabled" @disabled($step === 1 && ($this->age === null || $this->age < 60))>{{ $step === 3 ? 'Save & Prepare ID' : 'Continue' }}<x-admin.icon name="chevron-right" size="16" /></button>
                </div>
            </footer>
        </form>
    </div>
</div>
