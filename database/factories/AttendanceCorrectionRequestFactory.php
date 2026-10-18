<?php

namespace Database\Factories;

use App\Enums\CorrectionRequestStatus;
use App\Models\AttendanceCorrectionRequest;
use App\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceCorrectionRequestFactory extends Factory
{
    protected $model = AttendanceCorrectionRequest::class;

    public function definition(): array
    {
        return ['employee_profile_id' => EmployeeProfile::factory(), 'work_date' => now()->toDateString(), 'proposed_clock_in_at' => now()->startOfDay()->addHours(1), 'proposed_clock_out_at' => now()->startOfDay()->addHours(9), 'reason' => fake()->sentence(), 'status' => CorrectionRequestStatus::Pending];
    }
}
