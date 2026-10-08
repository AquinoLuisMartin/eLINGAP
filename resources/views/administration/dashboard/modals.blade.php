{{-- Profile Details Modal --}}
@if ($profileModal === 'profile')
    <div class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title" wire:keydown.escape="closeModal">
        <div class="modal-card profile-details-modal">
            <div class="modal-heading">
                <div>
                    <h2 id="profile-modal-title">{{ $this->currentUser->full_name }}</h2>
                    <p>{{ $this->currentUser->role->name->label() }} &middot; {{ $this->currentUser->email }}</p>
                </div>
                <button type="button" class="icon-button" wire:click="closeModal" aria-label="Close profile">
                    <x-admin.icon name="x" size="19" />
                </button>
            </div>
            <div class="profile-modal-grid">
                <div>
                    <small>Username</small>
                    <strong>{{ $this->currentUser->username }}</strong>
                </div>
                <div>
                    <small>Account Status</small>
                    <strong class="health-value"><i></i>{{ $this->currentUser->is_active ? 'Active' : 'Suspended' }}</strong>
                </div>
                <div>
                    <small>Last Login</small>
                    <strong>{{ $this->currentUser->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</strong>
                </div>
                <div>
                    <small>Account Created</small>
                    <strong>{{ $this->currentUser->created_at->format('M j, Y') }}</strong>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="primary-button" wire:click="closeModal">Done</button>
            </div>
        </div>
    </div>
@endif

{{-- Change Password Modal --}}
@if ($profileModal === 'password')
    <div class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="password-modal-title" wire:keydown.escape="closeModal">
        <div class="modal-card password-modal">
            <div class="modal-heading">
                <div>
                    <h2 id="password-modal-title">Change Password</h2>
                    <p>Update the password for {{ $this->currentUser->username }}.</p>
                </div>
                <button type="button" class="icon-button" wire:click="closeModal" aria-label="Close password dialog">
                    <x-admin.icon name="x" size="19" />
                </button>
            </div>
            <label class="field-label">
                Current password
                <input type="password" wire:model="passwordForm.current" />
            </label>
            <label class="field-label">
                New password
                <input type="password" wire:model="passwordForm.next" />
            </label>
            <label class="field-label">
                Confirm password
                <input type="password" wire:model="passwordForm.confirm" />
            </label>
            @foreach (['current', 'next', 'confirm'] as $field)
                @error('passwordForm.'.$field)
                    <p class="form-error" role="alert">{{ $message }}</p>
                @enderror
            @endforeach
            <div class="modal-actions">
                <button type="button" class="secondary-button" wire:click="closeModal">Cancel</button>
                <button type="button" class="primary-button" wire:click="updatePassword">
                    <x-admin.icon name="check" size="16" /> Update password
                </button>
            </div>
        </div>
    </div>
@endif

{{-- Logout Confirmation Modal --}}
@if ($logoutConfirmOpen)
    <div class="modal-backdrop logout-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="logout-modal-title" wire:keydown.escape="$set('logoutConfirmOpen', false)">
        <div class="modal-card logout-modal">
            <div class="logout-icon">
                <x-admin.icon name="log-out" size="20" />
            </div>
            <h2 id="logout-modal-title">Are you sure you want to log out?</h2>
            <p>You will be returned to the public eLINGAP landing page.</p>
            <div class="modal-actions">
                <button type="button" class="secondary-button" wire:click="$set('logoutConfirmOpen', false)">Cancel</button>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="danger-button">
                        <x-admin.icon name="log-out" size="16" /> Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
