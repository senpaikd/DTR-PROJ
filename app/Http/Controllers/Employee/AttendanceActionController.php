<?php

namespace App\Http\Controllers\Employee;

use App\Domain\Attendance\AttendanceService;
use App\Exceptions\AttendanceSequenceException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceActionController extends Controller
{
    public function clockIn(Request $request, AttendanceService $attendance): RedirectResponse
    {
        $result = $attendance->clockIn($request->user());

        return redirect()->route('dashboard')->with('status', $result->idempotent ? 'You are already clocked in.' : 'Clock-in recorded.');
    }

    public function clockOut(Request $request, AttendanceService $attendance): RedirectResponse
    {
        try {
            $result = $attendance->clockOut($request->user());
        } catch (AttendanceSequenceException $exception) {
            return redirect()->route('dashboard')->withErrors(['attendance' => $exception->getMessage()]);
        }

        return redirect()->route('dashboard')->with('status', $result->idempotent ? 'Clock-out was already recorded.' : 'Clock-out recorded.');
    }
}
