@props(['name', 'label', 'required' => false, 'hint' => null])
@php($fieldId = str_replace('.', '-', $name))
<div {{ $attributes->class(['ui-field']) }} data-field-required="{{ $required ? 'true' : 'false' }}">
    <label for="{{ $fieldId }}" class="field-label"><span class="field-label-text">{{ $label }}@if ($required)<span class="field-required" aria-hidden="true">*</span><span class="sr-only"> required</span>@endif</span></label>
    {{ $slot }}
    @if ($hint)<p id="{{ $fieldId }}-help" class="field-helper">{{ $hint }}</p>@endif
    @error($name)<p id="{{ $fieldId }}-error" class="field-error" role="alert">{{ $message }}</p>@enderror
</div>
