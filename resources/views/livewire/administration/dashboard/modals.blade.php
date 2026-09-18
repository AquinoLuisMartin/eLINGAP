{{-- Administrative Modals --}}

{{-- Senior / Program / User Creation and Edit Modal --}}
@if ($modal)
    <div class="modal-backdrop" role="dialog" aria-modal="true" wire:keydown.escape="closeModal">
        <div class="modal-card">
            <div class="modal-heading">
                <div>
                    @if ($modal === 'senior')
                        <h2>Add New Senior</h2>
                        <p>Create a new senior citizen registry record.</p>
                    @elseif ($modal === 'program')
                        <h2>Create Program</h2>
                        <p>Set up a new benefits and programs cycle.</p>
                    @elseif ($modal === 'user')
                        <h2>{{ $editingUser ? 'Edit User' : 'Add User' }}</h2>
                        <p>{{ $editingUser ? 'Update role, email, or account details.' : 'Invite a staff member to the eLINGAP workspace.' }}</p>
                    @endif
                </div>
                <button type="button" class="icon-button" wire:click="closeModal" aria-label="Close dialog">
                    <x-admin.icon name="x" size="19" />
                </button>
            </div>

            <div class="modal-form">
                @if ($modal === 'senior')
                    <label class="field-label">
                        Full name
                        <input type="text" wire:model="seniorForm.name" placeholder="Enter full name" />
                    </label>
                    <label class="field-label">
                        Age
                        <input type="number" min="60" wire:model="seniorForm.age" placeholder="60" />
                    </label>
                    <label class="field-label">
                        Barangay
                        <select wire:model="seniorForm.barangay">
                            <option value="Poblacion">Poblacion</option>
                            <option value="Sta. Cruz">Sta. Cruz</option>
                            <option value="Kaybanban">Kaybanban</option>
                            <option value="San Gabriel">San Gabriel</option>
                        </select>
                    </label>
                @elseif ($modal === 'program')
                    <label class="field-label">
                        Program name
                        <input type="text" wire:model="programForm.name" placeholder="Enter program name" />
                    </label>
                    <label class="field-label">
                        Implementing agency
                        <input type="text" wire:model="programForm.agency" placeholder="Enter agency" />
                    </label>
                    <label class="field-label">
                        Budget allocation
                        <input type="text" wire:model="programForm.budget" placeholder="₱0" />
                    </label>
                    <label class="field-label">
                        Cycle
                        <select wire:model="programForm.cycle">
                            <option value="Q4 2026">Q4 2026</option>
                            <option value="Annual 2027">Annual 2027</option>
                        </select>
                    </label>
                @elseif ($modal === 'user')
                    <label class="field-label">
                        Full name
                        <input type="text" wire:model="userForm.name" placeholder="Enter full name" />
                    </label>
                    <label class="field-label">
                        Email address
                        <input type="email" wire:model="userForm.email" placeholder="name@osca.gov.ph" />
                    </label>
                    <label class="field-label">
                        Role
                        <select wire:model="userForm.role">
                            <option value="OSCA Staff">OSCA Staff</option>
                            <option value="Barangay Coordinator">Barangay Coordinator</option>
                            <option value="System Administrator">System Administrator</option>
                        </select>
                    </label>
                @endif
            </div>

            <div class="modal-actions">
                <button type="button" class="secondary-button" wire:click="closeModal">Cancel</button>
                @if ($modal === 'senior')
                    <button type="button" class="primary-button" wire:click="saveSenior">
                        <x-admin.icon name="check" size="16" /> Save record
                    </button>
                @elseif ($modal === 'program')
                    <button type="button" class="primary-button" wire:click="saveProgram">
                        <x-admin.icon name="check" size="16" /> Save program
                    </button>
                @elseif ($modal === 'user')
                    <button type="button" class="primary-button" wire:click="saveUser">
                        <x-admin.icon name="check" size="16" /> {{ $editingUser ? 'Save changes' : 'Save user' }}
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif

