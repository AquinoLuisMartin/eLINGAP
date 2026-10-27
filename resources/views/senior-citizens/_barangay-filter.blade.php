@php($filterId = $filterId ?? 'registry-barangay')
<div class="field-label">
    <label for="{{ $filterId }}">Barangay</label>
    <x-select id="{{ $filterId }}" name="barangay_id" searchable>
        <option value="">All barangays</option>
        @foreach ($barangays as $barangay)
            <option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>
        @endforeach
    </x-select>
</div>
