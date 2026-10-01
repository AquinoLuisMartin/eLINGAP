<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\CreateSmsBlastRequest;
use App\Models\SeniorCitizen;
use App\Services\Sms\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SmsBlastController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', \App\Models\SmsMessage::class);

        return view('sms.blasts.create');
    }

    public function store(CreateSmsBlastRequest $request, SmsService $smsService): RedirectResponse
    {
        $query = SeniorCitizen::query()->where('status', $request->input('status', 'VERIFIED'))->whereNotNull('contact_number');
        $query->select(['id', 'contact_number'])->chunkById(200, function ($seniorCitizens) use ($request, $smsService): void {
            foreach ($seniorCitizens as $seniorCitizen) {
                $smsService->queue($seniorCitizen->contact_number, $request->string('message')->toString(), $request->user()->id, $seniorCitizen->id);
            }
        });

        return redirect()->route('sms.blasts.index')->with('status', 'SMS blast queued.');
    }
}
