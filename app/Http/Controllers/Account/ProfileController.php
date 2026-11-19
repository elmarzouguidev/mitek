<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordRequest;
use App\Http\Requests\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile', ['profile' => $request->user()->profile()->first()]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update($request->safe()->only(['name', 'email']));
        $user->profile()->updateOrCreate([], $request->safe()->only(['phone', 'company_name', 'ice', 'if_number', 'rc_number']));

        return back()->with('success', __('account.profile.saved'));
    }

    public function updatePassword(PasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('success', __('account.profile.password_saved'));
    }
}
