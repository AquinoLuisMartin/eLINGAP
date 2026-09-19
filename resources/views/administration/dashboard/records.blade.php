{{-- Senior Records Registry Page --}}
<section class="page-intro">
    <div>
        <p class="section-kicker">Registry management</p>
        <h2>Senior Records Registry</h2>
        <p class="section-subtitle">Masterlist of 18,427 registered senior citizens across 24 barangays.</p>
    </div>
    <div class="intro-actions">
        <button type="button" class="secondary-button" wire:click="$set('exportJob', 'registry')">
            <x-admin.icon name="download" size="16" /> Export CSV
        </button>
        <button type="button" class="primary-button" wire:click="openModal('senior')">
            <x-admin.icon name="plus" size="17" /> Add New Senior
        </button>
    </div>
</section>

<div class="stat-strip">
    <div class="mini-stat">
        <span class="mini-stat-dot blue"></span>
        <div>
            <small>Total Registry</small>
            <strong>18,427</strong>
        </div>
    </div>
    <div class="mini-stat">
        <span class="mini-stat-dot blue"></span>
        <div>
            <small>Age 60–69</small>
            <strong>8,240</strong>
        </div>
    </div>
    <div class="mini-stat">
        <span class="mini-stat-dot violet"></span>
        <div>
            <small>Age 70–79</small>
            <strong>6,912</strong>
        </div>
    </div>
    <div class="mini-stat">
        <span class="mini-stat-dot amber"></span>
        <div>
            <small>Age 80+</small>
            <strong>3,275</strong>
        </div>
    </div>
</div>

<div class="panel table-panel">
    <div class="table-tools">
        <div class="filter-pills">
            @foreach (['All', 'Active', 'For Validation', 'Pending'] as $filter)
                <button type="button" class="{{ $recordFilter === $filter ? 'selected' : '' }}" wire:click="$set('recordFilter', '{{ $filter }}')">
                    {{ $filter }}
                </button>
            @endforeach
        </div>
        <button type="button" class="filter-button">
            <x-admin.icon name="sliders-horizontal" size="16" /> More filters
        </button>
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>OSCA ID</th>
                    <th>Senior citizen</th>
                    <th>Barangay</th>
                    <th>Age</th>
                    <th>Status</th>
                    <th>Last updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->filteredRecords as $record)
                    <tr wire:key="record-{{ $record[0] }}">
                        <td>{{ $record[0] }}</td>
                        <td>{{ $record[1] }}</td>
                        <td>{{ $record[2] }}</td>
                        <td>{{ $record[3] }}</td>
                        <td>
                            <span class="status-badge {{ strtolower(str_replace(' ', '-', $record[4])) }}">
                                {{ $record[4] }}
                            </span>
                        </td>
                        <td>{{ $record[5] }}</td>
                        <td>
                            <button type="button" class="icon-button" aria-label="Record options">
                                <x-admin.icon name="more-horizontal" size="17" />
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--muted);">
                            No matching senior records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <span>Showing {{ count($this->filteredRecords) }} of 18,427 records</span>
        <div>
            <button type="button" class="icon-button" aria-label="Previous page">
                <x-admin.icon name="chevron-left" size="17" />
            </button>
            <span class="page-number">1</span>
            <button type="button" class="icon-button" aria-label="Next page">
                <x-admin.icon name="chevron-right" size="17" />
            </button>
        </div>
    </div>
</div>
