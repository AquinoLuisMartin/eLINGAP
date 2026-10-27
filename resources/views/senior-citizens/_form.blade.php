@csrf
<div>
    <label for="barangay_id" class="block font-medium">Barangay <span aria-hidden="true">*</span></label>
    <x-select id="barangay_id" name="barangay_id" required class="mt-1 w-full">
        <option value="">Select barangay</option>
        @foreach ($barangays as $barangay)
            <option value="{{ $barangay->id }}" @selected(old('barangay_id', $seniorCitizen->barangay_id ?? '') == $barangay->id)>{{ $barangay->name }}</option>
        @endforeach
    </x-select>
</div>
<div>
    <label for="first_name" class="block font-medium">First name *</label>
    <input id="first_name" name="first_name" value="{{ old('first_name', $seniorCitizen->first_name ?? '') }}" required class="mt-1 w-full rounded border p-2 uppercase">
</div>
<div>
    <label for="middle_name" class="block font-medium">Middle name</label>
    <input id="middle_name" name="middle_name" value="{{ old('middle_name', $seniorCitizen->middle_name ?? '') }}" class="mt-1 w-full rounded border p-2 uppercase">
</div>
<div>
    <label for="last_name" class="block font-medium">Last name *</label>
    <input id="last_name" name="last_name" value="{{ old('last_name', $seniorCitizen->last_name ?? '') }}" required class="mt-1 w-full rounded border p-2 uppercase">
</div>
<div>
    <label for="name_suffix" class="block font-medium">Name suffix</label>
    <input id="name_suffix" name="name_suffix" value="{{ old('name_suffix', $seniorCitizen->name_suffix ?? '') }}" class="mt-1 w-full rounded border p-2 uppercase">
</div>
<div>
    <label for="birth_date" class="block font-medium">Birth date *</label>
    <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', isset($seniorCitizen) ? $seniorCitizen->birth_date?->format('Y-m-d') : '') }}" required class="mt-1 w-full rounded border p-2">
</div>
<div>
    <label for="sex" class="block font-medium">Sex *</label>
    <x-select id="sex" name="sex" required class="mt-1 w-full">
        @foreach (['MALE', 'FEMALE', 'OTHER'] as $option)
            <option value="{{ $option }}" @selected(old('sex', $seniorCitizen->sex ?? '') === $option)>{{ $option }}</option>
        @endforeach
    </x-select>
</div>
<div>
    <label for="civil_status" class="block font-medium">Civil status</label>
    <x-select id="civil_status" name="civil_status" class="mt-1 w-full">
        <option value="">Select civil status</option>
        @foreach (['SINGLE' => 'Single', 'MARRIED' => 'Married', 'WIDOWED' => 'Widowed'] as $value => $label)
            <option value="{{ $value }}" @selected(strtoupper(old('civil_status', $seniorCitizen->civil_status ?? '')) === $value)>{{ $label }}</option>
        @endforeach
    </x-select>
</div>
<div>
    <label for="address" class="block font-medium">House and street address *</label>
    <textarea id="address" name="address" required class="mt-1 w-full rounded border p-2 uppercase">{{ old('address', $seniorCitizen->address ?? '') }}</textarea>
</div>
<div>
    <label for="contact_number" class="block font-medium">Philippine mobile number *</label>
    <x-senior-mobile-input id="contact_number" name="contact_number" required :value="old('contact_number', $seniorCitizen->contact_number ?? '')" />
</div>
<div>
    <label for="osca_id_number" class="block font-medium">OSCA ID number</label>
    <input id="osca_id_number" value="{{ $seniorCitizen->osca_id_number ?? '' }}" readonly placeholder="Assigned automatically when the ID is issued" class="mt-1 w-full rounded border p-2">
</div>

<fieldset class="space-y-4 rounded-lg border p-4">
    <legend class="px-2 font-semibold">Applicant and authorized representative</legend>
    <p class="text-sm text-slate-500">A contact number for the senior is required for every registration. Representative details and authorization proof are required when a representative applies or receives SMS.</p>
    <div class="field-label block text-sm"><label data-select-label>Applicant type</label><x-select label="Applicant type" name="applicant_type" class="mt-1 w-full">
            <option value="SELF" @selected(old('applicant_type', $seniorCitizen->applicant_type ?? 'SELF') === 'SELF')>Senior personally</option>
            <option value="REPRESENTATIVE" @selected(old('applicant_type', $seniorCitizen->applicant_type ?? 'SELF') === 'REPRESENTATIVE')>Authorized representative</option>
        </x-select>
    </div>
    @foreach (['representative_name' => 'Representative name', 'representative_relationship' => 'Relationship to senior', 'representative_contact' => 'Representative mobile number'] as $field => $label)
        <label class="block text-sm">{{ $label }}
            @if ($field === 'representative_contact')
                <x-senior-mobile-input name="representative_contact" :value="old($field, $seniorCitizen->representative_contact ?? '')" />
            @else
                <input name="{{ $field }}" value="{{ old($field, isset($seniorCitizen) ? $seniorCitizen->{$field} : '') }}" class="mt-1 w-full rounded border p-2 uppercase">
            @endif
        </label>
    @endforeach
    <label class="block text-sm">Proof of authorization (PDF or image, up to 5 MB)<input type="file" name="authorization_file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full rounded border p-2"></label>
    @isset($seniorCitizen)<p class="text-xs text-slate-500">Upload new proof when changing the authorized representative. Existing documents remain in the private record.</p>@endisset
</fieldset>
<div class="sticky bottom-0 flex flex-wrap gap-3 border-t border-[var(--line)] bg-[var(--white)] py-4"><a href="{{ isset($seniorCitizen) ? route('senior-citizens.show', [$seniorCitizen, ...request()->only(['from', 'search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])]) : route('senior-citizens.index') }}" class="secondary-button">Cancel</a><button type="submit" class="primary-button">{{ isset($seniorCitizen) ? 'Save changes' : 'Register senior' }}</button></div>
