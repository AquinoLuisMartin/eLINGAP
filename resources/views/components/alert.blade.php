@if (session('status'))
    <x-feedback>{{ session('status') }}</x-feedback>
@endif
@if ($errors->any())
    <x-feedback type="error"><ul class="list-disc pl-5">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul></x-feedback>
@endif
