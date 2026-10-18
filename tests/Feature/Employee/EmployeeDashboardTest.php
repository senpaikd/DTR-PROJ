<?php

namespace Tests\Feature\Employee;

use App\Models\EmployeeProfile;
use App\Models\EmployeeScheduleAssignment;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_employee_dashboard_contains_live_attendance_and_id_card_content(): void
    {
        $employee = $this->employee();
        $this->actingAs($employee->user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Your workday')
            ->assertSee($employee->employee_number)
            ->assertSee('Clock in')
            ->assertSee('Employee ID');
    }

    public function test_employee_can_clock_in_and_out_through_server_owned_actions(): void
    {
        $employee = $this->employee();
        CarbonImmutable::setTestNow('2026-10-19 01:00:00');
        $this->actingAs($employee->user)->post('/attendance/clock-in')->assertRedirect('/dashboard');
        $this->assertDatabaseCount('attendance_records', 1);

        CarbonImmutable::setTestNow('2026-10-19 09:00:00');
        $this->actingAs($employee->user)->post('/attendance/clock-out')->assertRedirect('/dashboard');
        $this->assertNotNull($employee->attendanceRecords()->first()->clock_out_at);
    }

    public function test_guest_cannot_use_attendance_actions(): void
    {
        $this->post('/attendance/clock-in')->assertRedirect('/login');
    }

    private function employee(): EmployeeProfile
    {
        $employee = EmployeeProfile::factory()->create();
        $schedule = WorkSchedule::factory()->create();
        WorkScheduleDay::factory()->for($schedule)->create(['weekday' => 1]);
        EmployeeScheduleAssignment::factory()->for($employee)->for($schedule)->create(['effective_from' => '2026-01-01']);

        return $employee;
    }
}
