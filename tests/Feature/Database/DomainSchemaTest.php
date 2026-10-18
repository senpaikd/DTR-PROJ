<?php

namespace Tests\Feature\Database;

use App\Enums\AttendanceStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\UserRole;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Holiday;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_have_typed_roles_and_employee_profiles(): void
    {
        $user = User::factory()->create(['role' => UserRole::Employee]);
        $profile = EmployeeProfile::factory()->for($user)->create();

        $this->assertSame(UserRole::Employee, $user->role);
        $this->assertTrue($user->employeeProfile->is($profile));
        $this->assertTrue($profile->user->is($user));
    }

    public function test_unique_domain_identifiers_are_enforced(): void
    {
        $department = Department::factory()->create(['name' => 'Operations']);

        try {
            Department::factory()->create(['name' => 'Operations']);
            $this->fail('Duplicate department names must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseCount('departments', 1);
        }

        Holiday::factory()->create(['date' => '2026-06-12']);
        $this->expectException(QueryException::class);
        Holiday::factory()->create(['date' => '2026-06-12']);
    }

    public function test_employee_numbers_and_schedule_weekdays_are_unique(): void
    {
        $first = EmployeeProfile::factory()->create(['employee_number' => 'EMP-1001']);
        $schedule = WorkSchedule::factory()->create();
        WorkScheduleDay::factory()->for($schedule)->create(['weekday' => 1]);

        $this->assertNotNull($first->user);

        try {
            EmployeeProfile::factory()->create(['employee_number' => 'EMP-1001']);
            $this->fail('Duplicate employee numbers must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseCount('employee_profiles', 1);
        }

        $this->expectException(QueryException::class);
        WorkScheduleDay::factory()->for($schedule)->create(['weekday' => 1]);
    }

    public function test_attendance_relationships_and_status_casts_are_available(): void
    {
        $employee = EmployeeProfile::factory()->create();
        $record = AttendanceRecord::factory()->for($employee)->create([
            'status' => AttendanceStatus::Completed,
        ]);
        $request = AttendanceCorrectionRequest::factory()
            ->for($employee)
            ->for($record)
            ->create(['status' => CorrectionRequestStatus::Pending]);

        $this->assertSame(AttendanceStatus::Completed, $record->status);
        $this->assertSame(CorrectionRequestStatus::Pending, $request->status);
        $this->assertTrue($employee->attendanceRecords->contains($record));
        $this->assertTrue($record->correctionRequests->contains($request));
    }
}
