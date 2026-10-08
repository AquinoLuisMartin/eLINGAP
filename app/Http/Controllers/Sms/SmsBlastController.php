<?php

namespace App\Http\Controllers\Sms;

use App\Enums\SmsStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\CreateSmsBlastRequest;
use App\Jobs\Sms\SendSmsJob;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\SmsBlast;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Services\Sms\SmsService;
use App\Services\Sms\SmsText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SmsBlastController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SmsMessage::class);

        return view('sms.blasts.index', ['blasts' => SmsBlast::query()->with(['creator', 'barangay'])
            ->withCount(['messages as sent_count' => fn ($query) => $query->where('status', SmsStatus::Sent->value), 'messages as failed_count' => fn ($query) => $query->where('status', SmsStatus::Failed->value)])
            ->latest('id')->paginate(15)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', SmsMessage::class);

        $filters = $request->validate(['barangay_id' => ['nullable', 'integer', 'exists:barangays,id']]);
        $eligible = 0;
        $total = SeniorCitizen::query()->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->where('barangay_id', $id))->count();
        $this->recipients($filters['barangay_id'] ?? null)->chunkById(200, function ($seniors) use (&$eligible) {
            foreach ($seniors as $senior) {
                if (SmsText::mobile($senior->contact_number)) {
                    $eligible++;
                }
            }
        });

        return view('sms.blasts.create', ['barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get(), 'templates' => SmsTemplate::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'body']), 'eligible' => $eligible, 'excluded' => $total - $eligible]);
    }

    public function store(CreateSmsBlastRequest $request, SmsService $smsService): RedirectResponse
    {
        $barangayId = $request->integer('barangay_id') ?: null;
        $total = SeniorCitizen::query()->when($barangayId, fn ($query, $id) => $query->where('barangay_id', $id))->count();
        $blast = DB::transaction(function () use ($request, $smsService, $barangayId, $total): SmsBlast {
            $blast = SmsBlast::create(['created_by' => $request->user()->id, 'barangay_id' => $barangayId, 'message' => $request->string('message')->toString()]);
            $sent = 0;
            $this->recipients($barangayId)->chunkById(200, function ($seniorCitizens) use ($request, $smsService, $blast, &$sent): void {
                foreach ($seniorCitizens as $seniorCitizen) {
                    if ($number = SmsText::mobile($seniorCitizen->contact_number)) {
                        $smsService->queue($number, $request->string('message')->toString(), $request->user()->id, $seniorCitizen->id, $blast->id);
                        $sent++;
                    }
                }
            });
            if ($sent === 0) {
                throw ValidationException::withMessages(['barangay_id' => 'No eligible recipients in this audience.']);
            }
            $blast->update(['recipient_count' => $sent, 'excluded_count' => $total - $sent]);
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'sms.broadcast', 'auditable_type' => SmsBlast::class, 'auditable_id' => $blast->id, 'new_values' => ['recipient_count' => $sent, 'barangay_id' => $barangayId, 'segments' => SmsText::details($blast->message)['segments']], 'ip_address' => $request->ip()]);

            return $blast;
        });

        return redirect()->route('sms.blasts.show', $blast)->with('status', "$blast->recipient_count SMS messages queued.");
    }

    public function show(SmsBlast $blast): View
    {
        Gate::authorize('viewAny', SmsMessage::class);

        return view('sms.blasts.show', ['blast' => $blast->load(['creator', 'barangay']), 'messages' => $blast->messages()->with('seniorCitizen')->latest('id')->paginate(20)]);
    }

    public function retry(Request $request, SmsMessage $message): RedirectResponse
    {
        Gate::authorize('create', SmsMessage::class);
        DB::transaction(function () use ($request, $message): void {
            $locked = SmsMessage::query()->with('seniorCitizen')->lockForUpdate()->findOrFail($message->id);
            abort_unless($locked->status === SmsStatus::Failed, 409, 'Only failed messages can be retried.');
            if ($locked->senior_citizen_id) {
                abort_unless($locked->seniorCitizen?->status?->value === 'VERIFIED', 422, 'Recipient is no longer eligible.');
                $number = SmsText::mobile($locked->seniorCitizen->contact_number);
                abort_unless($number, 422, 'Recipient has no valid mobile number.');
                $locked->recipient_number = $number;
            }
            $number = SmsText::mobile($locked->recipient_number);
            abort_unless($number, 422, 'Recipient has no valid mobile number.');
            $locked->recipient_number = $number;
            $locked->status = SmsStatus::Queued;
            $locked->failure_reason = null;
            $locked->queued_at = now();
            $locked->save();
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'sms.retried', 'auditable_type' => SmsMessage::class, 'auditable_id' => $locked->id, 'ip_address' => $request->ip()]);
            SendSmsJob::dispatch($locked->id)->afterCommit();
        });

        return back()->with('status', 'Failed SMS queued for retry.');
    }

    private function recipients(?int $barangayId)
    {
        return SeniorCitizen::query()->select(['id', 'contact_number'])->where('status', 'VERIFIED')
            ->when($barangayId, fn ($query) => $query->where('barangay_id', $barangayId));
    }
}
