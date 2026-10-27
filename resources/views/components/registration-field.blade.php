@props(['name', 'label', 'required' => false, 'hint' => null])
<x-form-field :name="$name" :label="$label" :required="$required" :hint="$hint" {{ $attributes }}>
    {{ $slot }}
</x-form-field>