{{-- Profile Details Modal --}}
@if ($profileModal === 'profile')
    <div class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title" wire:keydown.escape="closeModal">
        <div class="modal-card profile-details-modal">
            <div class="modal-heading">
                <div>
                    <h2 id="profile-modal-title">Maria A.</h2>
                    <p>System Administrator · admin@elingap.gov.ph</p>
                </div>
                <button type="button" class="icon-button" wire:click="closeModal" aria-label="Close profile">
                    <x-admin.icon name="x" size="19" />
                </button>
            </div>
            <div class="profile-modal-grid">
                <div>
                    <small>Assigned Office</small>
                    <strong>OSCA Santa Maria Municipal Hall</strong>
                </div>
                <div>
                    <small>Account Status</small>
                    <strong class="health-value"><i></i> Active</strong>
                </div>
                <div>
                    <small>Last Active / Login</small>
                    <strong>Sep 14, 2024 · 07:48 AM</strong>
                </div>
                <div>
                    <small>Recent Activity</small>
                    <strong>18 actions this month</strong>
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
                    <p>Update the password for admin@elingap.gov.ph.</p>
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
            @if ($passwordError)
                <p class="form-error" role="alert">{{ $passwordError }}</p>
            @endif
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

{{-- CSV Export Progress Modal --}}
@if ($exportJob)
    <div class="modal-backdrop" role="dialog" aria-modal="true" x-data="{
        progress: 0,
        init() {
            let timer = setInterval(() => {
                this.progress = Math.min(this.progress + 20, 100);
                if (this.progress >= 100) {
                    clearInterval(timer);
                    setTimeout(() => {
                        let content = 'eLINGAP {{ $exportJob === 'registry' ? 'Senior Registry' : 'System Logs' }} Export\nGenerated,Data\nComplete,Yes\n';
                        let blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
                        let link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = 'elingap-{{ $exportJob }}-export.csv';
                        link.click();
                        $wire.triggerToast('CSV export downloaded successfully.');
                        $wire.set('exportJob', null);
                    }, 300);
                }
            }, 180);
        }
    }">
        <div class="export-modal modal-card">
            <div class="export-icon">
                <x-admin.icon name="download" size="20" />
            </div>
            <h2>Preparing CSV export</h2>
            <p>Your {{ $exportJob === 'registry' ? 'senior registry' : 'system logs' }} export is being prepared.</p>
            <div class="export-progress-track">
                <i :style="'width: ' + progress + '%;'"></i>
            </div>
            <div class="export-progress-meta">
                <span x-text="progress < 100 ? 'Collecting records...' : 'Download ready'"></span>
                <strong x-text="progress + '%'"></strong>
            </div>
            <button type="button" class="secondary-button" wire:click="$set('exportJob', null)">Cancel</button>
        </div>
    </div>
@endif

{{-- Template Placeholder Prompt Modal --}}
@if ($templatePrompt)
    <div class="modal-backdrop" role="dialog" aria-modal="true" wire:keydown.escape="$set('templatePrompt', null)">
        <div class="modal-card template-prompt-modal">
            <div class="modal-heading">
                <div>
                    <h2>Complete message details</h2>
                    <p>{{ $templatePrompt['name'] }} needs a few details before it is added.</p>
                </div>
                <button type="button" class="icon-button" wire:click="$set('templatePrompt', null)" aria-label="Close template prompt">
                    <x-admin.icon name="x" size="19" />
                </button>
            </div>
            <div class="template-variable-fields">
                @foreach ($templatePrompt['variables'] as $variable)
                    <label class="field-label">
                        {{ $variable === 'DATE' ? 'Date' : 'Barangay' }}
                        <input type="{{ $variable === 'DATE' ? 'date' : 'text' }}"
                            wire:model="templateValues.{{ $variable }}"
                            placeholder="{{ $variable === 'DATE' ? 'Choose a date' : 'Enter barangay' }}" />
                    </label>
                @endforeach
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" wire:click="$set('templatePrompt', null)">Cancel</button>
                <button type="button" class="primary-button" wire:click="applyTemplate">
                    <x-admin.icon name="check" size="16" /> Use template
                </button>
            </div>
        </div>
    </div>
@endif
