<?php

namespace App\Http\Controllers\Employee;

use App\Domain\Attendance\ScheduleResolver;
use App\Domain\Settings\ApplicationSettings;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ApplicationSettings $settings, ScheduleResolver $schedules): View|RedirectResponse
    {
        if ($request->user()->isAdministrator()) {
            return redirect('/admin');
        }

        $employee = $request->user()->employeeProfile()->with(['department', 'position'])->firstOrFail();
        $timezone = $settings->timezone()->getName();
        $now = CarbonImmutable::now($timezone);
        $todayRecord = AttendanceRecord::query()->where('employee_profile_id', $employee->id)->whereDate('work_date', $now->toDateString())->first();
        $activeRecord = AttendanceRecord::query()->where('employee_profile_id', $employee->id)->whereNull('clock_out_at')->latest('clock_in_at')->first();
        $weekStart = $now->startOfWeek();
        $weekRecords = AttendanceRecord::query()->where('employee_profile_id', $employee->id)->whereBetween('work_date', [$weekStart->toDateString(), $weekStart->addDays(6)->toDateString()])->orderBy('work_date')->get()->keyBy(fn ($record) => $record->work_date->toDateString());

        return view('employee.dashboard', [
            'employee' => $employee,
            'now' => $now,
            'timezone' => $timezone,
            'todayRecord' => $todayRecord,
            'activeRecord' => $activeRecord,
            'shift' => $schedules->forEmployeeAt($employee, $now->utc()),
            'weekStart' => $weekStart,
            'weekRecords' => $weekRecords,
            'recentRecords' => AttendanceRecord::query()->where('employee_profile_id', $employee->id)->latest('work_date')->limit(5)->get(),
            'holidays' => Holiday::query()->where('is_active', true)->whereDate('date', '>=', $now->toDateString())->orderBy('date')->limit(3)->get(),
        ]);
    }
}
