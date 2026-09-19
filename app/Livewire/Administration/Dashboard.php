<?php

namespace App\Livewire\Administration;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('eLINGAP — System Admin Dashboard')]
class Dashboard extends Component
{
    // Active navigation tab
    public string $active = 'dashboard';

    // Global search query
    public string $search = '';

    // Active UI theme
    public string $theme = 'light';

    // Active modal identifier or payload
    public ?string $modal = null;

    // Payload for user modal edit mode
    public ?array $editingUser = null;

    // Unread notification count
    public int $noticeCount = 5;

    // Toast notification text
    public string $toast = '';

    // Distribution chart period selection
    public string $period = 'Monthly';

    // Distribution chart visual presentation type
    public string $chartType = 'Area';

    // Registry records filter pill
    public string $recordFilter = 'All';

    // Target barangay for SMS broadcast
    public string $smsBarangay = 'All 24 barangays';

    // Draft SMS broadcast message
    public string $smsMessage = '';

    // Available SMS balance
    public int $credits = 12840;

    // Active log category filter
    public string $logCategory = 'All Logs';

    // User management search filter
    public string $userQuery = '';

    // User management status filter
    public string $userStatusFilter = 'All';

    // Active FAQ accordion index
    public ?int $openFaq = null;

    // Profile sub-modal view
    public ?string $profileModal = null;

    // Logout confirmation dialog visibility
    public bool $logoutConfirmOpen = false;

    // Active export modal type
    public ?string $exportJob = null;

    // Selected template data for placeholder replacement
    public ?array $templatePrompt = null;

    // Form inputs for modals and dynamic actions
    public array $seniorForm = ['name' => '', 'age' => '', 'barangay' => 'Poblacion'];

    public array $programForm = ['name' => '', 'agency' => '', 'budget' => '', 'cycle' => 'Q4 2026'];

    public array $userForm = ['name' => '', 'email' => '', 'role' => 'OSCA Staff'];

    public array $passwordForm = ['current' => '', 'next' => '', 'confirm' => ''];

    public string $passwordError = '';

    public array $templateValues = [];

    // System configuration state
    public array $settings = [
        'gateway' => 'Semaphore SMS Gateway',
        'sender' => 'OSCA-SM',
        'timeout' => '30',
        'sessions' => '3',
        'backup' => 'Every night · 02:00 AM',
        'require_2fa' => true,
        'lock_failed' => true,
    ];

    public bool $settingsSaved = false;

    // Senior citizen registry records
    public array $registryRecords = [
        ['SC-2026-01842', 'Maria Lourdes Santos', 'Poblacion', '72', 'Active', 'Sept. 17, 2026'],
        ['SC-2026-01791', 'Rogelio Dela Cruz', 'Sta. Cruz', '81', 'For Validation', 'Sept. 16, 2026'],
        ['SC-2026-01766', 'Carmen Reyes', 'Kaybanban', '67', 'Active', 'Sept. 16, 2026'],
        ['SC-2026-01702', 'Eduardo Mendoza', 'San Gabriel', '76', 'Pending', 'Sept. 15, 2026'],
        ['SC-2026-01688', 'Natividad Garcia', 'Bagong Nayon', '88', 'Active', 'Sept. 15, 2026'],
    ];

    // Government programs and funding cycles
    public array $programList = [
        ['name' => 'Social Pension for Indigent Senior Citizens', 'agency' => 'DSWD / OSCA', 'budget' => '₱4,500,000', 'used' => 78, 'cycle' => 'Q3 2026', 'status' => 'Active'],
        ['name' => 'Senior Citizens Medical Assistance', 'agency' => 'Municipal Health Office', 'budget' => '₱1,250,000', 'used' => 54, 'cycle' => 'Annual 2026', 'status' => 'Active'],
        ['name' => 'Food and Wellness Support', 'agency' => 'Municipality of Santa Maria', 'budget' => '₱860,000', 'used' => 32, 'cycle' => 'Q4 2026', 'status' => 'Upcoming'],
    ];

