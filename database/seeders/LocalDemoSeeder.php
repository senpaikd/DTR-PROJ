<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\UserRole;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\EmployeeScheduleAssignment;
use App\Models\Holiday;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::updateOrCreate(['name' => 'Operations'], ['description' => 'Local demonstration department', 'is_active' => true]);
        $position = Position::updateOrCreate(['department_id' => $department->id, 'name' => 'Operations Associate'], ['is_active' => true]);

        User::updateOrCreate(['email' => 'admin@oras.test'], [
            'name' => 'Oras Administrator',
            'password' => Hash::make('password'),
            'role' => UserRole::Administrator,
            'is_active' => true,
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        $employeeUser = User::updateOrCreate(['email' => 'employee@oras.test'], [
            'name' => 'Maria Santos',
            'password' => Hash::make('password'),
            'role' => UserRole::Employee,
            'is_active' => true,
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $profile = EmployeeProfile::updateOrCreate(['user_id' => $employeeUser->id], [
            'employee_number' => 'EMP-0001',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employment_start_date' => now()->subYear()->toDateString(),
        ]);

        $schedule = WorkSchedule::updateOrCreate(['name' => 'Standard Weekday'], ['description' => 'Monday to Friday, 9:00 AM to 6:00 PM', 'is_active' => true]);
        WorkScheduleDay::query()->where('work_schedule_id', $schedule->id)->where('weekday', 0)->delete();
        foreach (range(1, 7) as $weekday) {
            $isWorkday = $weekday >= 1 && $weekday <= 5;
            WorkScheduleDay::updateOrCreate(['work_schedule_id' => $schedule->id, 'weekday' => $weekday], [
                'is_workday' => $isWorkday,
                'starts_at' => $isWorkday ? '09:00:00' : null,
                'ends_at' => $isWorkday ? '18:00:00' : null,
                'is_overnight' => false,
            ]);
        }
        EmployeeScheduleAssignment::firstOrCreate([
            'employee_profile_id' => $profile->id,
            'work_schedule_id' => $schedule->id,
            'effective_from' => now()->startOfYear()->toDateString(),
        ]);

        Holiday::updateOrCreate(['date' => now()->year.'-12-25'], [
            'name' => 'Christmas Day',
            'description' => 'Local demonstration holiday',
            'is_active' => true,
        ]);

        $recentWorkdays = collect(range(1, 10))
            ->map(fn (int $days) => CarbonImmutable::now('Asia/Manila')->subDays($days))
            ->filter(fn (CarbonImmutable $date) => $date->isWeekday())
            ->take(5)
            ->values();

        foreach ($recentWorkdays as $index => $date) {
            $clockIn = $date->setTime(9, $index === 1 ? 12 : 0)->utc();
            $clockOut = $date->setTime(18, 0)->utc();
            AttendanceRecord::updateOrCreate([
                'employee_profile_id' => $profile->id,
                'work_date' => $date->toDateString(),
            ], [
                'timezone' => 'Asia/Manila',
                'scheduled_start_at' => $date->setTime(9, 0)->utc(),
                'scheduled_end_at' => $date->setTime(18, 0)->utc(),
                'clock_in_at' => $clockIn,
                'clock_out_at' => $clockOut,
                'status' => AttendanceStatus::Completed,
                'source' => 'demo',
            ]);
        }

        $recordForCorrection = AttendanceRecord::query()
            ->where('employee_profile_id', $profile->id)
            ->orderBy('work_date')
            ->first();

        if ($recordForCorrection) {
            AttendanceCorrectionRequest::updateOrCreate([
                'employee_profile_id' => $profile->id,
                'work_date' => $recordForCorrection->work_date,
                'status' => CorrectionRequestStatus::Pending,
            ], [
                'attendance_record_id' => $recordForCorrection->id,
                'proposed_clock_in_at' => $recordForCorrection->clock_in_at?->addMinutes(5),
                'proposed_clock_out_at' => $recordForCorrection->clock_out_at,
                'reason' => 'Local demonstration request: clock-in was recorded five minutes early by mistake.',
            ]);
        }
    }
}
