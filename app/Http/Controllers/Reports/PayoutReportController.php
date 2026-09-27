<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Reports\PayoutReportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PayoutReportController extends Controller
{
    public function __invoke(PayoutReportService $service): View
    {
        Gate::authorize('viewAny', Application::class);

        return view('reports.payouts', ['summary' => $service->summary()]);
    }
}
