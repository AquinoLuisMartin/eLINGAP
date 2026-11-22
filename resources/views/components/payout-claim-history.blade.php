@props(['payments', 'showDemoNotice' => true])

<div class="flex flex-col gap-3">
    @foreach ($payments as $payment)
        <div wire:key="claim-history-{{ $payment->id }}" class="rounded-lg border border-[var(--line)] p-3 text-xs">
            @if ($showDemoNotice && app()->environment('local') && str_starts_with($payment->reference_number, 'DEMO-'))<p class="mb-3 text-sm font-semibold">Local demo only — No funds released</p>@endif
            @if ($payment->reversed_at)<p class="mb-3 font-semibold">Reversed claim</p>@endif
            @php
                $isProxy = in_array($payment->claimant_type, ['PROXY', 'REPRESENTATIVE'], true);
                $fields = [
                    'Amount released' => \App\Support\PhilippineCurrency::format($payment->amount),
                    'Claim date/time' => $payment->paid_at->format('M j, Y g:i A'),
                    'Claimant' => $payment->claimant_name ?: 'Not recorded',
                    'Claim method' => $isProxy ? 'Proxy' : ($payment->claimant_type === 'SELF' ? 'Personal' : 'Not recorded'),
                ];
                if ($isProxy) {
                    $fields['Relationship'] = $payment->claimant_relationship ?: 'Not recorded';
                    $fields['Contact number'] = $payment->claimant_contact ?: 'Not recorded';
                }
                $fields['Processing staff'] = $payment->recordedBy?->full_name ?: 'Staff account unavailable';
                $fields['Identity verification'] = match ($payment->identity_verified) { true => 'Verified', false => 'Not verified', default => 'Not recorded' };
                if ($isProxy) {
                    $fields['Authorization verification'] = match ($payment->authorization_verified) { true => 'Verified', false => 'Not verified', default => 'Not recorded' };
                }
            @endphp
            <dl class="grid gap-x-4 gap-y-3 text-sm sm:grid-cols-2">
                @foreach ($fields as $label => $value)
                    <div wire:key="claim-field-{{ $payment->id }}-{{ $loop->index }}" class="min-w-0"><dt class="text-xs text-[var(--muted)]">{{ $label }}</dt><dd class="mt-1 wrap-anywhere font-semibold text-(--navy) dark:text-slate-100">{{ $value }}</dd></div>
                @endforeach
            </dl>
            @if ($payment->reversed_at)<p class="mt-1">Reversed {{ $payment->reversed_at->format('M j, Y g:i A') }}: {{ $payment->reversal_reason }}</p>@endif
        </div>
    @endforeach
</div>
