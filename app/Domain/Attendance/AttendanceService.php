<?php

namespace App\Domain\Attendance;

use App\Domain\Settings\ApplicationSettings;
use App\Enums\AttendanceStatus;
use App\Exceptions\AttendanceSequenceException;
use App\Models\AttendanceRecord;
use App\Models\EmployeeProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(
        private readonly ScheduleResolver $schedules,
        private readonly ApplicationSettings $settings,
    ) {}

    public function clockIn(User $employeeUser): AttendanceActionResult
    {
        $employee = $this->employeeFor($employeeUser);
        $now = CarbonImmutable::now('UTC');

        return DB::transaction(function () use ($employee, $employeeUser, $now): AttendanceActionResult {
            EmployeeProfile::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $existing = AttendanceRecord::query()->where('employee_profile_id', $employee->id)->latest('work_date')->lockForUpdate()->first();
            if ($existing && ($existing->clock_out_at === null || $existing->work_date->isSameDay($now->setTimezone($existing->timezone)))) {
                return new AttendanceActionResult($existing, true);
            }

            $timezone = $this->settings->timezone()->getName();
            $shift = $this->schedules->forEmployeeAt($employee, $now);
            $workDate = $shift?->workDate ?? $now->setTimezone($timezone)->startOfDay();
            $record = AttendanceRecord::create([
                'employee_profile_id' => $employee->id,
                'work_date' => $workDate->toDateString(),
                'timezone' => $timezone,
                'scheduled_start_at' => $shift?->startsAtUtc,
                'scheduled_end_at' => $shift?->endsAtUtc,
                'clock_in_at' => $now,
                'status' => AttendanceStatus::ClockedIn,
                'clock_in_outside_schedule' => ! $shift || ! $shift->contains($now),
                'source' => 'employee',
                'created_by' => $employeeUser->id,
                'updated_by' => $employeeUser->id,
            ]);

            return new AttendanceActionResult($record);
        }, 3);
    }

    public function clockOut(User $employeeUser): AttendanceActionResult
    {
        $employee = $this->employeeFor($employeeUser);
        $now = CarbonImmutable::now('UTC');

        return DB::transaction(function () use ($employee, $employeeUser, $now): AttendanceActionResult {
            EmployeeProfile::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $active = AttendanceRecord::query()->where('employee_profile_id', $employee->id)->whereNull('clock_out_at')->latest('clock_in_at')->lockForUpdate()->first();
            if (! $active) {
                $completed = AttendanceRecord::query()->where('employee_profile_id', $employee->id)->whereNotNull('clock_out_at')->latest('clock_out_at')->first();
                if ($completed && $completed->clock_out_at->greaterThanOrEqualTo($now->subMinutes(5))) {
                    return new AttendanceActionResult($completed, true);
                }
                throw new AttendanceSequenceException('Clock in before attempting to clock out.');
            }

            $active->forceFill([
                'clock_out_at' => $now,
                'status' => AttendanceStatus::Completed,
                'clock_out_outside_schedule' => ! $active->scheduled_end_at || $now->greaterThan($active->scheduled_end_at),
                'updated_by' => $employeeUser->id,
            ])->save();

            return new AttendanceActionResult($active->fresh());
        }, 3);
    }

    private function employeeFor(User $user): EmployeeProfile
    {
        $employee = $user->employeeProfile;
        if (! $employee || ! $user->is_active) {
            throw new AuthorizationException('An active employee profile is required.');
        }

        return $employee;
    }
}
