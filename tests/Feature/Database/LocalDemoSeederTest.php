<?php

namespace Tests\Feature\Database;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_local_admin_and_employee_accounts_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@oras.test')->firstOrFail();
        $employee = User::where('email', 'employee@oras.test')->firstOrFail();
        $this->assertSame(UserRole::Administrator, $admin->role);
        $this->assertSame(UserRole::Employee, $employee->role);
        $this->assertFalse($admin->must_change_password);
        $this->assertFalse($employee->must_change_password);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue(Hash::check('password', $employee->password));
        $this->assertSame('EMP-0001', $employee->employeeProfile->employee_number);
        $this->assertDatabaseCount('users', 2);
    }
}
