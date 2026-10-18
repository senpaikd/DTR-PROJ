<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateEmployeeProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(UpdateEmployeeProfileRequest $request): View
    {
        return view('employee.profile.edit', ['profile' => $request->user()->employeeProfile->load(['department', 'position'])]);
    }

    public function update(UpdateEmployeeProfileRequest $request): RedirectResponse
    {
        $request->user()->employeeProfile->update($request->validated());

        return redirect()->route('profile.edit')->with('status', 'Profile updated.');
    }
}
