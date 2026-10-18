<?php

namespace App\Policies;

use App\Models\AttendanceCorrectionRequest;
use App\Models\User;

class AttendanceCorrectionRequestPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdministrator() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->employeeProfile !== null;
    }

    public function view(User $user, AttendanceCorrectionRequest $request): bool
    {
        return $user->employeeProfile?->is($request->employeeProfile) ?? false;
    }

    public function create(User $user): bool
    {
        return $user->employeeProfile !== null;
    }

    public function update(User $user, AttendanceCorrectionRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, AttendanceCorrectionRequest $request): bool
    {
        return false;
    }
}
