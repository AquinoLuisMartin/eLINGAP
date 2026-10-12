@foreach (['password' => 'New password', 'password_confirmation' => 'Confirm password'] as $field => $label)
    <div class="min-w-0" x-data="{ visible: false }">
        <label class="mb-2 block text-sm" for="{{ $field }}">{{ $label }} <span class="text-red-600" aria-hidden="true">*</span></label>
        <div class="relative">
            <input class="w-full pr-20!" id="{{ $field }}" name="{{ $field }}" type="password" :type="visible ? 'text' : 'password'" autocomplete="new-password" required aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" aria-describedby="{{ $field }}-help{{ $errors->has($field) ? ' '.$field.'-error' : '' }}">
            <button class="absolute inset-y-0 right-0 min-w-16 rounded-r-lg px-3 text-sm text-osca-primary dark:text-blue-300" type="button" @click="visible = !visible" :aria-pressed="visible" aria-controls="{{ $field }}" aria-label="Show {{ strtolower($label) }}" :aria-label="(visible ? 'Hide ' : 'Show ') + '{{ strtolower($label) }}'" x-text="visible ? 'Hide' : 'Show'">Show</button>
        </div>
        <p id="{{ $field }}-help" class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $field === 'password' ? 'Use at least 12 characters.' : 'Enter the same password again.' }}</p>
        @error($field)<p id="{{ $field }}-error" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
@endforeach
