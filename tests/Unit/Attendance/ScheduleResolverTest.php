<?php

namespace Tests\Unit\Attendance;

use App\Domain\Attendance\ScheduleResolver;
use App\Models\EmployeeProfile;
use App\Models\EmployeeScheduleAssignment;
use App\Models\Holiday;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_an_effective_recurring_shift_to_utc(): void
    {
        $employee = EmployeeProfile::factory()->create();
        $schedule = WorkSchedule::factory()->create();
        WorkScheduleDay::factory()->for($schedule)->create(['weekday' => 1, 'starts_at' => '09:00:00', 'ends_at' => '18:00:00']);
        EmployeeScheduleAssignment::factory()->for($employee)->for($schedule)->create(['effective_from' => '2026-01-01']);

        $shift = app(ScheduleResolver::class)->forEmployeeAt($employee, CarbonImmutable::parse('2026-10-19 01:30:00', 'UTC'));

        $this->assertSame('2026-10-19', $shift->workDate->toDateString());
        $this->assertSame('2026-10-19 01:00:00', $shift->startsAtUtc->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-19 10:00:00', $shift->endsAtUtc->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Manila', $shift->timezone);
    }

    public function test_an_overnight_shift_after_midnight_keeps_the_previous_work_date(): void
    {
        $employee = EmployeeProfile::factory()->create();
        $schedule = WorkSchedule::factory()->create();
        WorkScheduleDay::factory()->for($schedule)->create(['weekday' => 1, 'starts_at' => '22:00:00', 'ends_at' => '06:00:00', 'is_overnight' => true]);
        EmployeeScheduleAssignment::factory()->for($employee)->for($schedule)->create(['effective_from' => '2026-01-01']);

        $shift = app(ScheduleResolver::class)->forEmployeeAt($employee, CarbonImmutable::parse('2026-10-19 18:00:00', 'UTC'));

        $this->assertSame('2026-10-19', $shift->workDate->toDateString());
        $this->assertSame('2026-10-20 06:00:00', $shift->endsAtLocal()->format('Y-m-d H:i:s'));
    }

    public function test_holidays_and_non_workdays_do_not_resolve_a_shift(): void
    {
        $employee = EmployeeProfile::factory()->create();
        $schedule = WorkSchedule::factory()->create();
        WorkScheduleDay::factory()->for($schedule)->create(['weekday' => 1]);
        EmployeeScheduleAssignment::factory()->for($employee)->for($schedule)->create(['effective_from' => '2026-01-01']);
        Holiday::factory()->create(['date' => '2026-10-19', 'is_active' => true]);

        $this->assertNull(app(ScheduleResolver::class)->forEmployeeAt($employee, CarbonImmutable::parse('2026-10-19 01:00:00', 'UTC')));
    }
}
