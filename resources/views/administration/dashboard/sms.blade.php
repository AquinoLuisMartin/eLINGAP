{{-- SMS Dispatcher Page --}}
<section class="page-intro">
    <div>
        <p class="section-kicker">Communications center</p>
        <h2>SMS Dispatcher</h2>
        <p class="section-subtitle">Create and monitor SMS broadcasts for senior citizens and barangay coordinators.</p>
    </div>
    <span class="credit-pill">
        <x-admin.icon name="sparkles" size="15" /> {{ number_format($credits) }} credits available
    </span>
</section>

<div class="sms-layout">
    <div class="panel broadcast-panel">
        <div class="panel-heading">
            <div>
                <h3>Create broadcast</h3>
                <p>Send a targeted message to registered recipients.</p>
            </div>
            <span class="soft-badge blue">Draft</span>
        </div>

        <label class="field-label">
            Target Barangay
            <select wire:model.live="smsBarangay">
                <option value="All 24 barangays">All 24 barangays</option>
                <option value="Poblacion">Poblacion</option>
                <option value="Sta. Cruz">Sta. Cruz</option>
                <option value="Kaybanban">Kaybanban</option>
            </select>
        </label>

        <label class="field-label">
            Message
            <textarea wire:model.live="smsMessage" placeholder="Type your message here..." rows="6"></textarea>
            <span class="field-helper">{{ mb_strlen($smsMessage) }} characters · Estimated 1 SMS per recipient</span>
        </label>

        <div class="broadcast-footer">
            <span>
                <x-admin.icon name="users" size="16" />
                Estimated recipients: <strong>{{ number_format($this->recipientCount) }}</strong>
            </span>
            <button type="button" class="primary-button" wire:click="scheduleBroadcast">
                <x-admin.icon name="send" size="16" /> Review &amp; Schedule
            </button>
        </div>
    </div>

    <div class="panel template-panel">
        <div class="panel-heading">
            <div>
                <h3>Quick Templates</h3>
                <p>Select a template to populate the message.</p>
            </div>
            <x-admin.icon name="sparkles" size="18" class="panel-spark" />
        </div>

        <div class="template-list">
            @foreach ($templates as $name => $text)
                <button type="button" class="template-button" wire:click="openTemplatePrompt('{{ $name }}')">
                    <span class="template-icon">
                        <x-admin.icon name="file-text" size="16" />
                    </span>
                    <span>
                        <strong>{{ $name }}</strong>
                        <small>{{ $text }}</small>
                    </span>
                    <x-admin.icon name="chevron-right" size="16" />
                </button>
            @endforeach
        </div>

        <div class="credit-estimate">
            <x-admin.icon name="gauge" size="18" />
            <div>
                <strong>Credit estimate</strong>
                <span>{{ number_format($this->recipientCount) }} credits for this broadcast</span>
            </div>
        </div>
    </div>
</div>

<div class="panel history-panel">
    <div class="panel-heading">
        <div>
            <h3>Broadcast history</h3>
            <p>Recent messages sent by your team.</p>
        </div>
        <button type="button" class="text-button">View all</button>
    </div>
    <table>
        <thead>
            <tr>
                <th>Campaign</th>
                <th>Audience</th>
                <th>Sent</th>
                <th>Delivery</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($broadcasts as $item)
                <tr wire:key="broadcast-{{ $item['campaign'] }}-{{ $item['sent'] }}">
                    <td>{{ $item['campaign'] }}</td>
                    <td>{{ $item['audience'] }}</td>
                    <td>{{ $item['sent'] }}</td>
                    <td>{{ $item['delivery'] }}</td>
                    <td>
                        <span class="status-badge {{ $item['status'] === 'Scheduled' ? 'pending' : 'active' }}">
                            {{ $item['status'] }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
