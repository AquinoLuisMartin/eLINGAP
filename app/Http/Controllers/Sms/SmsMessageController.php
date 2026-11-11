<?php

namespace App\Http\Controllers\Sms;

use App\Enums\SmsStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\SendSmsRequest;
use App\Models\SmsMessage;
use App\Services\Sms\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SmsMessageController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SmsMessage::class);
        $filters = $request->validate(['status' => ['nullable', Rule::enum(SmsStatus::class)]]);

        return view('sms.messages.index', ['messages' => SmsMessage::query()
            ->select(['id', 'senior_citizen_id', 'recipient_number', 'message', 'queued_at', 'sent_at', 'status', 'failure_reason'])
            ->with('seniorCitizen:id,osca_id_number,first_name,middle_name,last_name,name_suffix')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')->paginate(15)->withQueryString()]);
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
