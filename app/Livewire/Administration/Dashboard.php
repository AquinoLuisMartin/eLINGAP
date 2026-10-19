<?php

namespace App\Livewire\Administration;

use App\Enums\LoginEvent;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\BenefitPayment;
use App\Models\Payout;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SmsBlast;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Rules\PasswordInput;
use App\Services\Auth\LoginLogger;
use App\Services\Reports\AdminDashboardReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('eLINGAP')]
class Dashboard extends Component
{
    use WithPagination;

    // Active navigation tab
    #[Url(as: 'tab', history: true)]
    public string $active = 'dashboard';

    // Global search query
    public string $search = '';

    // Active UI theme
    public string $theme = 'light';

    // Toast notification text
    public string $toast = '';

    // Distribution chart period selection
    public string $period = 'Monthly';

    // Distribution chart visual presentation type
    public string $chartType = 'Area';

    // Registry records filter pill
    public string $recordFilter = 'All';

    // Active log category filter
    public string $logCategory = 'All Logs';

    // User management search filter
    public string $userQuery = '';

    // User management status filter
    public string $userStatusFilter = 'All';

    // Profile sub-modal view
    public ?string $profileModal = null;

    // Logout confirmation dialog visibility
    public bool $logoutConfirmOpen = false;

    public array $passwordForm = ['current' => '', 'next' => '', 'confirm' => ''];

    public function mount(): void
    {
        $this->navigate($this->active);
    }

    // Navigate to a dashboard module.
    public function navigate(string $tab): void
    {
        abort_unless(in_array($tab, ['dashboard', 'records', 'programs', 'sms', 'users', 'system-logs', 'configuration', 'help'], true), 404);
        $this->active = $tab;
        $this->resetPage('recordsPage');
        $this->resetPage('logsPage');
        $this->resetPage('programsPage');
        $this->resetPage('broadcastsPage');
    }

