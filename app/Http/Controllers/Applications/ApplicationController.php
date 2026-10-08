<?php

namespace App\Http\Controllers\Applications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Applications\StoreApplicationRequest;
use App\Http\Requests\Applications\UpdateApplicationStatusRequest;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Program;
use App\Models\SeniorCitizen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Application::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:PENDING,APPROVED,REJECTED,CANCELLED'],
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
        ]);
        $query = Application::query()->with(['seniorCitizen.barangay', 'program'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('application_number', 'like', '%'.$search.'%')
                    ->orWhereHas('seniorCitizen', fn ($senior) => $senior->searchable($search));
            }))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->whereHas('seniorCitizen', fn ($senior) => $senior->where('barangay_id', $id)));

        return view('applications.index', [
            'applications' => $query->latest('id')->paginate(15)->withQueryString(),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Application::class);
        $search = $request->validate(['senior_search' => ['nullable', 'string', 'max:100']])['senior_search'] ?? null;

        return view('applications.create', [
            'seniorCitizens' => $search ? SeniorCitizen::query()
                ->select(['id', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'registration_number', 'osca_id_number'])
                ->where('status', 'VERIFIED')->searchable($search)
                ->orderBy('last_name')->limit(25)->get() : collect(),
            'programs' => Program::query()
                ->select(['id', 'name'])
                ->whereIn('status', ['ACTIVE', 'UPCOMING'])
                ->orderBy('name')
                ->limit(100)
                ->get(),
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $application = DB::transaction(function () use ($request) {
            $application = Application::create([
                ...$request->validated(),
                'application_number' => 'APP-'.Str::ulid(),
                'status' => 'PENDING',
            ]);
            $application->statusHistories()->create(['to_status' => 'PENDING', 'changed_by' => $request->user()->id]);
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'application.created', 'auditable_type' => Application::class, 'auditable_id' => $application->id, 'new_values' => ['status' => 'PENDING'], 'ip_address' => $request->ip()]);

            return $application;
        });

        return redirect()->route('applications.show', $application)->with('status', 'Application submitted.');
    }

    public function show(Application $application): View
    {
        Gate::authorize('view', $application);

        return view('applications.show', ['application' => $application->load(['seniorCitizen', 'program', 'statusHistories.changedBy'])]);
    }

    public function updateStatus(UpdateApplicationStatusRequest $request, Application $application): RedirectResponse
    {
        DB::transaction(function () use ($request, $application) {
            $application = Application::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $fromStatus = $application->status->value;
            abort_unless($fromStatus === 'PENDING', 409, 'This application has already been reviewed.');
            $toStatus = $request->string('status')->toString();

            $application->update([
                'status' => $toStatus,
                'remarks' => $request->input('remarks'),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
            $application->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => $application->status->value,
                'remarks' => $request->input('remarks'),
                'changed_by' => $request->user()->id,
            ]);
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'application.status_changed', 'auditable_type' => Application::class, 'auditable_id' => $application->id, 'old_values' => ['status' => $fromStatus], 'new_values' => ['status' => $toStatus], 'ip_address' => $request->ip()]);
        });

        return back()->with('status', 'Application status updated.');
    }
}
