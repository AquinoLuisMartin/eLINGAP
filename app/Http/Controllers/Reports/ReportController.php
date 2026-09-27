<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Reports\ApplicationReportService;
use App\Services\Reports\BeneficiaryReportService;
use App\Services\Reports\DemographicsReportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(
        ApplicationReportService $applications,
        BeneficiaryReportService $beneficiaries,
        DemographicsReportService $demographics,
    ): View {
        Gate::authorize('viewAny', Application::class);

        return view('reports.index', [
            'applicationSummary' => $applications->summary(),
            'beneficiarySummary' => $beneficiaries->summary(),
            'demographics' => $demographics->summary(),
        ]);
    }
}