    // Toggle color theme mode
    public function toggleTheme(): void
    {
        $this->theme = $this->theme === 'light' ? 'dark' : 'light';
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

    public function boot(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    #[Computed]
    public function currentUser(): User
    {
        return auth()->user()->loadMissing('role');
    }

    // Close any active modal dialog
    public function closeModal(): void
    {
        $this->profileModal = null;
        $this->logoutConfirmOpen = false;
        $this->reset('passwordForm');
        $this->resetValidation();
    }

    public function toggleUserStatus(int $userId): void
    {
        $user = User::findOrFail($userId);
        Gate::authorize('manageAccess', $user);

        DB::transaction(function () use ($user): void {
            $wasActive = $user->is_active;
            $user->update(['is_active' => ! $wasActive]);
            AuditLog::create(['user_id' => auth()->id(), 'action' => 'user.access_changed', 'auditable_type' => User::class, 'auditable_id' => $user->id, 'old_values' => ['is_active' => $wasActive], 'new_values' => ['is_active' => $user->is_active], 'ip_address' => request()->ip()]);
        });
        unset($this->filteredUsers, $this->userCounts);

        $this->triggerToast($user->full_name.' is now '.($user->is_active ? 'active' : 'suspended').'.');
    }

    public function updatedUserQuery(): void
    {
        $this->resetPage();
    }

    public function updatedUserStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function filteredUsers(): LengthAwarePaginator
    {
        return User::query()
            ->select(['id', 'role_id', 'username', 'email', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'is_active', 'last_login_at', 'created_at'])
            ->with('role:id,name')
            ->when(trim($this->userQuery) !== '', fn ($query) => $query->search(trim($this->userQuery)))
            ->when(in_array($this->userStatusFilter, ['Active', 'Suspended'], true), fn ($query) => $query->where('is_active', $this->userStatusFilter === 'Active'))
            ->orderBy('last_name')
            ->orderBy('id')
            ->paginate(15);
    }

    #[Computed]
    public function userCounts(): array
    {
        $total = User::count();
        $active = User::active()->count();

        return [
            'total' => $total,
            'active' => $active,
            'suspended' => $total - $active,
            'administrators' => User::whereRelation('role', 'name', UserRole::Admin->value)->count(),
        ];
    }

    public function updatePassword(LoginLogger $logger): void
    {
        try {
            $validated = $this->validate([
                'passwordForm.current' => ['required', 'string', new PasswordInput, 'current_password'],
                'passwordForm.next' => ['required', 'string', new PasswordInput, Password::defaults()],
                'passwordForm.confirm' => ['required', 'same:passwordForm.next'],
            ]);

            DB::transaction(function () use ($validated, $logger): void {
                $user = $this->currentUser;
                $user->forceFill([
                    'password_hash' => $validated['passwordForm']['next'],
                    'remember_token' => null,
                ])->save();

                $logger->success(LoginEvent::PasswordChanged, $user);
            });
        } finally {
            $this->reset('passwordForm');
        }

        $this->profileModal = null;
        $this->triggerToast('Password updated successfully.');
    }

    #[Computed]
    public function summary(): array
    {
        return app(AdminDashboardReport::class)->summary();
    }

    #[Computed]
    public function monitoring(): array
    {
        return app(AdminDashboardReport::class)->monitoring();
    }

    #[Computed]
    public function distribution(): array
    {
        return app(AdminDashboardReport::class)->distribution($this->period);
    }

    #[Computed]
    public function barangayCounts(): Collection
    {
        return Barangay::query()->select(['id', 'name'])->withCount('seniorCitizens')
            ->orderByDesc('senior_citizens_count')->orderBy('name')->get();
    }

    #[Computed]
    public function registryCounts(): array
    {
        $today = now()->startOfDay();
        $query = SeniorCitizen::query();

        return [
            'Total registry' => $query->count(),
            'Age 60-69' => (clone $query)->where('birth_date', '>', $today->copy()->subYears(70)->toDateString())->where('birth_date', '<=', $today->copy()->subYears(60)->toDateString())->count(),
            'Age 70-79' => (clone $query)->where('birth_date', '>', $today->copy()->subYears(80)->toDateString())->where('birth_date', '<=', $today->copy()->subYears(70)->toDateString())->count(),
            'Age 80+' => (clone $query)->where('birth_date', '<=', $today->copy()->subYears(80)->toDateString())->count(),
        ];
    }

    #[Computed]
    public function filteredRecords(): LengthAwarePaginator
    {
        $term = trim($this->search);

        return SeniorCitizen::query()
            ->select(['id', 'barangay_id', 'registration_number', 'osca_id_number', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date', 'status', 'updated_at'])
            ->with('barangay:id,name')
            ->when($term !== '', fn ($query) => $query->where(fn ($query) => $query->searchable($term)
                ->orWhereHas('barangay', fn ($query) => $query->whereRaw('lower(name) like ?', ['%'.mb_strtolower($term).'%']))))
            ->when(in_array($this->recordFilter, ['PENDING', 'VERIFIED', 'ARCHIVED', 'DECEASED', 'TRANSFERRED'], true), fn ($query) => $query->where('status', $this->recordFilter))
            ->latest('updated_at')->orderByDesc('id')->paginate(15, pageName: 'recordsPage');
    }

    #[Computed]
    public function programs(): LengthAwarePaginator
    {
        $benefitReleased = BenefitPayment::query()->join('benefit_entitlements', 'benefit_payments.benefit_entitlement_id', '=', 'benefit_entitlements.id')
            ->whereColumn('benefit_entitlements.program_id', 'programs.id')->whereNull('benefit_payments.reversed_at')->selectRaw('coalesce(sum(benefit_payments.amount), 0)');
        $released = Payout::query()->join('payout_schedules', 'payouts.payout_schedule_id', '=', 'payout_schedules.id')
            ->whereColumn('payout_schedules.program_id', 'programs.id')->where('payouts.status', 'RELEASED')->selectRaw('coalesce(sum(payouts.amount), 0)');

        return Program::query()->select(['id', 'name', 'agency', 'budget', 'starts_on', 'ends_on', 'status', 'benefit_code'])
            ->selectSub($released, 'released_amount')->selectSub($benefitReleased, 'benefit_released_amount')->withCount(['beneficiaries', 'benefitEntitlements'])
            ->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereRaw('lower(name) like ?', ['%'.mb_strtolower(trim($this->search)).'%'])
                ->orWhereRaw('lower(agency) like ?', ['%'.mb_strtolower(trim($this->search)).'%'])))
            ->latest('id')->paginate(9, pageName: 'programsPage');
    }

    #[Computed]
    public function broadcasts(): LengthAwarePaginator
    {
        return SmsBlast::query()->select(['id', 'barangay_id', 'recipient_count', 'excluded_count', 'created_at'])
            ->with('barangay:id,name')->withCount([
                'messages as sent_count' => fn ($query) => $query->where('status', 'SENT'),
                'messages as queued_count' => fn ($query) => $query->where('status', 'QUEUED'),
                'messages as failed_count' => fn ($query) => $query->where('status', 'FAILED'),
            ])->latest('id')->paginate(10, pageName: 'broadcastsPage');
    }

    private function logsQuery(): Builder
    {
        $audit = DB::table('audit_logs')->selectRaw('id, created_at, user_id, ? as category, action as detail, ? as status', ['Audit Trail', 'Recorded']);
        $login = DB::table('login_logs')->selectRaw('id, created_at, case when event = ? then null else user_id end as user_id, ? as category, event as detail, case when success then ? else ? end as status', ['PASSWORD_RESET', 'Login Events', 'Success', 'Failed']);
        $sms = DB::table('sms_messages')->selectRaw('id, created_at, created_by as user_id, ? as category, status as detail, status', ['SMS Dispatch']);

        return $this->withOperators(DB::query()->fromSub($audit->unionAll($login)->unionAll($sms), 'events')->select('events.*'), 'events.user_id')
            ->when(in_array($this->logCategory, ['Audit Trail', 'Login Events', 'SMS Dispatch'], true), fn ($query) => $query->where('category', $this->logCategory))
            ->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereRaw('lower(detail) like ?', ['%'.mb_strtolower(trim($this->search)).'%'])
                ->orWhereRaw("lower(concat_ws(' ', replace(roles.name, '_', ' '), users.first_name, users.middle_name, users.last_name, users.name_suffix)) like ?", ['%'.mb_strtolower(trim($this->search)).'%'])))
            ->orderByDesc('events.created_at')->orderBy('category')->orderByDesc('events.id');
    }

