@extends('layouts.app')
@section('content')
<div class="w-full min-w-0" x-data="smsMessages({ action: 'Resend' })">
<x-workspace-navigation section="messaging" />
<header class="mb-5 flex items-center justify-between gap-4">
    <h2 class="text-3xl font-semibold">Broadcast details</h2>
    <a href="{{ route('sms.blasts.index') }}" class="secondary-button shrink-0" x-on:click="if (window.history.length > 1) { $event.preventDefault(); window.history.back(); }">&larr; Back</a>
</header>
<div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2 dark:border-slate-700 dark:bg-slate-900">
    <p><strong>Audience:</strong> {{ $blast->barangay?->name ?: 'All barangays' }}</p>
    <p><strong>Sender:</strong> {{ $blast->creator->first_name }} {{ $blast->creator->last_name }}</p>
    <p><strong>Queued:</strong> {{ $blast->created_at->format('Y-m-d H:i') }}</p>
    <p><strong>Eligible:</strong> {{ $blast->recipient_count }} &middot; <strong>Excluded:</strong> {{ $blast->excluded_count }}</p>
</div>
<section class="w-full min-w-0" aria-labelledby="recipient-deliveries-title">
<h3 id="recipient-deliveries-title" class="my-5 text-xl font-semibold">Recipient deliveries</h3>
<p x-cloak x-show="notice" x-text="notice" role="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200"></p>
<x-sms.delivery-table :messages="$messages" :broadcast="true" />
<div class="mt-4">{{ $messages->links() }}</div>
</section>
<x-sms.message-dialog :broadcast="true" />
<x-sms.retry-dialog :broadcast="true" />
</div>
@endsection
