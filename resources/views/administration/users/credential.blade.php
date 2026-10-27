@extends('layouts.app')
@section('content')
<section class="panel mx-auto max-w-2xl space-y-5" data-one-time-credential aria-labelledby="credential-title">
    <h2 id="credential-title" class="text-2xl font-semibold">Private credential handover</h2>
    <p>{{ $handoverMessage }}</p>
    <dl class="space-y-4"><div><dt class="font-semibold">Username</dt><dd>{{ $username }}</dd></div>
        <div><dt class="font-semibold">{{ $credentialLabel }}</dt><dd><output class="block break-all rounded border p-4 font-mono text-lg" data-credential-value>{{ $credential }}</output></dd></div></dl>
    <p class="text-sm text-slate-500">This credential is shown once. After closing or leaving this page, it cannot be retrieved. Use the verified recovery process if a replacement is needed.</p>
    <p data-credential-feedback role="status" aria-live="polite"></p>
    <button type="button" class="secondary-button" data-dismiss-credential>Hide credential</button>
    <a href="{{ route('administration.users.index') }}" class="primary-button">Return to Account Management</a>
</section>
@endsection
