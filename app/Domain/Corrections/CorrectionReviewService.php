<?php

namespace App\Domain\Corrections;

use App\Domain\Auditing\AuditRecorder;
use App\Domain\Settings\ApplicationSettings;
use App\Enums\AttendanceStatus;
use App\Enums\CorrectionRequestStatus;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRevision;
use App\Models\EmployeeProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectionReviewService
{
    public function __construct(
        private readonly AuditRecorder $audit,
        private readonly ApplicationSettings $settings,
    ) {}

    public function approve(User $administrator, AttendanceCorrectionRequest $request, CorrectionReviewData $data): AttendanceRecord
    {
        $this->ensureAdministrator($administrator);
        $adjusted = $this->normal($data->clockInAt) !== $this->normal($request->proposed_clock_in_at)
            || $this->normal($data->clockOutAt) !== $this->normal($request->proposed_clock_out_at);
        if ($adjusted && blank($data->explanation)) {
            throw ValidationException::withMessages(['explanation' => 'Explain any change from the employee proposal.']);
        }
        if ($data->clockInAt && $data->clockOutAt && $data->clockOutAt <= $data->clockInAt) {
            throw ValidationException::withMessages(['clock_out_at' => 'Clock-out must be after clock-in.']);
        }

        return DB::transaction(function () use ($administrator, $request, $data): AttendanceRecord {
            $request = AttendanceCorrectionRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== CorrectionRequestStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'This request has already been reviewed.']);
            }
            $record = $request->attendance_record_id
                ? AttendanceRecord::query()->lockForUpdate()->findOrFail($request->attendance_record_id)
                : new AttendanceRecord(['employee_profile_id' => $request->employee_profile_id, 'work_date' => $request->work_date, 'timezone' => $this->settings->timezone()->getName(), 'source' => 'approved_correction', 'created_by' => $administrator->id]);
            $before = $record->exists ? $record->only(['clock_in_at', 'clock_out_at', 'status', 'source']) : [];
            $record->forceFill(['clock_in_at' => $data->clockInAt, 'clock_out_at' => $data->clockOutAt, 'status' => AttendanceStatus::Corrected, 'updated_by' => $administrator->id])->save();
            $after = $record->fresh()->only(['clock_in_at', 'clock_out_at', 'status', 'source']);

            $request->forceFill(['attendance_record_id' => $record->id, 'status' => CorrectionRequestStatus::Approved, 'reviewed_by' => $administrator->id, 'reviewed_at' => now('UTC'), 'review_notes' => $data->explanation, 'final_clock_in_at' => $data->clockInAt, 'final_clock_out_at' => $data->clockOutAt, 'adjustment_explanation' => $data->explanation])->save();
            AttendanceRevision::create(['attendance_record_id' => $record->id, 'attendance_correction_request_id' => $request->id, 'before_values' => $before, 'after_values' => $after, 'reason' => $data->explanation ?: $request->reason, 'changed_by' => $administrator->id, 'created_at' => now('UTC')]);
            $this->audit->record($administrator, 'attendance.correction.approved', $record, $before, $after, ['correction_request_id' => $request->id, 'explanation' => $data->explanation]);

            return $record->fresh();
        });
    }

    public function reject(User $administrator, AttendanceCorrectionRequest $request, string $reason): AttendanceCorrectionRequest
    {
        $this->ensureAdministrator($administrator);
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Provide a clear rejection reason.']);
        }

        return DB::transaction(function () use ($administrator, $request, $reason): AttendanceCorrectionRequest {
            $request = AttendanceCorrectionRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== CorrectionRequestStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'This request has already been reviewed.']);
            }
            $before = $request->only(['status']);
            $request->forceFill(['status' => CorrectionRequestStatus::Rejected, 'reviewed_by' => $administrator->id, 'reviewed_at' => now('UTC'), 'review_notes' => trim($reason)])->save();
            $this->audit->record($administrator, 'attendance.correction.rejected', $request, $before, $request->only(['status', 'review_notes']));

            return $request->fresh();
        });
    }

    public function correctDirectly(User $administrator, AttendanceRecord $record, array $values, string $reason): AttendanceRecord
    {
        $this->ensureAdministrator($administrator);
        $reason = trim($reason);
        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['reason' => 'Provide a clear reason for the correction.']);
        }

        $clockInAt = $values['clock_in_at'] ?? null;
        $clockOutAt = $values['clock_out_at'] ?? null;
        if ($clockInAt && $clockOutAt && $clockOutAt <= $clockInAt) {
            throw ValidationException::withMessages(['clock_out_at' => 'Clock-out must be after clock-in.']);
        }

        return DB::transaction(function () use ($administrator, $record, $clockInAt, $clockOutAt, $reason): AttendanceRecord {
            $record = AttendanceRecord::query()->lockForUpdate()->findOrFail($record->id);
            $before = $record->only(['clock_in_at', 'clock_out_at', 'status', 'source']);
            $record->forceFill([
                'clock_in_at' => $clockInAt,
                'clock_out_at' => $clockOutAt,
                'status' => AttendanceStatus::Corrected,
                'source' => 'admin_correction',
                'updated_by' => $administrator->id,
            ])->save();
            $after = $record->fresh()->only(['clock_in_at', 'clock_out_at', 'status', 'source']);

            AttendanceRevision::create([
                'attendance_record_id' => $record->id,
                'before_values' => $before,
                'after_values' => $after,
                'reason' => $reason,
                'changed_by' => $administrator->id,
                'created_at' => now('UTC'),
            ]);
            $this->audit->record($administrator, 'attendance.corrected.directly', $record, $before, $after, ['reason' => $reason]);

            return $record->fresh();
        });
    }

    public function createDirectly(User $administrator, EmployeeProfile $employee, string $workDate, array $values, string $reason): AttendanceRecord
    {
        $this->ensureAdministrator($administrator);
        $reason = trim($reason);
        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['reason' => 'Provide a clear reason for creating this record.']);
        }

        try {
            $workDate = CarbonImmutable::createFromFormat('!Y-m-d', $workDate)->format('Y-m-d');
        } catch (\Throwable) {
            throw ValidationException::withMessages(['work_date' => 'Provide a valid work date.']);
        }

        $clockInAt = $values['clock_in_at'] ?? null;
        $clockOutAt = $values['clock_out_at'] ?? null;
        if (! $clockInAt && ! $clockOutAt) {
            throw ValidationException::withMessages(['clock_in_at' => 'Provide at least a clock-in or clock-out time.']);
        }
        if ($clockInAt && $clockOutAt && $clockOutAt <= $clockInAt) {
            throw ValidationException::withMessages(['clock_out_at' => 'Clock-out must be after clock-in.']);
        }

        return DB::transaction(function () use ($administrator, $employee, $workDate, $clockInAt, $clockOutAt, $reason): AttendanceRecord {
            $employee = EmployeeProfile::query()->lockForUpdate()->findOrFail($employee->id);
            if (AttendanceRecord::query()->where('employee_profile_id', $employee->id)->whereDate('work_date', $workDate)->exists()) {
                throw ValidationException::withMessages(['work_date' => 'An attendance record already exists for this employee and work date.']);
            }

            $record = AttendanceRecord::create([
                'employee_profile_id' => $employee->id,
                'work_date' => $workDate,
                'timezone' => $this->settings->timezone()->getName(),
                'clock_in_at' => $clockInAt,
                'clock_out_at' => $clockOutAt,
                'status' => AttendanceStatus::Corrected,
                'source' => 'admin_correction',
                'created_by' => $administrator->id,
                'updated_by' => $administrator->id,
            ]);
            $after = $record->fresh()->only(['clock_in_at', 'clock_out_at', 'status', 'source']);

            AttendanceRevision::create([
                'attendance_record_id' => $record->id,
                'before_values' => [],
                'after_values' => $after,
                'reason' => $reason,
                'changed_by' => $administrator->id,
                'created_at' => now('UTC'),
            ]);
            $this->audit->record($administrator, 'attendance.created.directly', $record, [], $after, ['reason' => $reason]);

            return $record->fresh();
        });
    }

    private function ensureAdministrator(User $user): void
    {
        if (! $user->isAdministrator() || ! $user->is_active) {
            throw ValidationException::withMessages(['administrator' => 'An active administrator is required.']);
        }
    }

    private function normal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->utc()->format('Y-m-d H:i:s');
    }
}
