{{-- System Configuration Page --}}
<section class="page-intro">
    <div>
        <p class="section-kicker">Administration</p>
        <h2>System Configuration</h2>
        <p class="section-subtitle">Manage SMS gateway settings, security access, and automated backups.</p>
    </div>
    <button type="button" class="primary-button" wire:click="saveSettings">
        <x-admin.icon name="check" size="16" /> {{ $settingsSaved ? 'Saved' : 'Save Changes' }}
    </button>
</section>

<div class="config-grid">
    {{-- SMS Gateway Card --}}
    <div class="panel config-card">
        <div class="panel-heading">
            <div>
                <h3>SMS Gateway</h3>
                <p>Connection settings for outbound notifications.</p>
            </div>
            <x-admin.icon name="send" size="18" class="config-icon" />
        </div>

        <label class="field-label config-field">
            Gateway provider
            <input type="text" wire:model="settings.gateway" />
        </label>

        <label class="field-label config-field">
            Sender name
            <input type="text" wire:model="settings.sender" />
        </label>

        <label class="field-label config-field">
            Request timeout (seconds)
            <input type="number" wire:model="settings.timeout" />
        </label>
    </div>

    {{-- Security & Access Card --}}
    <div class="panel config-card">
        <div class="panel-heading">
            <div>
                <h3>Security &amp; Access</h3>
                <p>Protect administrative sessions and operator access.</p>
            </div>
            <x-admin.icon name="shield-check" size="18" class="config-icon" />
        </div>

        <label class="field-label config-field">
            Maximum concurrent sessions
            <input type="number" wire:model="settings.sessions" />
        </label>

        <div class="config-toggle">
            <div>
                <strong>Require two-factor authentication</strong>
                <small>Require a verification code for admin accounts.</small>
            </div>
            <input type="checkbox" wire:model="settings.require_2fa" />
        </div>

        <div class="config-toggle">
            <div>
                <strong>Lock after failed attempts</strong>
                <small>Lock accounts after 5 unsuccessful sign-ins.</small>
            </div>
            <input type="checkbox" wire:model="settings.lock_failed" />
        </div>
    </div>

    {{-- Automated Backups Card --}}
    <div class="panel config-card">
        <div class="panel-heading">
            <div>
                <h3>Automated Backups</h3>
                <p>Keep a recoverable copy of system data.</p>
            </div>
            <x-admin.icon name="archive" size="18" class="config-icon" />
        </div>

        <label class="field-label config-field">
            Backup schedule
            <input type="text" wire:model="settings.backup" />
        </label>

        <div class="backup-status">
            <x-admin.icon name="check" size="16" />
            <span>
                <strong>Last backup completed</strong>
                <small>Today at 02:00 AM · No issues detected</small>
            </span>
        </div>
    </div>
</div>
