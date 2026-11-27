@php
    $accounts = $this->userCounts;
    $monitoring = $this->monitoring;
    $recordedAt = now();
@endphp

<x-staff.page-header title="Account and system overview">
    <a class="primary-button" href="{{ route('administration.users.create') }}">
        <x-admin.icon name="user-round-plus" size="18" /> Create account
    </a>
</x-staff.page-header>

<div class="dashboard-overview grid min-w-0 gap-5 text-[var(--ink)]">
    <dl class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" aria-label="Administration summary">
        @foreach ([
            'Total accounts' => [$accounts['total'], 'users'],
            'Active accounts' => [$accounts['active'], 'user-round-check'],
            'Suspended accounts' => [$accounts['suspended'], 'user-round-x'],
            'Failed sign-ins today' => [$monitoring['failed_sign_ins'], 'shield-alert'],
        ] as $label => [$value, $icon])
            <div class="grid min-w-0 content-start gap-2 rounded-lg border border-[var(--line)] bg-[var(--white)] p-3 sm:p-4" wire:key="admin-metric-{{ $loop->index }}">
                <dt class="flex items-start gap-2 text-sm font-medium text-[var(--muted)]">
                    <x-admin.icon :name="$icon" class="mt-0.5 shrink-0" />
                    {{ $label }}
                </dt>
                <dd class="text-2xl leading-none font-semibold tracking-tight tabular-nums wrap-anywhere sm:text-[2rem]">
                    {{ number_format($value) }}
                </dd>
            </div>
        @endforeach
    </dl>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="panel" aria-labelledby="account-administration-heading">
            <div class="panel-heading"><h3 id="account-administration-heading">Account administration</h3></div>
            <div class="overview-links">
                <button type="button" class="overview-link" wire:click="navigate('users')">
                    <span class="overview-icon tone-blue"><x-admin.icon name="shield-check" size="17" /></span>
                    <span><strong>Manage account access</strong><small>Activate or suspend staff accounts</small></span>
                    <x-admin.icon name="chevron-right" size="16" class="overview-link-arrow" />
                </button>
                <a class="overview-link" href="{{ route('administration.users.index') }}">
                    <span class="overview-icon tone-blue"><x-admin.icon name="contact-round" size="17" /></span>
                    <span><strong>Account directory</strong><small>Find staff accounts and edit their details</small></span>
                    <x-admin.icon name="chevron-right" size="16" class="overview-link-arrow" />
                </a>
                <a class="overview-link" href="{{ route('administration.password-reset-requests.index') }}">
                    <span class="overview-icon tone-blue"><x-admin.icon name="key-round" size="17" /></span>
                    <span><strong>Password reset requests</strong><small>Review staff requests to recover access</small></span>
                    <x-admin.icon name="chevron-right" size="16" class="overview-link-arrow" />
                </a>
                <button type="button" class="overview-link" wire:click="navigate('configuration')">
                    <span class="overview-icon tone-blue"><x-admin.icon name="settings" size="17" /></span>
                    <span><strong>System configuration</strong><small>Update office details and settings</small></span>
                    <x-admin.icon name="chevron-right" size="16" class="overview-link-arrow" />
                </button>
            </div>
        </section>

        <section class="panel" aria-labelledby="system-monitoring-heading">
            <div class="panel-heading">
                <div>
                    <h3 id="system-monitoring-heading">System monitoring</h3>
                    <p>Today as of <time datetime="{{ $recordedAt->toIso8601String() }}">{{ $recordedAt->format('g:i A') }}</time></p>
                </div>
                <button type="button" class="overview-text-button" wire:click="navigate('system-logs')">
                    View logs <x-admin.icon name="arrow-right" size="16" />
                </button>
            </div>
            <dl class="overview-monitor">
                @foreach ([
                    'Successful sign-ins' => [$monitoring['successful_sign_ins'], 'log-in'],
                    'Failed sign-ins' => [$monitoring['failed_sign_ins'], 'shield-alert'],
                    'Audit events' => [$monitoring['audit_events_today'], 'clipboard-list'],
                ] as $label => [$value, $icon])
                    <div wire:key="monitor-{{ $loop->index }}">
                        <dt><span class="overview-icon tone-blue"><x-admin.icon :name="$icon" size="17" /></span>{{ $label }}</dt>
                        <dd>{{ number_format($value) }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    <section class="panel activity-panel" aria-labelledby="recent-audit-heading">
        <div class="panel-heading">
            <h3 id="recent-audit-heading">Recent audit activity</h3>
            <button type="button" class="overview-text-button" wire:click="navigate('system-logs')">
                View all logs <x-admin.icon name="arrow-right" size="16" />
            </button>
        </div>
        @if ($this->recentActivity->isNotEmpty())
            <ul class="activity-list">
                @foreach ($this->recentActivity as $event)
                    @php
                        $eventTime = \Illuminate\Support\Carbon::parse($event->created_at);
                        $eventIcon = match ($event->action) {
                            'senior_citizen.id_generated' => 'id-card',
                            'senior_citizen.id_print_generated' => 'printer',
                            'senior_citizen.id_print_confirmed' => 'printer-check',
                            'senior_citizen.id_print_failed' => 'printer-x',
                            'senior_citizen.created', 'user.created' => 'user-round-plus',
                            'senior_citizen.updated', 'user.updated' => 'pencil',
                            'senior_citizen.verified' => 'user-round-check',
                            'senior_citizen.archived' => 'archive',
                            'user.access_changed' => 'shield-check',
                            'user.password_reset', 'PASSWORD_CHANGED', 'PASSWORD_RESET' => 'key-round',
                            'LOGIN' => 'log-in',
                            'LOGIN_FAILED' => 'shield-alert',
                            'LOGOUT' => 'log-out',
                            'senior_id.officials_updated' => 'settings',
                            'report.exported' => 'download',
                            default => 'file-text',
                        };
                    @endphp
                    <li class="activity-item" wire:key="activity-{{ $event->id }}">
                        <span class="overview-icon tone-blue"><x-admin.icon :name="$eventIcon" size="17" /></span>
                        <div>
                            <strong>{{ \App\Support\SystemEventLabel::for($event->action) }} - {{ \App\Support\SystemEventLabel::operator($event->operator_role, $event->operator_name) }}</strong>
                            <small>Audit event #{{ $event->id }}</small>
                        </div>
                        <time datetime="{{ $eventTime->toIso8601String() }}" title="{{ $eventTime->format('M j, Y g:i A') }}">{{ $eventTime->diffForHumans() }}</time>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="overview-empty">
                <span class="overview-icon tone-slate"><x-admin.icon name="clipboard-list" size="18" /></span>
                <p>No audit activity recorded yet.</p>
            </div>
        @endif
    </section>
</div>
