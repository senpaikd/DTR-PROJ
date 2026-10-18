<?php

namespace App\Http\Controllers\Employee;

use App\Domain\Settings\ApplicationSettings;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceHistoryController extends Controller
{
    public function index(Request $request, ApplicationSettings $settings): View
    {
        return $this->render($request, $settings, false);
    }

    public function print(Request $request, ApplicationSettings $settings): View
    {
        return $this->render($request, $settings, true);
    }

    private function render(Request $request, ApplicationSettings $settings, bool $print): View
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = CarbonImmutable::createFromFormat('Y-m', $validated['month'] ?? now($settings->timezone())->format('Y-m'), $settings->timezone())->startOfMonth();
        $employee = $request->user()->employeeProfile()->with(['department', 'position'])->firstOrFail();
        $records = AttendanceRecord::query()->where('employee_profile_id', $employee->id)->whereBetween('work_date', [$month->toDateString(), $month->endOfMonth()->toDateString()])->orderBy('work_date')->get();

        return view($print ? 'employee.attendance.print' : 'employee.attendance.index', compact('employee', 'records', 'month'));
    }
}
