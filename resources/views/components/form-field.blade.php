@props(['name', 'label', 'required' => false, 'hint' => null])
@php($fieldId = str_replace('.', '-', $name))
<div {{ $attributes->class(['ui-field']) }}>
    <label for="{{ $fieldId }}" class="field-label">
        {{ $label }} @if ($required)<span class="field-required" aria-hidden="true">*</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)<p id="{{ $fieldId }}-help" class="field-helper">{{ $hint }}</p>@endif
    @error($name)<p id="{{ $fieldId }}-error" class="field-error" role="alert">{{ $message }}</p>@enderror
</div>
