@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
        @foreach ($errors->all() as $message)
            <p wire:key="error-{{ $loop->index }}">{{ $message }}</p>
        @endforeach
    </div>
@endif
