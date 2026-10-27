<?php

namespace App\View\Composers;

use App\Models\Program;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PublicContent
{
    public function compose(View $view): void
    {
        $programs = collect();

        try {
            $programs = Program::query()->whereIn('status', ['ACTIVE', 'UPCOMING'])
                ->where(fn ($query) => $query->whereNull('benefit_code')->orWhere('benefit_code', 'not like', 'synthetic-%'))
                ->orderByRaw("CASE WHEN status = 'ACTIVE' THEN 0 ELSE 1 END")->orderBy('name')
                ->get(['id', 'name']);
        } catch (QueryException $exception) {
            $programs = collect();
            try {
                Log::warning('Public content is unavailable.', ['failure_type' => $exception::class]);
            } catch (Throwable) {
                error_log('Public content and application logging are unavailable.');
            }
        }

        $view->with([
            'publicPrograms' => $programs,
        ]);
    }
}