    // User accounts registry
    public array $userList = [
        ['name' => 'Maria A.', 'email' => 'maria.admin@elingap.gov.ph', 'role' => 'Municipal Administrator', 'barangay' => 'All barangays', 'status' => 'Active', 'lastLogin' => 'Today, 09:18 AM', 'created' => 'Jan. 08, 2024'],
        ['name' => 'Ana Villanueva', 'email' => 'ana.villanueva@osca.gov.ph', 'role' => 'OSCA Coordinator', 'barangay' => 'Poblacion', 'status' => 'Active', 'lastLogin' => 'Today, 08:42 AM', 'created' => 'Feb. 14, 2024'],
        ['name' => 'Rogelio Cruz', 'email' => 'rogelio.cruz@osca.gov.ph', 'role' => 'Benefits Officer', 'barangay' => 'Sta. Cruz', 'status' => 'Active', 'lastLogin' => 'Yesterday, 04:12 PM', 'created' => 'Mar. 22, 2024'],
        ['name' => 'Liza Mendoza', 'email' => 'liza.mendoza@osca.gov.ph', 'role' => 'Registry Clerk', 'barangay' => 'Kaybanban', 'status' => 'Active', 'lastLogin' => 'Yesterday, 02:30 PM', 'created' => 'Apr. 03, 2024'],
        ['name' => 'Paolo Reyes', 'email' => 'paolo.reyes@osca.gov.ph', 'role' => 'SMS Operator', 'barangay' => 'All barangays', 'status' => 'Active', 'lastLogin' => 'Sept. 15, 10:21 AM', 'created' => 'Apr. 18, 2024'],
        ['name' => 'Joseph Dela Cruz', 'email' => 'joseph.delacruz@osca.gov.ph', 'role' => 'Registry Clerk', 'barangay' => 'San Gabriel', 'status' => 'Suspended', 'lastLogin' => 'Sept. 02, 11:04 AM', 'created' => 'May. 06, 2024'],
        ['name' => 'Mila Santos', 'email' => 'mila.santos@osca.gov.ph', 'role' => 'Benefits Officer', 'barangay' => 'Bagong Nayon', 'status' => 'Inactive', 'lastLogin' => 'Aug. 28, 03:45 PM', 'created' => 'Jun. 12, 2024'],
    ];

    // Broadcast campaign history
    public array $broadcasts = [
        ['campaign' => 'Pension payout reminder', 'audience' => 'All barangays · 18,427', 'sent' => 'Sept. 12, 2026', 'delivery' => '98.6%', 'status' => 'Delivered'],
        ['campaign' => 'Health screening invitation', 'audience' => 'Kaybanban · 842', 'sent' => 'Sept. 08, 2026', 'delivery' => '99.2%', 'status' => 'Delivered'],
    ];

