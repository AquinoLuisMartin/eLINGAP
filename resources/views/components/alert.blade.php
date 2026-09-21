@if (session('status'))
    <p role="status">{{ session('status') }}</p>
@endif
@if ($errors->any())
    <ul role="alert">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif
