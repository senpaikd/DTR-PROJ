<?php

namespace App\Domain\Attendance;

use App\Models\AttendanceRecord;

final readonly class AttendanceActionResult
{
    public function __construct(public AttendanceRecord $record, public bool $idempotent = false) {}

    public function elapsedMinutes(): ?int
    {
        if (! $this->record->clock_in_at || ! $this->record->clock_out_at) {
            return null;
        }

        return (int) $this->record->clock_in_at->diffInMinutes($this->record->clock_out_at);
    }

    public function __get(string $name): mixed
    {
        return $name === 'elapsedMinutes' ? $this->elapsedMinutes() : null;
    }
}
