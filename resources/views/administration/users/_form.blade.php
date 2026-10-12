@csrf
<p class="text-sm text-slate-500 dark:text-slate-400">Fields marked <span class="text-red-600">*</span> are required.</p>
@foreach (['Personal details' => ['first_name' => 'First name', 'middle_name' => 'Middle name', 'last_name' => 'Last name', 'name_suffix' => 'Name suffix'], 'Account details' => ['username' => 'Username', 'email' => 'Email address']] as $section => $fields)
    <fieldset>
        <legend class="mb-4 text-base font-medium">{{ $section }}</legend>
        <div class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
            @foreach ($fields as $field => $label)
                @php($required = ! in_array($field, ['middle_name', 'name_suffix'], true))
                <div class="min-w-0">
                    <label class="mb-2 block text-sm" for="{{ $field }}">{{ $label }} @if ($required)<span class="text-red-600" aria-hidden="true">*</span>@else<span class="text-slate-400">(optional)</span>@endif</label>
                    <input class="w-full" id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}"
                        value="{{ old($field, $user->{$field} ?? '') }}" maxlength="{{ $field === 'email' ? 255 : ($field === 'name_suffix' ? 20 : 100) }}"
                        autocomplete="{{ match ($field) { 'first_name' => 'given-name', 'middle_name' => 'additional-name', 'last_name' => 'family-name', 'email' => 'email', 'username' => 'username', default => 'off' } }}"
                        @if (in_array($field, ['username', 'email'], true)) spellcheck="false" autocapitalize="none" @endif
                        @if ($field === 'username') minlength="3" @endif
                        @required($required) aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}"
                        @if ($errors->has($field) || $field === 'username') aria-describedby="{{ $field === 'username' ? 'username-help ' : '' }}{{ $errors->has($field) ? $field.'-error' : '' }}" @endif>
                    @if ($field === 'username')<p id="username-help" class="mt-2 text-xs text-slate-500 dark:text-slate-400">Use letters, numbers, dots, underscores, or dashes. No spaces.</p>@endif
                    @error($field)<p id="{{ $field }}-error" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
    </fieldset>
@endforeach
<fieldset>
    <legend class="mb-4 text-base font-medium">Access role</legend>
    <div class="max-w-md">
        <label class="mb-2 block text-sm" for="role_id">Role <span class="text-red-600" aria-hidden="true">*</span></label>
        <select class="w-full" id="role_id" name="role_id" required aria-invalid="{{ $errors->has('role_id') ? 'true' : 'false' }}" aria-describedby="role-help{{ $errors->has('role_id') ? ' role_id-error' : '' }}">
            <option value="">Select role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id ?? '') == $role->id)>{{ $role->name->label() }}</option>
            @endforeach
        </select>
        <p id="role-help" class="mt-2 text-xs text-slate-500 dark:text-slate-400">The assigned role determines which system features this account can access.</p>
        @error('role_id')<p id="role_id-error" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
</fieldset>
@unless (isset($user))
    <fieldset>
        <legend class="mb-4 text-base font-medium">Sign-in password</legend>
        <div class="grid gap-6 sm:grid-cols-2">@include('administration.users._password-fields')</div>
    </fieldset>
@endunless
<div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end dark:border-slate-700">
    <a class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 px-5 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800" href="{{ route('administration.users.index') }}">Cancel</a>
    <button class="inline-flex min-h-11 items-center justify-center rounded-lg bg-osca-primary px-5 text-sm text-white hover:bg-osca-primary-dark" type="submit">{{ isset($user) ? 'Save changes' : 'Create account' }}</button>
</div>
