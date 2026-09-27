<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\SendSmsRequest;
use App\Models\SmsMessage;
use App\Services\Sms\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SmsMessageController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SmsMessage::class);

        return view('sms.messages.index', ['messages' => SmsMessage::query()->with('seniorCitizen')->latest('id')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', SmsMessage::class);

        return view('sms.messages.create');
    }

    public function store(SendSmsRequest $request, SmsService $smsService): RedirectResponse
    {
        $data = $request->validated();
        $smsService->queue($data['recipient_number'], $data['message'], $request->user()->id, $data['senior_citizen_id'] ?? null);

        return redirect()->route('sms.messages.index')->with('status', 'SMS queued for delivery.');
    }
}
