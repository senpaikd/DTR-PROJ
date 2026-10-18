<?php

namespace Database\Factories;

use App\Models\EmployeeProfile;
use App\Models\EmployeeScheduleAssignment;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeScheduleAssignmentFactory extends Factory
{
    protected $model = EmployeeScheduleAssignment::class;

    public function definition(): array
    {
        return ['employee_profile_id' => EmployeeProfile::factory(), 'work_schedule_id' => WorkSchedule::factory(), 'effective_from' => now()->startOfYear(), 'effective_to' => null];
    }
}
