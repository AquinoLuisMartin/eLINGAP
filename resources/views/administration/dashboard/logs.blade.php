{{-- System Logs Page --}}
<section class="page-intro">
    <div>
        <p class="section-kicker">Administration</p>
        <h2>System Logs</h2>
        <p class="section-subtitle">Unified audit trails, authentication events, and SMS dispatch activity.</p>
    </div>
    <button type="button" class="secondary-button" wire:click="$set('exportJob', 'logs')">
        <x-admin.icon name="download" size="16" /> Export Logs
    </button>
</section>

<div class="panel table-panel unified-logs-panel">
    <div class="logs-summary">
        <div>
            <strong>{{ count($this->filteredLogs) }}</strong>
            <span>events in current view</span>
        </div>
        <div class="logs-legend">
            <span><i class="log-dot audit"></i> Audit</span>
            <span><i class="log-dot login"></i> Login</span>
            <span><i class="log-dot sms"></i> SMS</span>
        </div>
    </div>

    <div class="table-tools logs-tools">
        <div class="filter-pills">
            @foreach (['All Logs', 'Audit Trail', 'Login Events', 'SMS Dispatch'] as $category)
                <button type="button" class="{{ $logCategory === $category ? 'selected' : '' }}" wire:click="$set('logCategory', '{{ $category }}')">
                    {{ $category }}
                </button>
            @endforeach
        </div>
        <span class="logs-search-note">
            <x-admin.icon name="search" size="14" /> Global search is active
        </span>
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Category</th>
                    <th>User / Recipient</th>
                    <th>Description / Event Details</th>
                    <th>Source IP / Telco</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->filteredLogs as $log)
                    <tr wire:key="log-{{ $log['timestamp'] }}-{{ $log['source'] }}">
                        <td class="timestamp-cell">{{ $log['timestamp'] }}</td>
                        <td>
                            <span class="category-badge {{ strtolower(str_replace(' ', '-', $log['category'])) }}">
                                {{ $log['category'] }}
                            </span>
                        </td>
                        <td>{{ $log['subject'] }}</td>
                        <td class="detail-cell">{{ $log['detail'] }}</td>
                        <td>{{ $log['source'] }}</td>
                        <td>
                            <span class="status-badge {{ strtolower($log['status']) }}">
                                {{ $log['status'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-log-state">
                                <x-admin.icon name="search" size="21" />
                                <strong>No matching system logs</strong>
                                <span>Try another search term or category.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <span>Showing {{ count($this->filteredLogs) }} of {{ count($systemLogs) }} system events</span>
        <span class="logs-retention">
            <x-admin.icon name="archive" size="14" /> Retained for 90 days
        </span>
    </div>
</div>
