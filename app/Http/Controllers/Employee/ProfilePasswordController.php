<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfilePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilePasswordController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()?->employeeProfile !== null, 403);

        return view('employee.profile.password');
    }

    public function update(UpdateProfilePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'password_changed_at' => now('UTC'),
            'remember_token' => null,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('profile.edit')->with('status', 'Password changed successfully.');
    }
}
