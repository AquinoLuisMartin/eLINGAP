<section class="page-intro"><div><p class="section-kicker">Administration</p><h2>System Logs</h2><p class="section-subtitle">Recorded audit, authentication, and SMS events.</p></div></section>
<div class="panel table-panel" wire:loading.class="data-refreshing" wire:target="search,logCategory">
    <div class="logs-summary"><div><strong>{{ number_format($this->filteredLogs->total()) }}</strong><span>matching events</span></div></div>
    <div class="table-tools"><div class="filter-pills">
        @foreach (['All Logs', 'Audit Trail', 'Login Events', 'SMS Dispatch'] as $category)
            <button wire:key="log-category-{{ $loop->index }}" type="button" class="{{ $logCategory === $category ? 'selected' : '' }}" wire:click="$set('logCategory', '{{ $category }}')">{{ $category }}</button>
        @endforeach
    </div></div>
    <p wire:loading wire:target="search,logCategory" role="status">Updating events...</p>
    <div class="table-scroll"><table><thead><tr><th>Timestamp</th><th>Category</th><th>Event ID</th><th>Event</th><th>Status</th></tr></thead><tbody>
        @forelse ($this->filteredLogs as $log)
            <tr wire:key="log-{{ $log->category }}-{{ $log->id }}"><td>{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('M j, Y g:i A') }}</td><td><span class="category-badge {{ strtolower(str_replace(' ', '-', $log->category)) }}">{{ $log->category }}</span></td><td>#{{ $log->id }}</td><td class="detail-cell">{{ $log->detail }}</td><td>{{ $log->status }}</td></tr>
        @empty
            <tr><td colspan="5"><div class="empty-log-state"><strong>No matching system events</strong><span>Try another search term or category.</span></div></td></tr>
        @endforelse
    </tbody></table></div>{{ $this->filteredLogs->links() }}
    <div class="table-footer"><span>Showing {{ $this->filteredLogs->firstItem() ?? 0 }}-{{ $this->filteredLogs->lastItem() ?? 0 }} of {{ number_format($this->filteredLogs->total()) }} matching events</span></div>
</div>