    // System audit, login, and SMS dispatch log entries
    public array $systemLogs = [
        ['timestamp' => 'Sept. 17, 2026 · 09:42 AM', 'category' => 'Audit Trail', 'subject' => 'Admin User', 'detail' => 'System configuration updated: SMS gateway settings', 'source' => '10.24.0.18', 'status' => 'Success'],
        ['timestamp' => 'Sept. 17, 2026 · 09:26 AM', 'category' => 'Audit Trail', 'subject' => 'Ana Villanueva', 'detail' => 'Senior record verified: Maria Lourdes Santos · SC-2026-01842', 'source' => '10.24.0.24', 'status' => 'Success'],
        ['timestamp' => 'Sept. 17, 2026 · 09:02 AM', 'category' => 'Login Events', 'subject' => 'Unknown user', 'detail' => 'Failed login attempt: 3 invalid credentials', 'source' => '172.16.8.42', 'status' => 'Failed'],
        ['timestamp' => 'Sept. 17, 2026 · 08:45 AM', 'category' => 'SMS Dispatch', 'subject' => 'Maria Lourdes Santos', 'detail' => 'Pension payout reminder delivered · MSG-882941', 'source' => 'Globe · 0917 555 0182', 'status' => 'Success'],
        ['timestamp' => 'Sept. 17, 2026 · 08:34 AM', 'category' => 'Login Events', 'subject' => 'Maria A. · Admin', 'detail' => 'Successful sign-in from registered workstation', 'source' => '10.24.0.18', 'status' => 'Success'],
        ['timestamp' => 'Sept. 17, 2026 · 08:15 AM', 'category' => 'SMS Dispatch', 'subject' => 'Rogelio Dela Cruz', 'detail' => 'Message delivery delayed by carrier', 'source' => 'Smart · 0908 421 0091', 'status' => 'Warning'],
        ['timestamp' => 'Sept. 16, 2026 · 05:12 PM', 'category' => 'Audit Trail', 'subject' => 'Admin User', 'detail' => 'Masterlist export downloaded: registry-sept-16.csv', 'source' => '10.24.0.18', 'status' => 'Critical'],
        ['timestamp' => 'Sept. 16, 2026 · 04:48 PM', 'category' => 'Login Events', 'subject' => 'j.delacruz@osca.gov.ph', 'detail' => 'Account locked after repeated failed authentication', 'source' => '192.168.4.19', 'status' => 'Failed'],
    ];

    // Quick message templates
    public array $templates = [
        'Pension Payout Reminder' => 'Mahal na Lolo/Lola, ipinaabot ng OSCA Santa Maria na ang inyong quarterly pension ay maaari nang kunin sa inyong designated barangay hall simula [DATE].',
        'ID Renewal Notice' => 'Paalala mula sa OSCA Santa Maria: Ang inyong OSCA ID ay mawawalan ng bisa sa [DATE]. Mangyaring bisitahin ang aming opisina para sa renewal. Dalhin ang 2 piraso',
        'Health Program Announcement' => 'Imbitasyon mula sa OSCA Santa Maria: Libreng medical consultation at health screening para sa lahat ng senior citizens sa [BARANGAY] sa [DATE]. Libre ang lahat',
    ];

    // Navigate to a dashboard module
    public function navigate(string $tab): void
    {
        $this->active = $tab;
    }

    // Toggle color theme mode
    public function toggleTheme(): void
    {
        $this->theme = $this->theme === 'light' ? 'dark' : 'light';
    }

    // Clear notice badge count
    public function clearNotices(): void
    {
        $this->noticeCount = 0;
    }

    // Show temporary toast message
    public function triggerToast(string $message): void
    {
        $this->toast = $message;
        $this->dispatch('toast-shown');
    }

    // Dismiss active toast message
    public function dismissToast(): void
    {
        $this->toast = '';
    }

