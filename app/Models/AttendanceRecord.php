<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = ['employee_profile_id', 'work_date', 'timezone', 'scheduled_start_at', 'scheduled_end_at', 'clock_in_at', 'clock_out_at', 'status', 'clock_in_outside_schedule', 'clock_out_outside_schedule', 'source', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'work_date' => 'date', 'scheduled_start_at' => UtcDateTime::class, 'scheduled_end_at' => UtcDateTime::class,
            'clock_in_at' => UtcDateTime::class, 'clock_out_at' => UtcDateTime::class, 'status' => AttendanceStatus::class,
            'clock_in_outside_schedule' => 'boolean', 'clock_out_outside_schedule' => 'boolean',
        ];
    }

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function correctionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    public function revisions()
    {
        return $this->hasMany(AttendanceRevision::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
