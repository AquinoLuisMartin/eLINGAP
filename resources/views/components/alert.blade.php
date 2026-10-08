@if (session('status'))
    <p role="status" class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-green-800">{{ session('status') }}</p>
@endif
@if ($errors->any())
    <ul role="alert" class="mb-4 list-disc rounded border border-red-200 bg-red-50 p-3 pl-8 text-red-800">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif
