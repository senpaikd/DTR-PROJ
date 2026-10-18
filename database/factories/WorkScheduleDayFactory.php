<?php

namespace Database\Factories;

use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkScheduleDayFactory extends Factory
{
    protected $model = WorkScheduleDay::class;

    public function definition(): array
    {
        return ['work_schedule_id' => WorkSchedule::factory(), 'weekday' => fake()->numberBetween(0, 6), 'is_workday' => true, 'starts_at' => '09:00:00', 'ends_at' => '18:00:00', 'is_overnight' => false];
    }
}
