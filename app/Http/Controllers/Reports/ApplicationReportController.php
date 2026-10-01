<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Reports\ApplicationReportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApplicationReportController extends Controller
{
    public function __invoke(ApplicationReportService $service): View
    {
        Gate::authorize('viewAny', Application::class);

        return view('reports.applications', ['summary' => $service->summary()]);
    }
}
