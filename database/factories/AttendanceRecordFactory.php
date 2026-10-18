<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        $date = fake()->unique()->dateTimeBetween('-6 months', 'now');

        return ['employee_profile_id' => EmployeeProfile::factory(), 'work_date' => $date, 'timezone' => 'Asia/Manila', 'clock_in_at' => $date->format('Y-m-d').' 01:00:00', 'clock_out_at' => $date->format('Y-m-d').' 09:00:00', 'status' => AttendanceStatus::Completed, 'source' => 'employee'];
    }
}
