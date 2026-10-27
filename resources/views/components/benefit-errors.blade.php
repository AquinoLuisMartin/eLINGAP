@if ($errors->any())
    <x-feedback type="error"><ul class="list-disc pl-5">
        @foreach ($errors->all() as $message)
            <li wire:key="error-{{ $loop->index }}">{{ $message }}</li>
        @endforeach
    </ul></x-feedback>
@endif
