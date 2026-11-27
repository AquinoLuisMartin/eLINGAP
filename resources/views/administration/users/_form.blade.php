@csrf
<div class="flex flex-col gap-5 p-4 sm:p-6">

    @foreach (['Personal details' => ['first_name' => 'First name', 'middle_name' => 'Middle name', 'last_name' => 'Last name', 'name_suffix' => 'Name suffix'], 'Account access' => ['employee_id' => 'Employee ID']] as $section => $fields)
        <fieldset class="min-w-0">
            <legend class="mb-3 w-full border-b border-[var(--line)] pb-2 text-base font-semibold text-[var(--ink)]">{{ $section }}</legend>
            <div class="grid gap-x-5 gap-y-4 sm:grid-cols-2">
                @foreach ($fields as $field => $label)
                    @php($required = ! in_array($field, ['middle_name', 'name_suffix'], true))
                    @php($fieldValue = old($field, $user->{$field} ?? ''))
                    <div class="ui-field">
                        <label class="text-sm font-medium text-[var(--ink)]" for="{{ $field }}">
                            {{ $label }}
                            @if ($required)
                                <span class="field-required" aria-hidden="true">*</span>
                            @else
                                <span class="text-xs font-normal text-[var(--muted)]">(optional)</span>
                            @endif
                        </label>
                        @if ($field === 'name_suffix')
                            <x-name-suffix-select class="w-full" id="name_suffix" name="name_suffix" :value="$fieldValue" :aria-describedby="$errors->has($field) ? $field.'-error' : null" />
                        @else
                            <input class="w-full" id="{{ $field }}" name="{{ $field }}" type="text"
                                value="{{ is_string($fieldValue) ? $fieldValue : '' }}" maxlength="{{ $field === 'employee_id' ? 9 : 100 }}"
                                autocomplete="{{ match ($field) { 'first_name' => 'given-name', 'middle_name' => 'additional-name', 'last_name' => 'family-name', 'employee_id' => 'username', default => 'off' } }}"
                                @if ($field === 'employee_id') spellcheck="false" autocapitalize="none" minlength="9" pattern="[0-9]{4}-[0-9]{4}" placeholder="YYYY-NNNN" @endif
                                @required($required) aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}"
                                @if ($errors->has($field) || $field === 'employee_id') aria-describedby="{{ $field === 'employee_id' ? 'employee_id-help ' : '' }}{{ $errors->has($field) ? $field.'-error' : '' }}" @endif>
                        @endif
                        @if ($field === 'employee_id')
                            <p id="employee_id-help" class="text-xs leading-relaxed text-[var(--muted)]">Employees use this ID to sign in. Format: YYYY-NNNN.</p>
                        @endif
                        @error($field)<p id="{{ $field }}-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                @if ($loop->last)
                    <div class="ui-field">
                        <label class="text-sm font-medium text-[var(--ink)]" for="role_id">Role <span class="field-required" aria-hidden="true">*</span></label>
                        <x-select class="w-full" id="role_id" name="role_id" required aria-invalid="{{ $errors->has('role_id') ? 'true' : 'false' }}" :aria-describedby="$errors->has('role_id') ? 'role_id-error' : null">
                            <option value="">Select role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id ?? '') == $role->id)>{{ $role->name->label() }}</option>
                            @endforeach
                        </x-select>

                        @error('role_id')<p id="role_id-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                @endif
            </div>
        </fieldset>
    @endforeach

    @unless (isset($user))
        <div class="rounded-md border border-[var(--line)] bg-[var(--paper)] px-3 py-2.5">
            <p class="text-sm font-medium text-[var(--ink)]">Temporary password</p>
            <p class="mt-1 text-xs leading-relaxed text-[var(--muted)]">The password appears once after account creation. Share it privately with the employee, who must change it on first sign-in.</p>
        </div>
    @endunless
</div>

<div class="flex flex-col-reverse gap-2 border-t border-[var(--line)] px-4 py-3 sm:flex-row sm:justify-end sm:px-6">
    <a class="secondary-button min-h-11" href="{{ route('administration.users.index') }}">Cancel</a>
    <x-button type="submit" class="min-h-11">
        {{ isset($user) ? 'Save changes' : 'Create account' }}
    </x-button>
</div>
