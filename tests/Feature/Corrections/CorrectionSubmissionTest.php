<?php

namespace Tests\Feature\Corrections;

use App\Domain\Corrections\CorrectionRequestService;
use App\Enums\CorrectionRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\EmployeeProfile;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CorrectionSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_request_a_recent_owned_record_correction_without_changing_official_time(): void
    {
        CarbonImmutable::setTestNow('2026-10-16 04:00:00');
        $employee = EmployeeProfile::factory()->create();
        $record = AttendanceRecord::factory()->for($employee)->create(['work_date' => '2026-10-10', 'clock_in_at' => '2026-10-10 01:00:00']);

        $request = app(CorrectionRequestService::class)->submit($employee->user, [
            'attendance_record_id' => $record->id,
            'work_date' => '2026-10-10',
            'proposed_clock_in_at' => '2026-10-10 01:15:00',
            'proposed_clock_out_at' => '2026-10-10 09:00:00',
            'reason' => 'I double-clicked after arriving and need the actual time recorded.',
        ]);

        $this->assertSame(CorrectionRequestStatus::Pending, $request->status);
        $this->assertSame('2026-10-10 01:00:00', $record->fresh()->clock_in_at->format('Y-m-d H:i:s'));
        CarbonImmutable::setTestNow();
    }

    public function test_employee_cannot_request_another_employees_record(): void
    {
        $employee = EmployeeProfile::factory()->create();
        $otherRecord = AttendanceRecord::factory()->create(['work_date' => now()->toDateString()]);
        $this->expectException(ValidationException::class);
        app(CorrectionRequestService::class)->submit($employee->user, ['attendance_record_id' => $otherRecord->id, 'work_date' => now()->toDateString(), 'reason' => 'This record should not be available to me.']);
    }

    public function test_configured_submission_window_is_enforced(): void
    {
        CarbonImmutable::setTestNow('2026-10-16 04:00:00');
        $employee = EmployeeProfile::factory()->create();
        $this->expectException(ValidationException::class);
        try {
            app(CorrectionRequestService::class)->submit($employee->user, ['work_date' => '2026-08-01', 'reason' => 'This is outside the correction window.']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
