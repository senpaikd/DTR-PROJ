<?php

namespace Tests\Unit\Attendance;

use App\Domain\Attendance\AttendanceStatusResolver;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\EmployeeProfile;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_record_status_is_returned(): void
    {
        $employee = EmployeeProfile::factory()->create();
        AttendanceRecord::factory()->for($employee)->create(['work_date' => '2026-10-15', 'status' => AttendanceStatus::Corrected]);
        $this->assertSame(AttendanceStatus::Corrected, app(AttendanceStatusResolver::class)->forDate($employee, CarbonImmutable::parse('2026-10-15')));
    }

    public function test_missing_past_attendance_uses_neutral_status(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-20 12:00:00', 'Asia/Manila'));
        $employee = EmployeeProfile::factory()->create();
        $this->assertSame(AttendanceStatus::NoAttendanceRecorded, app(AttendanceStatusResolver::class)->forDate($employee, CarbonImmutable::parse('2026-10-15')));
        CarbonImmutable::setTestNow();
    }
}
