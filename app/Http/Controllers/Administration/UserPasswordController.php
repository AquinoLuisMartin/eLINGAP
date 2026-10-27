<?php

namespace App\Http\Controllers\Administration;

use App\Enums\PasswordResetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UpdateUserPasswordRequest;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Services\Auth\PasswordRecovery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserPasswordController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $input = $request->validate(['status' => ['nullable', Rule::enum(PasswordResetStatus::class)]]);
        $requests = PasswordResetRequest::query()->select(['id', 'user_id', 'status', 'requested_at', 'reviewed_by',
            'reviewed_at', 'identity_verified_at', 'recovery_expires_at', 'completed_at', 'failed_attempts'])
            ->with(['user:id,username,first_name,middle_name,last_name,name_suffix', 'reviewer:id,username,first_name,middle_name,last_name,name_suffix'])
            ->when($input['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('requested_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('administration.users.recovery-index', ['requests' => $requests]);
    }

    public function show(PasswordResetRequest $resetRequest): View
    {
        Gate::authorize('viewAny', User::class);
        $resetRequest->load(['user:id,role_id,username,first_name,middle_name,last_name,name_suffix,is_active', 'user.role',
            'reviewer:id,username,first_name,middle_name,last_name,name_suffix']);

        return view('administration.users.recovery-review', ['reset' => $resetRequest]);
    }

    public function store(Request $request, User $user, PasswordRecovery $recovery): RedirectResponse
    {
        Gate::authorize('manageAccess', $user);
        $reset = $recovery->request($user->username, $request->user());
        abort_unless($reset, 422, 'Recovery is available only for active staff accounts.');

        return redirect()->route('administration.password-reset-requests.show', $reset);
    }

    public function update(UpdateUserPasswordRequest $request, PasswordResetRequest $resetRequest, PasswordRecovery $recovery): View|RedirectResponse
    {
        abort_if($request->expectsJson(), 406, 'Use the review form for private credential handover.');
        $code = $recovery->review($resetRequest, $request->user(), $request->validated('decision'),
            $request->boolean('identity_verified'), $request->validated('admin_password'));
        if (! $code) {
            return redirect()->route('administration.password-reset-requests.index')->with('status', 'Recovery request rejected.');
        }

        return view('administration.users.credential', ['username' => $resetRequest->user->username,
            'credential' => $code, 'credentialLabel' => 'Recovery code',
            'handoverMessage' => 'Identity verified and recovery approved. Hand this single-use code directly to the verified employee. It expires in 15 minutes.']);
    }
}
