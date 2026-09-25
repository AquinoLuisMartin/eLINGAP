@csrf
@foreach (['username' => 'Username', 'email' => 'Email address', 'first_name' => 'First name', 'middle_name' => 'Middle name', 'last_name' => 'Last name', 'name_suffix' => 'Name suffix'] as $field => $label)
    <div>
        <label for="{{ $field }}">{{ $label }}</label>
        <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}"
            value="{{ old($field, $user->{$field} ?? '') }}"
            @required(! in_array($field, ['middle_name', 'name_suffix'], true))>
    </div>
@endforeach
<div>
    <label for="role_id">Role</label>
    <select id="role_id" name="role_id" required>
        <option value="">Select role</option>
        @foreach ($roles as $role)
            <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id ?? '') == $role->id)>{{ $role->name->label() }}</option>
        @endforeach
    </select>
</div>
@unless (isset($user))
    @include('administration.users._password-fields')
@endunless
<button type="submit">{{ isset($user) ? 'Save changes' : 'Create account' }}</button>
<a href="{{ route('administration.users.index') }}">Cancel</a>
