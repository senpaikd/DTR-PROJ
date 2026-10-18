<?php

namespace Tests\Feature\Corrections;

use App\Domain\Corrections\CorrectionReviewService;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdministrativeCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_correct_a_record_with_revision_and_audit_history(): void
    {
        $administrator = User::factory()->administrator()->create();
        $record = AttendanceRecord::factory()->create([
            'clock_in_at' => '2026-10-10 01:00:00',
            'clock_out_at' => '2026-10-10 09:00:00',
        ]);

        app(CorrectionReviewService::class)->correctDirectly($administrator, $record, [
            'clock_in_at' => '2026-10-10 01:15:00',
            'clock_out_at' => '2026-10-10 09:15:00',
        ], 'Verified against the signed manual attendance sheet.');

        $this->assertSame(AttendanceStatus::Corrected, $record->fresh()->status);
        $this->assertDatabaseHas('attendance_revisions', [
            'attendance_record_id' => $record->id,
            'changed_by' => $administrator->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.corrected.directly',
            'actor_id' => $administrator->id,
        ]);
    }

    public function test_direct_correction_requires_a_reason_and_valid_clock_order(): void
    {
        $administrator = User::factory()->administrator()->create();
        $record = AttendanceRecord::factory()->create();

        $this->expectException(ValidationException::class);

        app(CorrectionReviewService::class)->correctDirectly($administrator, $record, [
            'clock_in_at' => '2026-10-10 09:00:00',
            'clock_out_at' => '2026-10-10 01:00:00',
        ], '');
    }

    public function test_administrator_can_create_attendance_with_revision_and_audit_history(): void
    {
        $administrator = User::factory()->administrator()->create();
        $employee = EmployeeProfile::factory()->create();

        $record = app(CorrectionReviewService::class)->createDirectly($administrator, $employee, '2026-10-09', [
            'clock_in_at' => '2026-10-09 01:00:00',
            'clock_out_at' => '2026-10-09 09:00:00',
        ], 'Created from the signed manual attendance sheet.');

        $this->assertSame($employee->id, $record->employee_profile_id);
        $this->assertSame(AttendanceStatus::Corrected, $record->status);
        $this->assertDatabaseHas('attendance_revisions', [
            'attendance_record_id' => $record->id,
            'changed_by' => $administrator->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.created.directly',
            'subject_id' => $record->id,
        ]);
    }
}
