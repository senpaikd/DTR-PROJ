<?php

namespace Tests\Feature\Employee;

use App\Models\AttendanceRecord;
use App\Models\EmployeeProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_history_and_print_views_are_scoped_to_the_authenticated_employee(): void
    {
        $employee = EmployeeProfile::factory()->create();
        $owned = AttendanceRecord::factory()->for($employee)->create(['work_date' => '2026-10-10']);
        $other = AttendanceRecord::factory()->create(['work_date' => '2026-10-10']);

        $this->actingAs($employee->user)->get('/attendance?month=2026-10')->assertOk()->assertSee($owned->work_date->format('M j'))->assertDontSee($other->employeeProfile->employee_number);
        $this->actingAs($employee->user)->get('/attendance/print?month=2026-10')->assertOk()->assertSee($employee->employee_number);
    }

    public function test_employee_can_open_and_submit_a_correction_form(): void
    {
        $employee = EmployeeProfile::factory()->create();
        $record = AttendanceRecord::factory()->for($employee)->create(['work_date' => now()->toDateString()]);

        $this->actingAs($employee->user)->get('/corrections/create?record='.$record->id)->assertOk()->assertSee('Request a correction');
        $this->actingAs($employee->user)->post('/corrections', [
            'attendance_record_id' => $record->id,
            'work_date' => now()->toDateString(),
            'proposed_clock_in_at' => now()->startOfDay()->addHours(9)->format('Y-m-d\TH:i'),
            'proposed_clock_out_at' => now()->startOfDay()->addHours(18)->format('Y-m-d\TH:i'),
            'reason' => 'I accidentally recorded the wrong time and need it corrected.',
        ])->assertRedirect('/corrections');
        $this->assertDatabaseCount('attendance_correction_requests', 1);
    }
}
