<section class="page-intro"><div><p class="section-kicker">Registry management</p><h2>Senior Records Registry</h2><p class="section-subtitle">{{ number_format($this->summary['seniors']) }} registered seniors across {{ number_format($this->barangayCounts->count()) }} barangays.</p></div><a class="primary-button" href="{{ route('senior-citizens.create') }}"><x-admin.icon name="plus" size="17" /> Add New Senior</a></section>
<div class="stat-strip">
    @foreach ($this->registryCounts as $label => $total)
        <div class="mini-stat" wire:key="registry-count-{{ $loop->index }}"><span class="mini-stat-dot blue"></span><div><small>{{ $label }}</small><strong>{{ number_format($total) }}</strong></div></div>
    @endforeach
</div>
<div class="panel table-panel" wire:loading.class="data-refreshing" wire:target="search,recordFilter,previousPage,nextPage,gotoPage">
    <div class="table-tools"><div class="filter-pills">
        @foreach (['All', 'PENDING', 'VERIFIED', 'ARCHIVED', 'DECEASED'] as $filter)
            <button wire:key="record-filter-{{ $filter }}" type="button" class="{{ $recordFilter === $filter ? 'selected' : '' }}" wire:click="$set('recordFilter', '{{ $filter }}')">{{ ucfirst(strtolower($filter)) }}</button>
        @endforeach
    </div></div>
    <p wire:loading wire:target="search,recordFilter" role="status">Updating records...</p>
    <div class="table-scroll"><table><thead><tr><th>OSCA / Registration ID</th><th>Senior citizen</th><th>Barangay</th><th>Age</th><th>Status</th><th>Last updated</th><th>Details</th></tr></thead><tbody>
        @forelse ($this->filteredRecords as $record)
            <tr wire:key="record-{{ $record->id }}"><td>{{ $record->osca_id_number ?? $record->registration_number }}</td><td>{{ $record->full_name }}</td><td>{{ $record->barangay?->name ?? 'Unassigned' }}</td><td>{{ $record->birth_date->age }}</td><td><span class="status-badge {{ $record->status->value === 'VERIFIED' ? 'active' : 'pending' }}">{{ ucfirst(strtolower($record->status->value)) }}</span></td><td>{{ $record->updated_at->format('M j, Y') }}</td><td><a class="text-button" href="{{ route('senior-citizens.show', $record) }}">View</a></td></tr>
        @empty
            <tr><td colspan="7"><div class="empty-log-state"><strong>No matching senior records</strong><span>Add a record or adjust your search and status filter.</span></div></td></tr>
        @endforelse
    </tbody></table></div>
    {{ $this->filteredRecords->links() }}
    <div class="table-footer"><span>Showing {{ $this->filteredRecords->firstItem() ?? 0 }}-{{ $this->filteredRecords->lastItem() ?? 0 }} of {{ number_format($this->filteredRecords->total()) }} matching records</span></div>
</div>
