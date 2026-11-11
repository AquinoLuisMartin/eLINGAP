@csrf
<div class="flex flex-col gap-8 p-5 sm:p-7">
    <p class="text-xs text-slate-500 dark:text-slate-400">Fields marked <span class="field-required" aria-hidden="true">*</span> are required.</p>

    @foreach (['Personal details' => ['first_name' => 'First name', 'middle_name' => 'Middle name', 'last_name' => 'Last name', 'name_suffix' => 'Name suffix'], 'Account access' => ['employee_id' => 'Employee ID']] as $section => $fields)
        <fieldset class="min-w-0">
            <legend class="account-section-title mb-5 w-full border-b border-slate-200 pb-3 text-sm dark:border-slate-700">{{ $section }}</legend>
            <div class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                @foreach ($fields as $field => $label)
                    @php($required = ! in_array($field, ['middle_name', 'name_suffix'], true))
                    @php($fieldValue = old($field, $user->{$field} ?? ''))
                    <div class="ui-field">
                        <label class="account-field-label text-sm" for="{{ $field }}">
                            {{ $label }}
                            @if ($required)
                                <span class="field-required" aria-hidden="true">*</span>
                            @else
                                <span class="text-xs font-normal text-slate-500 dark:text-slate-400">(optional)</span>
                            @endif
                        </label>
                        <input class="w-full" id="{{ $field }}" name="{{ $field }}" type="text"
                            value="{{ is_string($fieldValue) ? $fieldValue : '' }}" maxlength="{{ match ($field) { 'employee_id' => 9, 'name_suffix' => 20, default => 100 } }}"
                            autocomplete="{{ match ($field) { 'first_name' => 'given-name', 'middle_name' => 'additional-name', 'last_name' => 'family-name', 'employee_id' => 'username', default => 'off' } }}"
                            @if ($field === 'employee_id') spellcheck="false" autocapitalize="none" minlength="9" pattern="[0-9]{4}-[0-9]{4}" placeholder="YYYY-NNNN" @endif
                            @required($required) aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}"
                            @if ($errors->has($field) || $field === 'employee_id') aria-describedby="{{ $field === 'employee_id' ? 'employee_id-help ' : '' }}{{ $errors->has($field) ? $field.'-error' : '' }}" @endif>
                        @if ($field === 'employee_id')
                            <p id="employee_id-help" class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">Format: YYYY-NNNN (e.g. 2026-0001). Must be unique to this employee.</p>
                        @endif
                        @error($field)<p id="{{ $field }}-error" class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                @if ($loop->last)
                    <div class="ui-field">
                        <label class="account-field-label text-sm" for="role_id">Role <span class="field-required" aria-hidden="true">*</span></label>
                        <x-select class="w-full" id="role_id" name="role_id" required aria-invalid="{{ $errors->has('role_id') ? 'true' : 'false' }}" aria-describedby="role-help{{ $errors->has('role_id') ? ' role_id-error' : '' }}">
                            <option value="">Select role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id ?? '') == $role->id)>{{ $role->name->label() }}</option>
                            @endforeach
                        </x-select>
                        <p id="role-help" class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">Determines which system features the employee can access.</p>
                        @error('role_id')<p id="role_id-error" class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endif
            </div>
        </fieldset>
    @endforeach

    @unless (isset($user))
        <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">A temporary password will be shown once after creation. Share it privately with the employee, who must change it on first sign-in.</p>
    @endunless
</div>

<div class="flex flex-col-reverse gap-3 border-t border-slate-200 px-5 py-5 sm:flex-row sm:justify-end sm:px-7 dark:border-slate-700">
    <a class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-5 text-sm text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" href="{{ route('administration.users.index') }}">Cancel</a>
    <button class="inline-flex min-h-11 items-center justify-center rounded-lg bg-osca-primary px-5 text-sm text-white hover:bg-osca-primary-dark" type="submit">
        {{ isset($user) ? 'Save changes' : 'Create account' }}
    </button>
</div>
