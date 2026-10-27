@php
    $accounts = $this->userCounts;
    $monitoring = $this->monitoring;
@endphp
<div class="dashboard-overview">
<section class="page-intro">
    <div>
        <h2>Account and system overview</h2>
        <p class="section-subtitle">Manage account access and review recorded system activity.</p>
    </div>
    <a class="primary-button" href="{{ route('administration.users.create') }}"><x-admin.icon name="plus" size="17" /> Create account</a>
</section>
<section class="metric-grid" aria-label="Administration summary">
    @foreach (['Total accounts' => [$accounts['total'], 'Registered system accounts'], 'Active accounts' => [$accounts['active'], 'Accounts with system access'], 'Suspended accounts' => [$accounts['suspended'], 'Accounts with access disabled'], 'Failed sign-ins today' => [$monitoring['failed_sign_ins'], 'Recorded unsuccessful sign-in attempts']] as $label => [$value, $description])
        <div class="metric-card" wire:key="admin-metric-{{ $loop->index }}">
            <div class="metric-label">{{ $label }}</div>
            <div class="metric-value">{{ number_format($value) }}</div>
            <div class="metric-bottom">{{ $description }}</div>
        </div>
    @endforeach
</section>
<section class="dashboard-grid">
    <div class="panel tasks-panel">
        <div class="panel-heading"><div><h3>Account administration</h3><p>Create staff accounts and manage access.</p></div></div>
        <a class="task-row" href="{{ route('administration.users.create') }}"><span class="task-icon blue"><x-admin.icon name="users" size="16" /></span><span><strong>Create an account</strong><small>Set the account role and sign-in credentials.</small></span></a>
        <button type="button" class="task-row" wire:click="navigate('users')"><span class="task-icon blue"><x-admin.icon name="shield-check" size="16" /></span><span><strong>Manage account access</strong><small>Review active and suspended accounts.</small></span></button>
        <a class="task-row" href="{{ route('administration.users.index') }}"><span class="task-icon blue"><x-admin.icon name="users" size="16" /></span><span><strong>Account directory</strong><small>Review account details and available password actions.</small></span></a>
        <button type="button" class="task-row" wire:click="navigate('configuration')"><span class="task-icon blue"><x-admin.icon name="settings" size="16" /></span><span><strong>System configuration</strong><small>Review office and system settings.</small></span></button>
    </div>
    <div class="panel table-panel">
        <div class="panel-heading"><div><h3>System monitoring</h3><p>Recorded activity as of {{ now()->format('M j, Y g:i A') }}.</p></div><button type="button" class="text-button" wire:click="navigate('system-logs')">View logs</button></div>
        <div class="table-scroll" role="region" aria-label="Recent activity" tabindex="0"><table>
            <caption class="sr-only">Authentication, audit, and SMS monitoring counts</caption>
            <thead><tr><th scope="col">Activity</th><th scope="col">Count</th><th scope="col">Period</th></tr></thead>
            <tbody>
                @foreach (['Successful sign-ins' => [$monitoring['successful_sign_ins'], 'Today'], 'Failed sign-ins' => [$monitoring['failed_sign_ins'], 'Today'], 'Audit events' => [$monitoring['audit_events_today'], 'Today'], 'Queued SMS messages' => [$monitoring['sms_queued'], 'Current queue'], 'Failed SMS messages' => [$monitoring['sms_failed'], 'All recorded failures']] as $label => [$value, $period])
                    <tr wire:key="monitor-{{ $loop->index }}"><th scope="row">{{ $label }}</th><td>{{ number_format($value) }}</td><td>{{ $period }}</td></tr>
                @endforeach
            </tbody>
        </table></div>
        <button type="button" class="text-button" wire:click="navigate('sms')">Review SMS delivery records</button>
    </div>
</section>
<section class="panel activity-panel">
    <div class="panel-heading"><div><h3>Recent audit activity</h3><p>Latest recorded actions across the system.</p></div><button type="button" class="text-button" wire:click="navigate('system-logs')">View all</button></div>
    <div class="activity-list">
        @forelse ($this->recentActivity as $event)
            <div class="activity-item" wire:key="activity-{{ $event->id }}"><span class="activity-icon blue"><x-admin.icon name="activity" size="15" /></span><div><strong>{{ \App\Support\SystemEventLabel::for($event->action) }} - {{ \App\Support\SystemEventLabel::operator($event->operator_role, $event->operator_name) }}</strong><small>Audit event #{{ $event->id }}</small></div><time datetime="{{ \Illuminate\Support\Carbon::parse($event->created_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($event->created_at)->diffForHumans() }}</time></div>
        @empty
            <p class="chart-note">No audit activity recorded yet.</p>
        @endforelse
    </div>
</section>

</div>
