<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateFirstLoginPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class FirstLoginPasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.first-login-password');
    }

    public function update(UpdateFirstLoginPasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill(['password' => Hash::make($request->validated('password')), 'must_change_password' => false, 'password_changed_at' => now(), 'remember_token' => null])->save();
        $request->session()->regenerate();

        $destination = $request->user()->isAdministrator() ? '/admin' : route('dashboard');

        return redirect($destination)->with('status', 'Your password has been updated.');
    }
}
