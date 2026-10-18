<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdministrator() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->employeeProfile !== null;
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        return $user->employeeProfile?->is($record->employeeProfile) ?? false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AttendanceRecord $record): bool
    {
        return false;
    }

    public function delete(User $user, AttendanceRecord $record): bool
    {
        return false;
    }
}
