<?php

namespace Tests\Feature\Accounts;

use App\Domain\Accounts\EmployeeAccountService;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_provisions_an_employee_with_a_manual_temporary_password(): void
    {
        $result = app(EmployeeAccountService::class)->create(
            ['name' => 'Maria Santos', 'email' => 'MARIA@EXAMPLE.COM'],
            ['employee_number' => 'EMP-0001'],
            'Temporary-Employee-42!',
        );

        $this->assertSame('Temporary-Employee-42!', $result->temporaryPassword);
        $this->assertSame('maria@example.com', $result->user->email);
        $this->assertSame(UserRole::Employee, $result->user->role);
        $this->assertTrue($result->user->must_change_password);
        $this->assertTrue(Hash::check($result->temporaryPassword, $result->user->password));
        $this->assertSame('EMP-0001', $result->user->employeeProfile->employee_number);
    }

    public function test_it_can_generate_a_temporary_password(): void
    {
        $result = app(EmployeeAccountService::class)->create(
            ['name' => 'Juan Dela Cruz', 'email' => 'juan@example.com'],
            ['employee_number' => 'EMP-0002'],
        );

        $this->assertNotEmpty($result->temporaryPassword);
        $this->assertTrue(Hash::check($result->temporaryPassword, $result->user->password));
    }

    public function test_administrator_employee_provisioning_is_audited_without_the_password(): void
    {
        $administrator = User::factory()->administrator()->create();

        $result = app(EmployeeAccountService::class)->create(
            ['name' => 'Audited Employee', 'email' => 'audited@example.com'],
            ['employee_number' => 'EMP-0099'],
            'Provisioned-Temporary-42!',
            $administrator,
        );

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $administrator->id,
            'action' => 'employee.created',
            'subject_id' => $result->user->id,
        ]);
        $audit = DB::table('audit_logs')->where('action', 'employee.created')->first();
        $this->assertStringNotContainsString($result->temporaryPassword, json_encode($audit));
    }

    public function test_administrator_password_reset_invalidates_sessions_and_is_audited_without_the_password(): void
    {
        $administrator = User::factory()->administrator()->create();
        $employee = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => 'employee-session',
            'user_id' => $employee->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'payload' => 'fixture',
            'last_activity' => now()->timestamp,
        ]);

        $credential = app(EmployeeAccountService::class)->resetPassword(
            $employee,
            'Replacement-Temporary-42!',
            $administrator,
        );

        $this->assertDatabaseMissing('sessions', ['user_id' => $employee->id]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $administrator->id,
            'action' => 'employee.password.reset',
            'subject_id' => $employee->id,
        ]);
        $audit = DB::table('audit_logs')->where('action', 'employee.password.reset')->first();
        $this->assertStringNotContainsString($credential->temporaryPassword, json_encode($audit));
    }

    public function test_administrator_can_deactivate_an_employee_and_invalidate_their_sessions_with_an_audit_entry(): void
    {
        $administrator = User::factory()->administrator()->create();
        $employee = User::factory()->create(['is_active' => true]);
        DB::table('sessions')->insert([
            'id' => 'active-employee-session',
            'user_id' => $employee->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'payload' => 'fixture',
            'last_activity' => now()->timestamp,
        ]);

        app(EmployeeAccountService::class)->setActiveStatus($employee, false, $administrator);

        $this->assertFalse($employee->fresh()->is_active);
        $this->assertDatabaseMissing('sessions', ['user_id' => $employee->id]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $administrator->id,
            'action' => 'employee.deactivated',
            'subject_id' => $employee->id,
        ]);
    }
}
