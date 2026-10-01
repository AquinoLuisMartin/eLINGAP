<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Reports\DemographicsReportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DemographicsReportController extends Controller
{
    public function __invoke(DemographicsReportService $service): View
    {
        Gate::authorize('viewAny', Application::class);

        return view('reports.demographics', ['summary' => $service->summary()]);
    }
}
