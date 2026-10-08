<section class="page-intro"><div><p class="section-kicker">Communications center</p><h2>SMS Dispatcher</h2><p class="section-subtitle">Recorded sending results and broadcast history.</p></div><a class="primary-button" href="{{ route('sms.blasts.create') }}"><x-admin.icon name="send" size="16" /> Create Broadcast</a></section>
<section class="stat-strip">
    <div class="mini-stat"><div><small>Sent this month</small><strong>{{ number_format($this->summary['sms_sent']) }}</strong></div></div>
    <div class="mini-stat"><div><small>Sending success this month</small><strong>{{ $this->summary['sms_rate'] === null ? 'No attempts' : $this->summary['sms_rate'].'%' }}</strong></div></div>
    <div class="mini-stat"><div><small>Currently queued</small><strong>{{ number_format($this->summary['sms_queued']) }}</strong></div></div>
    <div class="mini-stat"><div><small>Currently failed</small><strong>{{ number_format($this->summary['sms_failed']) }}</strong></div></div>
</section>
<div class="panel history-panel"><div class="panel-heading"><div><h3>Broadcast History</h3><p>Sending success records provider acceptance, not confirmed delivery.</p></div><a class="text-button" href="{{ route('sms.templates.index') }}">Manage templates</a></div>
<div class="table-scroll"><table><thead><tr><th>Broadcast</th><th>Audience</th><th>Created</th><th>Sent / Recipients</th><th>Queued</th><th>Failed</th><th>Details</th></tr></thead><tbody>
    @forelse ($this->broadcasts as $blast)
        <tr wire:key="broadcast-{{ $blast->id }}"><td>#{{ $blast->id }}</td><td>{{ $blast->barangay?->name ?? 'All barangays' }}</td><td>{{ $blast->created_at->format('M j, Y g:i A') }}</td><td>{{ number_format($blast->sent_count) }} / {{ number_format($blast->recipient_count) }}</td><td>{{ number_format($blast->queued_count) }}</td><td>{{ number_format($blast->failed_count) }}</td><td><a class="text-button" href="{{ route('sms.blasts.show', $blast) }}">View</a></td></tr>
    @empty
        <tr><td colspan="7"><div class="empty-log-state"><strong>No broadcasts recorded</strong><span>Create a broadcast to queue messages for eligible recipients.</span></div></td></tr>
    @endforelse
</tbody></table></div>{{ $this->broadcasts->links() }}</div>
