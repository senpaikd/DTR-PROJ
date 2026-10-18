<?php

namespace App\Policies;

use App\Models\EmployeeProfile;
use App\Models\User;

class EmployeeProfilePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdministrator() ? true : null;
    }

    public function view(User $user, EmployeeProfile $profile): bool
    {
        return $user->employeeProfile?->is($profile) ?? false;
    }

    public function update(User $user, EmployeeProfile $profile): bool
    {
        return $user->employeeProfile?->is($profile) ?? false;
    }

    public function delete(User $user, EmployeeProfile $profile): bool
    {
        return false;
    }
}
