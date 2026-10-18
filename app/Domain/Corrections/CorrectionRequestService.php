<?php

namespace App\Domain\Corrections;

use App\Domain\Settings\ApplicationSettings;
use App\Enums\CorrectionRequestStatus;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CorrectionRequestService
{
    public function __construct(private readonly ApplicationSettings $settings) {}

    public function submit(User $employeeUser, array $data): AttendanceCorrectionRequest
    {
        $employee = $employeeUser->employeeProfile;
        if (! $employee) {
            throw ValidationException::withMessages(['attendance' => 'An employee profile is required.']);
        }

        $validated = Validator::make($data, [
            'attendance_record_id' => ['nullable', 'integer'],
            'work_date' => ['required', 'date_format:Y-m-d'],
            'proposed_clock_in_at' => ['nullable', 'date'],
            'proposed_clock_out_at' => ['nullable', 'date', 'after:proposed_clock_in_at'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ])->validate();

        $workDate = CarbonImmutable::parse($validated['work_date'], $this->settings->timezone());
        $today = CarbonImmutable::now($this->settings->timezone())->startOfDay();
        if ($workDate->isAfter($today) || $workDate->isBefore($today->subDays($this->settings->correctionWindowDays()))) {
            throw ValidationException::withMessages(['work_date' => 'The work date is outside the correction request window.']);
        }

        $record = null;
        if (! empty($validated['attendance_record_id'])) {
            $record = AttendanceRecord::query()->whereKey($validated['attendance_record_id'])->where('employee_profile_id', $employee->id)->first();
            if (! $record) {
                throw ValidationException::withMessages(['attendance_record_id' => 'The selected attendance record is invalid.']);
            }
        }

        $duplicate = AttendanceCorrectionRequest::query()
            ->where('employee_profile_id', $employee->id)
            ->whereDate('work_date', $validated['work_date'])
            ->where('status', CorrectionRequestStatus::Pending)
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['work_date' => 'A correction request for this date is already pending.']);
        }

        return AttendanceCorrectionRequest::create([
            ...$validated,
            'employee_profile_id' => $employee->id,
            'attendance_record_id' => $record?->id,
            'status' => CorrectionRequestStatus::Pending,
        ]);
    }
}
