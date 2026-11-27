<section class="page-intro"><div><p class="section-kicker">Administration</p><h2>System Logs</h2></div></section>
<div class="panel table-panel" wire:loading.class="data-refreshing" wire:target="search,logCategory">
    <div class="logs-summary"><div><strong>{{ number_format($this->filteredLogs->total()) }}</strong><span>matching events</span></div></div>
    <div class="table-tools"><div class="filter-pills">
        @foreach (['All Logs', 'Audit Trail', 'Login Events'] as $category)
            <button wire:key="log-category-{{ $loop->index }}" type="button" class="{{ $logCategory === $category ? 'selected' : '' }}" wire:click="$set('logCategory', '{{ $category }}')">{{ $category }}</button>
        @endforeach
    </div></div>
    <p wire:loading wire:target="search,logCategory" role="status">Updating events...</p>
    <div class="table-scroll" role="region" aria-label="Operational logs" tabindex="0"><table><thead><tr><th scope="col">Timestamp</th><th scope="col">Category</th><th scope="col">Event ID</th><th scope="col">Event</th><th scope="col">Status</th></tr></thead><tbody>
        @forelse ($this->filteredLogs as $log)
            <tr wire:key="log-{{ $log->category }}-{{ $log->id }}">
                <td>{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('M j, Y g:i A') }}</td>
                <td><span class="ui-label">{{ $log->category }}</span></td>
                <td>#{{ $log->id }}</td>
                <td class="detail-cell">
                    {{ \App\Support\SystemEventLabel::for($log->detail, $log->category) }} - {{ \App\Support\SystemEventLabel::operator($log->operator_role, $log->operator_name) }}
                    @if ($log->senior_id !== null)
                        <div><strong>Senior citizen:</strong> {{ $log->senior_name ?: 'Record #'.$log->senior_id.' (name unavailable)' }}</div>
                        @foreach (['first_name' => 'First name', 'middle_name' => 'Middle name', 'last_name' => 'Last name', 'name_suffix' => 'Suffix'] as $field => $label)
                            @if ($log->{'old_'.$field} !== $log->{'new_'.$field})
                                <div>{{ $label }} changed from {{ $log->{'old_'.$field} ?: 'Not recorded' }} to {{ $log->{'new_'.$field} ?: 'Not recorded' }}</div>
                            @endif
                        @endforeach
                    @endif
                </td>
                <td><span class="ui-label">{{ $log->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty-log-state"><strong>No matching system events</strong><span>Try another search term or category.</span></div></td></tr>
        @endforelse
    </tbody></table></div>{{ $this->filteredLogs->links('administration.dashboard.pagination') }}
    <div class="table-footer"><span>Showing {{ $this->filteredLogs->firstItem() ?? 0 }}-{{ $this->filteredLogs->lastItem() ?? 0 }} of {{ number_format($this->filteredLogs->total()) }} matching events</span></div>
</div>
