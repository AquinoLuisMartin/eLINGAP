<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\StoreSmsTemplateRequest;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SmsTemplateController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SmsMessage::class);

        return view('sms.templates.index', ['templates' => SmsTemplate::query()->whereNull('superseded_by_id')->orderBy('name')->paginate(15), 'retiredTemplates' => SmsTemplate::query()->whereNotNull('superseded_by_id')->pluck('id')->map(strval(...))->all()]);
    }

    public function create(): View
    {
        Gate::authorize('create', SmsTemplate::class);

        return view('sms.templates.create');
    }

    public function store(StoreSmsTemplateRequest $request): RedirectResponse
    {
        SmsTemplate::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('sms.templates.index')->with('status', 'SMS template created.');
    }
}
