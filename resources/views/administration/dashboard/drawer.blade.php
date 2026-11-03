<div class="notification-drawer-backdrop" x-show="notificationsOpen" x-transition.opacity style="display: none;"><aside class="notification-drawer" @click.stop>
    <div class="drawer-heading"><div><span class="section-kicker">Current queues</span><h2>Needs Attention</h2></div><button type="button" class="icon-button" @click="notificationsOpen = false" aria-label="Close notifications"><x-admin.icon name="x" size="19" /></button></div>
    @if ($this->summary['pending'] > 0)
        <div class="drawer-alert warning"><div><strong>{{ number_format($this->summary['pending']) }} pending verifications</strong><p>Senior records awaiting review.</p><button type="button" class="drawer-link" @click="notificationsOpen = false; $wire.set('recordFilter', 'PENDING'); $wire.navigate('records')">Review queue</button></div></div>
    @endif
    @if ($this->summary['pending'] === 0)
        <p class="chart-note">No pending verifications.</p>
    @endif
</aside></div>
