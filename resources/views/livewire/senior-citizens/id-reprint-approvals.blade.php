<div class="flex flex-col gap-4">
    <x-staff.page-header title="ID Reprint Approvals" description="Review reprint requests before authorizing one additional physical copy."><a class="secondary-button" href="{{ route('registrations.index') }}">Back to Registrations</a></x-staff.page-header>
    @if ($notice)<x-feedback>{{ $notice }}</x-feedback>@endif
    @error('reviewReason')<x-feedback type="error">{{ $message }}</x-feedback>@enderror
    @forelse ($requests as $request)
        <section wire:key="id-reprint-request-{{ $request->id }}" class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-semibold text-(--navy) dark:text-slate-100">{{ $request->seniorCitizen->full_name }} <span class="text-sm font-normal">{{ $request->seniorCitizen->osca_id_number }}</span></h2><x-badge :status="$request->status === 'PENDING' ? 'Pending Approval' : $request->status" /></div>
            <dl class="grid gap-3 rounded-lg bg-slate-50 p-4 text-sm sm:grid-cols-2 dark:bg-slate-800">
                <div><dt class="text-slate-500 dark:text-slate-400">Reason</dt><dd class="font-medium">{{ \App\Models\IdReprintRequest::REASONS[$request->reason] ?? $request->reason }}</dd></div>
                <div><dt class="text-slate-500 dark:text-slate-400">Requested by</dt><dd>{{ $request->requester->full_name }} on {{ $request->created_at->format('M j, Y g:i A') }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500 dark:text-slate-400">Additional details</dt><dd class="whitespace-pre-line wrap-anywhere">{{ $request->details }}</dd></div>
                @if ($request->reviewed_at)<div><dt class="text-slate-500 dark:text-slate-400">Reviewed by</dt><dd>{{ $request->reviewer?->full_name }} on {{ $request->reviewed_at->format('M j, Y g:i A') }}</dd></div>@endif
                @if ($request->review_reason)<div><dt class="text-slate-500 dark:text-slate-400">Review reason</dt><dd class="whitespace-pre-line wrap-anywhere">{{ $request->review_reason }}</dd></div>@endif
            </dl>
            @if ($request->status === 'PENDING')
                <label class="flex flex-col gap-1 text-sm">Review reason (required when rejecting)<textarea wire:model="reviewReasons.{{ $request->id }}" rows="2" maxlength="2000" class="w-full rounded border border-slate-200 p-3 dark:border-slate-700"></textarea></label>
                <div class="flex flex-wrap justify-end gap-3"><button type="button" wire:click="review({{ $request->id }}, false)" wire:loading.attr="disabled" class="secondary-button">Reject</button><button type="button" wire:click="review({{ $request->id }}, true)" wire:loading.attr="disabled" class="primary-button">Approve One Reprint</button></div>
            @endif
        </section>
    @empty
        <p class="text-sm text-slate-500 dark:text-slate-400">No reprint requests.</p>
    @endforelse
    {{ $requests->links() }}
</div>
