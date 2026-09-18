{{-- Notifications Slide-in Drawer --}}
<div class="notification-drawer-backdrop" x-show="notificationsOpen" x-transition.opacity @click="notificationsOpen = false" style="display: none;">
    <aside class="notification-drawer" @click.stop>
        <div class="drawer-heading">
            <div>
                <span class="section-kicker">System center</span>
                <h2>Notifications</h2>
            </div>
            <button type="button" class="icon-button" @click="notificationsOpen = false" aria-label="Close notifications">
                <x-admin.icon name="x" size="19" />
            </button>
        </div>

        <div class="drawer-alert critical">
            <span class="drawer-alert-icon">
                <x-admin.icon name="alert-triangle" size="17" />
            </span>
            <div>
                <strong>3 pending ID verifications</strong>
                <p>Senior records are waiting for document review.</p>
                <button type="button" class="drawer-link" @click="notificationsOpen = false; $wire.set('recordFilter', 'Pending'); $wire.navigate('records')">
                    Review queue →
                </button>
            </div>
            <time>Now</time>
        </div>

        <div class="drawer-alert warning">
            <span class="drawer-alert-icon">
                <x-admin.icon name="message-square" size="17" />
            </span>
            <div>
                <strong>SMS credits running low</strong>
                <p>12,840 credits remain for scheduled broadcasts.</p>
                <button type="button" class="drawer-link" @click="notificationsOpen = false; $wire.navigate('sms')">
                    Open SMS Dispatcher →
                </button>
            </div>
            <time>12m</time>
        </div>

        <div class="drawer-alert success">
            <span class="drawer-alert-icon">
                <x-admin.icon name="check" size="17" />
            </span>
            <div>
                <strong>System backup completed</strong>
                <p>Nightly backup finished without errors.</p>
            </div>
            <time>1h</time>
        </div>

        <button type="button" class="secondary-button drawer-footer-button" @click="notificationsOpen = false; $wire.clearNotices()">
            Mark all as read
        </button>
    </aside>
</div>
