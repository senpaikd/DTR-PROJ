<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FirstLoginPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_password_users_are_redirected_until_they_change_it(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/first-login/password');
        $this->actingAs($user)->get('/first-login/password')->assertOk();
    }

    public function test_user_can_replace_temporary_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Old-Password-42!'), 'must_change_password' => true]);

        $this->actingAs($user)->put('/first-login/password', [
            'current_password' => 'Old-Password-42!',
            'password' => 'New-Secure-Password-84!',
            'password_confirmation' => 'New-Secure-Password-84!',
        ])->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue(Hash::check('New-Secure-Password-84!', $user->password));
    }

    public function test_temporary_password_administrator_is_gated_then_returned_to_admin_panel(): void
    {
        $administrator = User::factory()->administrator()->create(['must_change_password' => true]);

        $this->actingAs($administrator)->get('/admin')->assertRedirect(route('first-login.password.edit'));

        $this->put(route('first-login.password.update'), [
            'current_password' => 'password',
            'password' => 'Replacement-Admin-2026!',
            'password_confirmation' => 'Replacement-Admin-2026!',
        ])->assertRedirect('/admin');
    }
}
