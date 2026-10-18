<?php

namespace Database\Factories;

use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return ['date' => fake()->unique()->dateTimeBetween('now', '+2 years'), 'name' => fake()->words(2, true), 'description' => fake()->sentence(), 'is_active' => true];
    }
}
