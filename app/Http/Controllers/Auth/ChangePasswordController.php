<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Services\Auth\Credentials;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(ChangePasswordRequest $request, Credentials $credentials): RedirectResponse
    {
        $credentials->change($request->user(), $request->validated('current_password'), $request->validated('password'));

        return redirect()->route(auth()->user()->homeRouteName())->with('status', 'Password changed.');
    }
}
