<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkScheduleDay extends Model
{
    use HasFactory;

    protected $fillable = ['work_schedule_id', 'weekday', 'is_workday', 'starts_at', 'ends_at', 'is_overnight'];

    protected function casts(): array
    {
        return ['weekday' => 'integer', 'is_workday' => 'boolean', 'is_overnight' => 'boolean'];
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }
}
