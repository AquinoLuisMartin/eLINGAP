<?php

namespace App\Http\Controllers\Applications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Applications\StoreApplicationRequest;
use App\Http\Requests\Applications\UpdateApplicationStatusRequest;
use App\Models\Application;
use App\Models\Program;
use App\Models\SeniorCitizen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Application::class);

        return view('applications.index', ['applications' => Application::query()->with(['seniorCitizen', 'program'])->latest('id')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', Application::class);

        return view('applications.create', [
            'seniorCitizens' => SeniorCitizen::query()->where('status', 'VERIFIED')->orderBy('last_name')->get(),
            'programs' => Program::query()->whereIn('status', ['ACTIVE', 'UPCOMING'])->orderBy('name')->get(),
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $application = DB::transaction(function () use ($request) {
            $application = Application::create([
                ...$request->validated(),
                'application_number' => 'APP-'.now()->format('YmdHis').'-'.random_int(100, 999),
                'status' => 'PENDING',
            ]);
            $application->statusHistories()->create(['to_status' => 'PENDING', 'changed_by' => $request->user()->id]);

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
        $fromStatus = $application->status->value;

        DB::transaction(function () use ($request, $application, $fromStatus) {
            $application->update(['status' => $request->string('status')->toString(), 'remarks' => $request->input('remarks'), 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            $application->statusHistories()->create(['from_status' => $fromStatus, 'to_status' => $application->status->value, 'remarks' => $request->input('remarks'), 'changed_by' => $request->user()->id]);
        });

        return back()->with('status', 'Application status updated.');
    }
}
