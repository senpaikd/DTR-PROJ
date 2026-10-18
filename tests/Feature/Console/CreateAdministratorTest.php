<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdministratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_be_created_non_interactively(): void
    {
        $this->artisan('dtr:create-admin', [
            '--name' => 'System Administrator',
            '--email' => 'ADMIN@EXAMPLE.COM',
            '--password' => 'Temporary-Admin-42!',
        ])->assertSuccessful();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertSame(UserRole::Administrator, $admin->role);
        $this->assertTrue($admin->must_change_password);
        $this->assertTrue(Hash::check('Temporary-Admin-42!', $admin->password));
    }

    public function test_duplicate_administrator_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('dtr:create-admin', ['--name' => 'Admin', '--email' => 'admin@example.com', '--password' => 'Temporary-Admin-42!'])
            ->assertFailed();
    }
}
