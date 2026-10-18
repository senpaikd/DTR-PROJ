<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use App\Enums\CorrectionRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = ['employee_profile_id', 'attendance_record_id', 'work_date', 'proposed_clock_in_at', 'proposed_clock_out_at', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_notes', 'final_clock_in_at', 'final_clock_out_at', 'adjustment_explanation'];

    protected function casts(): array
    {
        return [
            'work_date' => 'date', 'proposed_clock_in_at' => UtcDateTime::class, 'proposed_clock_out_at' => UtcDateTime::class,
            'status' => CorrectionRequestStatus::class, 'reviewed_at' => UtcDateTime::class,
            'final_clock_in_at' => UtcDateTime::class, 'final_clock_out_at' => UtcDateTime::class,
        ];
    }

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function revisions()
    {
        return $this->hasMany(AttendanceRevision::class);
    }
}
