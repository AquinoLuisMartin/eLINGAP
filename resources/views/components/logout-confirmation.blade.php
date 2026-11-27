<div {{ $attributes->class(['modal-backdrop', 'logout-modal-backdrop']) }} role="dialog" aria-modal="true" aria-labelledby="logout-modal-title">
    <div class="modal-card logout-modal">
        <div class="logout-icon">
            <x-admin.icon name="log-out" size="20" />
        </div>
        <h2 id="logout-modal-title">Are you sure you want to log out?</h2>

        <div class="modal-actions">
            {{ $cancel }}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="danger-button">
                    <x-admin.icon name="log-out" size="16" /> Log Out
                </button>
            </form>
        </div>
    </div>
</div>
