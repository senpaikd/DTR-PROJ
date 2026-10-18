<?php

namespace Tests\Feature\Attendance;

use App\Domain\Attendance\AttendanceService;
use App\Enums\AttendanceStatus;
use App\Exceptions\AttendanceSequenceException;
use App\Models\EmployeeProfile;
use App\Models\EmployeeScheduleAssignment;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_clock_in_and_out_use_server_time_and_elapsed_duration(): void
    {
        $employee = $this->scheduledEmployee();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-19 01:05:00', 'UTC'));

        $in = app(AttendanceService::class)->clockIn($employee->user);
        $this->assertFalse($in->idempotent);
        $this->assertSame(AttendanceStatus::ClockedIn, $in->record->status);
        $this->assertSame('2026-10-19 01:05:00', $in->record->clock_in_at->utc()->format('Y-m-d H:i:s'));

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-19 09:35:00', 'UTC'));
        $out = app(AttendanceService::class)->clockOut($employee->user);
        $this->assertSame(AttendanceStatus::Completed, $out->record->status);
        $this->assertSame(510, $out->elapsedMinutes);
    }

    public function test_repeated_clock_in_and_clock_out_are_idempotent(): void
    {
        $employee = $this->scheduledEmployee();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-19 01:00:00', 'UTC'));

        $first = app(AttendanceService::class)->clockIn($employee->user);
        $repeat = app(AttendanceService::class)->clockIn($employee->user);
        $this->assertTrue($repeat->idempotent);
        $this->assertSame($first->record->id, $repeat->record->id);
        $this->assertDatabaseCount('attendance_records', 1);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-19 10:00:00', 'UTC'));
        app(AttendanceService::class)->clockOut($employee->user);
        $repeatOut = app(AttendanceService::class)->clockOut($employee->user);
        $this->assertTrue($repeatOut->idempotent);
        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_out_of_schedule_clock_in_is_accepted_and_flagged(): void
    {
        $employee = EmployeeProfile::factory()->create();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-19 01:00:00', 'UTC'));

        $result = app(AttendanceService::class)->clockIn($employee->user);

        $this->assertTrue($result->record->clock_in_outside_schedule);
        $this->assertSame('2026-10-19', $result->record->work_date->toDateString());
    }

    public function test_clock_out_without_a_record_is_rejected(): void
    {
        $employee = EmployeeProfile::factory()->create();
        $this->expectException(AttendanceSequenceException::class);
        app(AttendanceService::class)->clockOut($employee->user);
    }

    private function scheduledEmployee(): EmployeeProfile
    {
        $employee = EmployeeProfile::factory()->create();
        $schedule = WorkSchedule::factory()->create();
        WorkScheduleDay::factory()->for($schedule)->create(['weekday' => 1, 'starts_at' => '09:00:00', 'ends_at' => '18:00:00']);
        EmployeeScheduleAssignment::factory()->for($employee)->for($schedule)->create(['effective_from' => '2026-01-01']);

        return $employee;
    }
}
