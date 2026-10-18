<?php

namespace App\Domain\Accounts;

use App\Domain\Auditing\AuditRecorder;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class EmployeeAccountService
{
    public function __construct(
        private readonly TemporaryPassphraseGenerator $generator,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(array $userData, array $profileData, ?string $temporaryPassword = null, ?User $administrator = null): ProvisionedEmployee
    {
        $password = $temporaryPassword ?: $this->generator->generate();
        $this->validatePassword($password);

        return DB::transaction(function () use ($userData, $profileData, $password, $administrator): ProvisionedEmployee {
            $user = User::create([
                'name' => trim($userData['name']),
                'email' => mb_strtolower(trim($userData['email'])),
                'password' => Hash::make($password),
                'role' => UserRole::Employee,
                'is_active' => true,
                'must_change_password' => true,
            ]);
            $user->employeeProfile()->create($profileData);
            if ($administrator) {
                $this->audit->record($administrator, 'employee.created', $user, [], [
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_number' => $user->employeeProfile->employee_number,
                    'credential_mode' => 'temporary',
                ]);
            }

            return new ProvisionedEmployee($user->load('employeeProfile'), $password);
        });
    }

    public function resetPassword(User $user, ?string $temporaryPassword = null, ?User $administrator = null): TemporaryCredential
    {
        $password = $temporaryPassword ?: $this->generator->generate();
        $this->validatePassword($password);

        return DB::transaction(function () use ($user, $password, $administrator): TemporaryCredential {
            $user->forceFill([
                'password' => Hash::make($password),
                'must_change_password' => true,
                'password_changed_at' => null,
                'remember_token' => null,
            ])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            if ($administrator) {
                $this->audit->record($administrator, 'employee.password.reset', $user, [], [
                    'must_change_password' => true,
                    'sessions_invalidated' => true,
                ]);
            }

            return new TemporaryCredential($user->fresh(), $password);
        });
    }

    public function setActiveStatus(User $user, bool $isActive, User $administrator): User
    {
        return DB::transaction(function () use ($user, $isActive, $administrator): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $before = ['is_active' => $user->is_active];
            if ($user->is_active === $isActive) {
                return $user;
            }

            $user->forceFill(['is_active' => $isActive])->save();
            if (! $isActive) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            $this->audit->record(
                $administrator,
                $isActive ? 'employee.activated' : 'employee.deactivated',
                $user,
                $before,
                ['is_active' => $isActive],
                ['sessions_invalidated' => ! $isActive],
            );

            return $user->fresh();
        });
    }

    private function validatePassword(string $password): void
    {
        Validator::make(['password' => $password], [
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()->symbols()],
        ])->validate();
    }
}
