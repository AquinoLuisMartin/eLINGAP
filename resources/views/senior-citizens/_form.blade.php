@csrf
<div>
    <label for="barangay_id" class="block font-medium">Barangay <span aria-hidden="true">*</span></label>
    <select id="barangay_id" name="barangay_id" required class="mt-1 w-full rounded border p-2">
        <option value="">Select barangay</option>
        @foreach ($barangays as $barangay)
            <option value="{{ $barangay->id }}" @selected(old('barangay_id', $seniorCitizen->barangay_id ?? '') == $barangay->id)>{{ $barangay->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="first_name" class="block font-medium">First name *</label>
    <input id="first_name" name="first_name" value="{{ old('first_name', $seniorCitizen->first_name ?? '') }}" required class="mt-1 w-full rounded border p-2">
</div>
<div>
    <label for="middle_name" class="block font-medium">Middle name</label>
    <input id="middle_name" name="middle_name" value="{{ old('middle_name', $seniorCitizen->middle_name ?? '') }}" class="mt-1 w-full rounded border p-2">
</div>
<div>
    <label for="last_name" class="block font-medium">Last name *</label>
    <input id="last_name" name="last_name" value="{{ old('last_name', $seniorCitizen->last_name ?? '') }}" required class="mt-1 w-full rounded border p-2">
</div>
<div>
    <label for="name_suffix" class="block font-medium">Name suffix</label>
    <input id="name_suffix" name="name_suffix" value="{{ old('name_suffix', $seniorCitizen->name_suffix ?? '') }}" class="mt-1 w-full rounded border p-2">
</div>
<div>
    <label for="birth_date" class="block font-medium">Birth date *</label>
    <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', isset($seniorCitizen) ? $seniorCitizen->birth_date?->format('Y-m-d') : '') }}" required class="mt-1 w-full rounded border p-2">
</div>
<div>
    <label for="sex" class="block font-medium">Sex *</label>
    <select id="sex" name="sex" required class="mt-1 w-full rounded border p-2">
        @foreach (['MALE', 'FEMALE', 'OTHER'] as $option)
            <option value="{{ $option }}" @selected(old('sex', $seniorCitizen->sex ?? '') === $option)>{{ $option }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="civil_status" class="block font-medium">Civil status</label>
    <select id="civil_status" name="civil_status" class="mt-1 w-full rounded border p-2">
        <option value="">Select civil status</option>
        @foreach (['SINGLE' => 'Single', 'MARRIED' => 'Married', 'WIDOWED' => 'Widowed'] as $value => $label)
            <option value="{{ $value }}" @selected(strtoupper(old('civil_status', $seniorCitizen->civil_status ?? '')) === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="address" class="block font-medium">House and street address *</label>
    <textarea id="address" name="address" required class="mt-1 w-full rounded border p-2">{{ old('address', $seniorCitizen->address ?? '') }}</textarea>
</div>
<div>
    <label for="contact_number" class="block font-medium">Philippine mobile number</label>
    <input id="contact_number" name="contact_number" type="tel" inputmode="tel" placeholder="09xx xxx xxxx" value="{{ old('contact_number', $seniorCitizen->contact_number ?? '') }}" class="mt-1 w-full rounded border p-2">
</div>
<div>
    <label for="osca_id_number" class="block font-medium">OSCA ID number</label>
    <input id="osca_id_number" value="{{ $seniorCitizen->osca_id_number ?? '' }}" readonly placeholder="Assigned automatically when the ID is issued" class="mt-1 w-full rounded border p-2">
</div>
<div><label for="photo" class="block font-medium">Square ID photo (JPEG or PNG, up to 2 MB)</label><input id="photo" type="file" name="photo" accept=".jpg,.jpeg,.png" class="mt-1 w-full rounded border p-2"></div>
<fieldset class="space-y-4 rounded-lg border p-4">
    <legend class="px-2 font-semibold">Applicant and authorized representative</legend>
    <p class="text-sm text-slate-500">A mobile number for the senior or representative is required. Representative details and authorization proof are required when a representative applies or receives SMS.</p>
    <label class="block text-sm">Applicant type
        <select name="applicant_type" class="mt-1 w-full rounded border p-2">
            <option value="SELF" @selected(old('applicant_type', $seniorCitizen->applicant_type ?? 'SELF') === 'SELF')>Senior personally</option>
            <option value="REPRESENTATIVE" @selected(old('applicant_type', $seniorCitizen->applicant_type ?? 'SELF') === 'REPRESENTATIVE')>Authorized representative</option>
        </select>
    </label>
    @foreach (['representative_name' => 'Representative name', 'representative_relationship' => 'Relationship to senior', 'representative_contact' => 'Representative mobile number'] as $field => $label)
        <label class="block text-sm">{{ $label }}<input name="{{ $field }}" type="{{ $field === 'representative_contact' ? 'tel' : 'text' }}" value="{{ old($field, isset($seniorCitizen) ? $seniorCitizen->{$field} : '') }}" class="mt-1 w-full rounded border p-2"></label>
    @endforeach
    <label class="block text-sm">Proof of authorization (PDF or image, up to 5 MB)<input type="file" name="authorization_file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full rounded border p-2"></label>
    @isset($seniorCitizen)<p class="text-xs text-slate-500">Upload new proof when changing the authorized representative. Existing documents remain in the private record.</p>@endisset
</fieldset>
<div class="sticky bottom-0 flex gap-3 border-t bg-white py-4"><a href="{{ isset($seniorCitizen) ? route('senior-citizens.show', $seniorCitizen) : route('senior-citizens.index') }}" class="rounded border px-4 py-2">Cancel</a><button type="submit" class="rounded bg-osca-primary px-4 py-2 font-semibold text-white">{{ isset($seniorCitizen) ? 'Save changes' : 'Register senior' }}</button></div>
