<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PayoutReportController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        Gate::authorize('viewAny', Application::class);

        return redirect()->route('reports.workspace', 'payouts');
    }
}
