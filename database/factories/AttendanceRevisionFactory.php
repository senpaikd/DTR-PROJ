<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRevisionFactory extends Factory
{
    protected $model = AttendanceRevision::class;

    public function definition(): array
    {
        return ['attendance_record_id' => AttendanceRecord::factory(), 'before_values' => [], 'after_values' => ['status' => 'corrected'], 'reason' => fake()->sentence(), 'changed_by' => User::factory(), 'created_at' => now()];
    }
}
