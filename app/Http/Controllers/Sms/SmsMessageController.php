<?php

namespace App\Http\Controllers\Sms;

use App\Enums\SmsStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\SendSmsRequest;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use App\Services\Sms\SmsService;
use Illuminate\Http\JsonResponse;
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

        $programs = Program::query()->orderBy('name')->get(['id', 'name']);
        $selected = null;
        $programId = filter_var(old('program_id'), FILTER_VALIDATE_INT);
        $seniorId = filter_var(old('senior_citizen_id'), FILTER_VALIDATE_INT);
        if ($programId && $seniorId) {
            $selected = SeniorCitizen::query()->onProgramMasterlist($programId)
                ->find($seniorId, ['id', 'osca_id_number', 'first_name', 'middle_name', 'last_name', 'name_suffix']);
        }

        return view('sms.messages.create', ['programs' => $programs, 'programId' => $programId ?: '', 'selected' => $selected ? $this->recipientOption($selected) : null]);
    }

    public function recipients(Request $request): JsonResponse
    {
        Gate::authorize('create', SmsMessage::class);
        Gate::authorize('viewAny', Program::class);
        $filters = $request->validate(['program_id' => ['required', 'integer', 'exists:programs,id'], 'search' => ['nullable', 'string', 'max:100']]);
        $recipients = SeniorCitizen::query()->onProgramMasterlist((int) $filters['program_id'])
            ->select(['id', 'osca_id_number', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'contact_number', 'representative_contact'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->searchable($search))
            ->orderBy('last_name')->orderBy('first_name')->orderBy('id')->lazy(50)
            ->filter(fn (SeniorCitizen $senior) => $senior->smsContact() !== null)
            ->take(16)->map($this->recipientOption(...))->values()->collect();

        return response()->json(['recipients' => $recipients->take(15)->all(), 'has_more' => $recipients->count() > 15])
            ->header('Cache-Control', 'no-store');
    }

    private function recipientOption(SeniorCitizen $senior): array
    {
        return ['id' => (string) $senior->id, 'name' => $senior->surname_first_name, 'osca_id' => $senior->osca_id_number];
    }

    public function store(SendSmsRequest $request, SmsService $smsService): RedirectResponse
    {
        $data = $request->validated();
        $message = $smsService->sendIndividual($request->recipientNumber(), $data['message'], $request->user()->id, $data['senior_citizen_id'] ?? null);
        $redirect = redirect()->route('sms.messages.index');

        return $message->status === SmsStatus::Sent
            ? $redirect->with('status', 'SMS submitted to the provider.')
            : $redirect->withErrors(['sms' => $message->failure_reason ?? 'SMS delivery could not be confirmed.']);
    }
}
