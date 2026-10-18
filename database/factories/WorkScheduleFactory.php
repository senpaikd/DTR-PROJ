<?php

namespace Database\Factories;

use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkScheduleFactory extends Factory
{
    protected $model = WorkSchedule::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true).' schedule', 'description' => fake()->sentence(), 'is_active' => true];
    }
}
