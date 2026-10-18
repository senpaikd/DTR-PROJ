<?php

namespace Tests\Feature\Corrections;

use App\Domain\Corrections\CorrectionReviewData;
use App\Domain\Corrections\CorrectionReviewService;
use App\Enums\AttendanceStatus;
use App\Enums\CorrectionRequestStatus;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CorrectionReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_updates_official_record_and_creates_immutable_revision_and_audit(): void
    {
        $admin = User::factory()->administrator()->create();
        $record = AttendanceRecord::factory()->create(['clock_in_at' => '2026-10-10 01:00:00']);
        $request = AttendanceCorrectionRequest::factory()->for($record->employeeProfile)->for($record)->create([
            'work_date' => $record->work_date,
            'proposed_clock_in_at' => '2026-10-10 01:15:00',
            'proposed_clock_out_at' => '2026-10-10 09:00:00',
        ]);

        app(CorrectionReviewService::class)->approve($admin, $request, new CorrectionReviewData('2026-10-10 01:15:00', '2026-10-10 09:00:00', 'Verified with the employee.'));

        $this->assertSame(CorrectionRequestStatus::Approved, $request->fresh()->status);
        $this->assertSame(AttendanceStatus::Corrected, $record->fresh()->status);
        $this->assertSame('2026-10-10 01:15:00', $record->fresh()->clock_in_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('attendance_revisions', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.correction.approved', 'actor_id' => $admin->id]);
    }

    public function test_adjusting_employee_proposal_requires_explanation(): void
    {
        $admin = User::factory()->administrator()->create();
        $request = AttendanceCorrectionRequest::factory()->create(['proposed_clock_in_at' => '2026-10-10 01:00:00']);
        $this->expectException(ValidationException::class);
        app(CorrectionReviewService::class)->approve($admin, $request, new CorrectionReviewData('2026-10-10 02:00:00', null, null));
    }

    public function test_rejection_preserves_official_record(): void
    {
        $admin = User::factory()->administrator()->create();
        $record = AttendanceRecord::factory()->create();
        $request = AttendanceCorrectionRequest::factory()->for($record->employeeProfile)->for($record)->create();
        $before = $record->clock_in_at;

        app(CorrectionReviewService::class)->reject($admin, $request, 'The supporting details do not match the record.');

        $this->assertSame(CorrectionRequestStatus::Rejected, $request->fresh()->status);
        $this->assertTrue($record->fresh()->clock_in_at->equalTo($before));
        $this->assertDatabaseCount('attendance_revisions', 0);
    }
}
