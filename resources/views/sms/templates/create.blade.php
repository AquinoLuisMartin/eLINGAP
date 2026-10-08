@extends('layouts.app')
@section('content')
<h1 class="mb-6 text-3xl font-bold">New SMS template</h1>
<form method="POST" action="{{ route('sms.templates.store') }}" class="grid max-w-2xl gap-4 rounded-lg border bg-white p-5">@csrf<label>Template name <input name="name" value="{{ old('name') }}" required maxlength="120" class="mt-1 block w-full rounded border p-2"></label><label>Message <textarea name="body" rows="7" required maxlength="1600" class="mt-1 block w-full rounded border p-2">{{ old('body') }}</textarea></label><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Available in broadcast composer</label><div class="flex gap-3"><a class="rounded border px-4 py-2" href="{{ route('sms.templates.index') }}">Cancel</a><button class="rounded bg-osca-primary px-4 py-2 text-white">Save template</button></div></form>
@endsection