    private function withOperators(Builder $query, string $userColumn): Builder
    {
        return $query->leftJoin('users', 'users.id', '=', $userColumn)
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->addSelect('roles.name as operator_role')
            ->selectRaw("trim(concat_ws(' ', users.first_name, users.middle_name, users.last_name, users.name_suffix)) as operator_name");
    }

    #[Computed]
    public function filteredLogs(): LengthAwarePaginator
    {
        return $this->logsQuery()->paginate(15, pageName: 'logsPage');
    }

    #[Computed]
    public function recentActivity(): Collection
    {
        return $this->withOperators(DB::table('audit_logs')->select(['audit_logs.id', 'audit_logs.action', 'audit_logs.created_at']), 'audit_logs.user_id')
            ->latest('audit_logs.created_at')->orderByDesc('audit_logs.id')->limit(5)->get();
    }

    #[Computed]
    public function configuration(): Collection
    {
        return SystemConfiguration::query()->whereIn('key', SystemConfiguration::DISPLAY_KEYS)->orderBy('key')->get(['id', 'key', 'value', 'updated_at']);
    }

    public function updatedSearch(): void
    {
        foreach (['recordsPage', 'logsPage', 'programsPage'] as $page) {
            $this->resetPage($page);
        }
    }

    public function updatedRecordFilter(): void
    {
        $this->resetPage('recordsPage');
    }

    public function updatedLogCategory(): void
    {
        $this->resetPage('logsPage');
    }

    public function render(): View
    {
        return view('administration.dashboard.panel');
    }
}