    // Open an administrative modal dialog
    public function openModal(string $type, ?array $user = null): void
    {
        $this->modal = $type;
        $this->editingUser = $user;

        if ($type === 'user' && $user) {
            $this->userForm = [
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
        } elseif ($type === 'user') {
            $this->userForm = ['name' => '', 'email' => '', 'role' => 'OSCA Staff'];
        } elseif ($type === 'senior') {
            $this->seniorForm = ['name' => '', 'age' => '', 'barangay' => 'Poblacion'];
        } elseif ($type === 'program') {
            $this->programForm = ['name' => '', 'agency' => '', 'budget' => '', 'cycle' => 'Q4 2026'];
        }
    }

    // Close any active modal dialog
    public function closeModal(): void
    {
        $this->modal = null;
        $this->editingUser = null;
        $this->profileModal = null;
        $this->exportJob = null;
        $this->templatePrompt = null;
        $this->logoutConfirmOpen = false;
        $this->passwordError = '';
    }

    // Save a new senior citizen registry record
    public function saveSenior(): void
    {
        if (empty(trim($this->seniorForm['name'])) || empty(trim((string) $this->seniorForm['age']))) {
            return;
        }

        $id = 'SC-2026-'.rand(10000, 99999);
        $newRecord = [
            $id,
            $this->seniorForm['name'],
            $this->seniorForm['barangay'],
            (string) $this->seniorForm['age'],
            'Pending',
            'Just now',
        ];

        array_unshift($this->registryRecords, $newRecord);
        $this->closeModal();
        $this->triggerToast('Senior record saved successfully.');
    }

    // Save a newly configured government assistance program
    public function saveProgram(): void
    {
        if (empty(trim($this->programForm['name'])) || empty(trim($this->programForm['agency']))) {
            return;
        }

        $newProgram = [
            'name' => $this->programForm['name'],
            'agency' => $this->programForm['agency'],
            'budget' => $this->programForm['budget'] ?: '₱0',
            'used' => 0,
            'cycle' => $this->programForm['cycle'],
            'status' => 'Upcoming',
        ];

        array_unshift($this->programList, $newProgram);
        $this->closeModal();
        $this->triggerToast('Program saved successfully.');
    }

    // Save a new or updated administrative user account
    public function saveUser(): void
    {
        if (empty(trim($this->userForm['name'])) || empty(trim($this->userForm['email']))) {
            return;
        }

        if ($this->editingUser) {
            foreach ($this->userList as &$user) {
                if ($user['email'] === $this->editingUser['email']) {
                    $user['name'] = $this->userForm['name'];
                    $user['email'] = $this->userForm['email'];
                    $user['role'] = $this->userForm['role'];
                    break;
                }
            }
            $this->triggerToast('User account updated successfully.');
        } else {
            $this->userList[] = [
                'name' => $this->userForm['name'],
                'email' => $this->userForm['email'],
                'role' => $this->userForm['role'],
                'barangay' => 'All barangays',
                'status' => 'Active',
                'lastLogin' => 'Never',
                'created' => 'Today',
            ];
            $this->triggerToast('User account saved successfully.');
        }

        $this->closeModal();
    }

    // Toggle active and suspended status of a user
    public function toggleUserStatus(string $email): void
    {
        foreach ($this->userList as &$user) {
            if ($user['email'] === $email) {
                $next = $user['status'] === 'Active' ? 'Suspended' : 'Active';
                $user['status'] = $next;
                $this->triggerToast("{$user['name']} is now ".strtolower($next).'.');
                break;
            }
        }
    }

    // Save system configuration settings
    public function saveSettings(): void
    {
        $this->settingsSaved = true;
        $this->triggerToast('System configuration saved successfully.');
    }

    // Calculate recipient count for the selected barangay
    #[Computed]
    public function recipientCount(): int
    {
        return match ($this->smsBarangay) {
            'All 24 barangays' => 18427,
            'Poblacion' => 2418,
            'Sta. Cruz' => 1985,
            default => 842,
        };
    }

    // Schedule an SMS broadcast
    public function scheduleBroadcast(): void
    {
        if (empty(trim($this->smsMessage))) {
            $this->triggerToast('Enter a message before scheduling.');

            return;
        }

        $campaign = str_starts_with($this->smsMessage, 'Mahal na')
            ? 'Pension payout reminder'
            : (str_starts_with($this->smsMessage, 'Paalala') ? 'ID renewal notice' : 'Health screening invitation');

        $recipients = $this->recipientCount;

        array_unshift($this->broadcasts, [
            'campaign' => $campaign,
            'audience' => "{$this->smsBarangay} · ".number_format($recipients),
            'sent' => 'Just now',
            'delivery' => 'Queued',
            'status' => 'Scheduled',
        ]);

        $this->credits = max(0, $this->credits - $recipients);
        $this->smsMessage = '';
        $this->triggerToast('Broadcast scheduled successfully.');
    }

    // Open template prompt modal to fill in variable placeholders
    public function openTemplatePrompt(string $name): void
    {
        $text = $this->templates[$name] ?? '';
        preg_match_all('/\[([A-Z]+)\]/', $text, $matches);
        $variables = $matches[1] ?? [];

        $this->templatePrompt = [
            'name' => $name,
            'text' => $text,
            'variables' => $variables,
        ];

        $this->templateValues = array_fill_keys($variables, '');
    }

    // Apply template with replaced variable values
    public function applyTemplate(): void
    {
        if (! $this->templatePrompt) {
            return;
        }

        foreach ($this->templatePrompt['variables'] as $var) {
            if (empty(trim($this->templateValues[$var] ?? ''))) {
                return;
            }
        }

        $text = $this->templatePrompt['text'];
        foreach ($this->templateValues as $key => $val) {
            $text = str_replace("[{$key}]", $val, $text);
        }

        $this->smsMessage = $text;
        $this->templatePrompt = null;
    }

    // Filter registry records by search term and status
    #[Computed]
    public function filteredRecords(): array
    {
        return array_filter($this->registryRecords, function ($record) {
            $matchesFilter = $this->recordFilter === 'All' || $record[4] === $this->recordFilter;
            $haystack = strtolower(implode(' ', $record));
            $matchesSearch = empty($this->search) || str_contains($haystack, strtolower($this->search));

            return $matchesFilter && $matchesSearch;
        });
    }

    // Filter users by search term and account status
    #[Computed]
    public function filteredUsers(): array
    {
        return array_filter($this->userList, function ($user) {
            $matchesStatus = $this->userStatusFilter === 'All' || $user['status'] === $this->userStatusFilter;
            $haystack = strtolower("{$user['name']} {$user['email']} {$user['role']} {$user['barangay']}");
            $matchesSearch = empty($this->userQuery) || str_contains($haystack, strtolower($this->userQuery));

            return $matchesStatus && $matchesSearch;
        });
    }

    // Count statistics for user accounts
    #[Computed]
    public function userCounts(): array
    {
        return [
            'total' => count($this->userList),
            'active' => count(array_filter($this->userList, fn ($u) => $u['status'] === 'Active')),
            'suspended' => count(array_filter($this->userList, fn ($u) => $u['status'] === 'Suspended')),
            'inactive' => count(array_filter($this->userList, fn ($u) => $u['status'] === 'Inactive')),
        ];
    }

    // Filter system logs by category and global search
    #[Computed]
    public function filteredLogs(): array
    {
        return array_filter($this->systemLogs, function ($log) {
            $matchesCategory = $this->logCategory === 'All Logs' || $log['category'] === $this->logCategory;
            $query = strtolower(trim($this->search));
            $matchesSearch = empty($query) || str_contains(strtolower(implode(' ', $log)), $query);

            return $matchesCategory && $matchesSearch;
        });
    }

    // Toggle FAQ item expansion
    public function toggleFaq(int $index): void
    {
        $this->openFaq = $this->openFaq === $index ? null : $index;
    }

    // Update administrator password
    public function updatePassword(): void
    {
        if (empty($this->passwordForm['current']) || empty($this->passwordForm['next']) || empty($this->passwordForm['confirm'])) {
            $this->passwordError = 'Complete all password fields.';

            return;
        }

        if (strlen($this->passwordForm['next']) < 8) {
            $this->passwordError = 'New password must be at least 8 characters.';

            return;
        }

        if ($this->passwordForm['next'] !== $this->passwordForm['confirm']) {
            $this->passwordError = 'New password and confirmation do not match.';

            return;
        }

        $this->passwordForm = ['current' => '', 'next' => '', 'confirm' => ''];
        $this->passwordError = '';
        $this->profileModal = null;
        $this->triggerToast('Password updated successfully.');
    }

    // Render the Livewire component view
    public function render()
    {
        return view('administration.dashboard.panel');
    }
}
