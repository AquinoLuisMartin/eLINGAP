@csrf
<div>
    <label for="barangay_id">Barangay</label>
    <select id="barangay_id" name="barangay_id" required>
        <option value="">Select barangay</option>
        @foreach ($barangays as $barangay)
            <option value="{{ $barangay->id }}" @selected(old('barangay_id', $seniorCitizen->barangay_id ?? '') == $barangay->id)>{{ $barangay->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="first_name">First name</label>
    <input id="first_name" name="first_name" value="{{ old('first_name', $seniorCitizen->first_name ?? '') }}" required>
</div>
<div>
    <label for="middle_name">Middle name</label>
    <input id="middle_name" name="middle_name" value="{{ old('middle_name', $seniorCitizen->middle_name ?? '') }}">
</div>
<div>
    <label for="last_name">Last name</label>
    <input id="last_name" name="last_name" value="{{ old('last_name', $seniorCitizen->last_name ?? '') }}" required>
</div>
<div>
    <label for="name_suffix">Name suffix</label>
    <input id="name_suffix" name="name_suffix" value="{{ old('name_suffix', $seniorCitizen->name_suffix ?? '') }}">
</div>
<div>
    <label for="birth_date">Birth date</label>
    <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', isset($seniorCitizen) ? $seniorCitizen->birth_date?->format('Y-m-d') : '') }}" required>
</div>
<div>
    <label for="sex">Sex</label>
    <select id="sex" name="sex" required>
        @foreach (['MALE', 'FEMALE', 'OTHER'] as $option)
            <option value="{{ $option }}" @selected(old('sex', $seniorCitizen->sex ?? '') === $option)>{{ $option }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="civil_status">Civil status</label>
    <input id="civil_status" name="civil_status" value="{{ old('civil_status', $seniorCitizen->civil_status ?? '') }}">
</div>
<div>
    <label for="address">Address</label>
    <textarea id="address" name="address" required>{{ old('address', $seniorCitizen->address ?? '') }}</textarea>
</div>
<div>
    <label for="contact_number">Contact number</label>
    <input id="contact_number" name="contact_number" value="{{ old('contact_number', $seniorCitizen->contact_number ?? '') }}">
</div>
<div>
    <label for="osca_id_number">OSCA ID number</label>
    <input id="osca_id_number" name="osca_id_number" value="{{ old('osca_id_number', $seniorCitizen->osca_id_number ?? '') }}">
</div>
<button type="submit">{{ isset($seniorCitizen) ? 'Update record' : 'Create record' }}</button>
<a href="{{ route('senior-citizens.index') }}">Cancel</a>
