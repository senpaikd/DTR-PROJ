<?php

namespace App\Domain\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\CorrectionRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\EmployeeProfile;
use Carbon\CarbonImmutable;

class AttendanceStatusResolver
{
    public function forDate(EmployeeProfile $employee, CarbonImmutable $localDate): AttendanceStatus
    {
        $record = AttendanceRecord::query()->where('employee_profile_id', $employee->id)->whereDate('work_date', $localDate->toDateString())->first();
        if ($record?->correctionRequests()->where('status', CorrectionRequestStatus::Pending)->exists()) {
            return AttendanceStatus::CorrectionPending;
        }
        if ($record) {
            return $record->status;
        }

        return $localDate->startOfDay()->isBefore(CarbonImmutable::now(config('app.timezone'))->startOfDay())
            ? AttendanceStatus::NoAttendanceRecorded
            : AttendanceStatus::NotStarted;
    }
}
