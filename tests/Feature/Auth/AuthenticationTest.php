<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_active_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Correct-Horse-42!'), 'must_change_password' => false]);

        $this->post('/login', ['email' => strtoupper($user->email), 'password' => 'Correct-Horse-42!'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Correct-Horse-42!'), 'is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'Correct-Horse-42!'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_administrator_login_and_employee_dashboard_url_lead_to_admin_panel(): void
    {
        $administrator = User::factory()->administrator()->create([
            'password' => Hash::make('Correct-Horse-42!'),
            'must_change_password' => false,
        ]);

        $this->post('/login', ['email' => $administrator->email, 'password' => 'Correct-Horse-42!'])
            ->assertRedirect('/admin');
        $this->get('/dashboard')->assertRedirect('/admin');
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Correct-Horse-42!')]);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
