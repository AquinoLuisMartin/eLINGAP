@extends('layouts.app')
@section('content')
<x-workspace-navigation section="messaging" />
<x-staff.page-header title="SMS templates" description="Maintain reusable messages for individual notices and broadcasts.">
    <a class="primary-button" href="{{ route('sms.templates.create') }}">New template</a>
</x-staff.page-header>
<div class="grid gap-3">@forelse ($templates as $template)<article class="rounded-lg border bg-white p-4"><div class="flex items-center justify-between"><h2 class="font-semibold">{{ $template->name }}</h2><x-badge :status="$template->is_active ? 'ACTIVE' : 'INACTIVE'" /></div><p class="mt-2 whitespace-pre-wrap text-sm">{{ $template->body }}</p></article>@empty<p class="rounded-lg border bg-white p-6">No templates yet.</p>@endforelse</div><div class="mt-4">{{ $templates->links() }}</div>
@endsection
