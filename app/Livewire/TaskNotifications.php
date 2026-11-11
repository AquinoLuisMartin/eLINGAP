<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\PasswordResetRequest;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TaskNotifications extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->user()?->is_active && ! auth()->user()->must_change_password, 403);
        Gate::authorize('viewAny', SeniorCitizen::class);
    }

    #[Computed]
    public function tasks(): array
    {
        $tasks = [];

        if (auth()->user()->isAdmin()) {
            $tasks[] = $this->queue(
                PasswordResetRequest::query()->where('status', 'PENDING')
                    ->whereHas('user', fn (Builder $query) => $query->where('is_active', true)
                        ->whereRelation('role', 'name', UserRole::OscaStaff->value)),
                'recovery', 'Password reset requests', 'Verify employee identity and review account recovery.',
                route('administration.password-reset-requests.index', ['status' => 'PENDING']),
                'administration.password-reset-requests.show',
            );
        }

        if (auth()->user()->isOscaStaff()) {
            $tasks[] = $this->queue(
                SeniorCitizen::query()->where('status', 'PENDING'),
                'verification', 'Registrations awaiting review', 'Review requirements and verify pending senior records.',
                route('senior-citizens.index', ['status' => 'PENDING']), 'senior-citizens.show',
            );
            $tasks[] = $this->queue(
                SeniorCitizen::query()->where('status', 'VERIFIED')->whereNull('id_generated_at'),
                'identification', 'IDs awaiting preparation', 'Prepare identification for approved registrations.',
                route('registrations.index', ['stage' => 'VERIFIED']), 'senior-id.show',
            );
        }

        $tasks[] = $this->queue(
            Application::query()->where('status', 'PENDING'),
            'applications', 'Benefit applications awaiting review', 'Review the application and record a decision.',
            route('applications.index', ['status' => 'PENDING']), 'applications.show',
        );
        $tasks[] = $this->queue(
            SmsMessage::query()->where('status', 'FAILED'),
            'sms', 'Failed SMS messages', 'Review provider results and failed message deliveries.',
            route('sms.messages.index', ['status' => 'FAILED']),
        );

        return array_values(array_filter($tasks));
    }

    private function queue(Builder $query, string $key, string $title, string $description, string $url, ?string $nextRoute = null): ?array
    {
        $summary = $query->toBase()->selectRaw('count(*) as total, min(id) as next_id')->first();
        if (! $summary->total) {
            return null;
        }

        return [
            'key' => $key, 'title' => $title, 'description' => $description, 'count' => (int) $summary->total,
            'url' => $url, 'next_url' => $nextRoute ? route($nextRoute, $summary->next_id) : null,
        ];
    }

    public function render(): View
    {
        return view('livewire.task-notifications', [
            'tasks' => $this->tasks,
            'taskCount' => array_sum(array_column($this->tasks, 'count')),
        ]);
    }
}
