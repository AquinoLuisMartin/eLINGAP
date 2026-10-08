@extends('layouts.app')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3"><h1 class="text-3xl font-bold">SMS templates</h1><a class="rounded bg-osca-primary px-4 py-2 text-white" href="{{ route('sms.templates.create') }}">New template</a></div>
<div class="grid gap-3">@forelse ($templates as $template)<article class="rounded-lg border bg-white p-4"><div class="flex items-center justify-between"><h2 class="font-semibold">{{ $template->name }}</h2><x-badge :status="$template->is_active ? 'ACTIVE' : 'INACTIVE'" /></div><p class="mt-2 whitespace-pre-wrap text-sm">{{ $template->body }}</p></article>@empty<p class="rounded-lg border bg-white p-6">No templates yet.</p>@endforelse</div><div class="mt-4">{{ $templates->links() }}</div>
@endsection
