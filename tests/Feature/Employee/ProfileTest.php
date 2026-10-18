<?php

namespace Tests\Feature\Employee;

use App\Models\EmployeeProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_update_phone_but_not_protected_identity_fields(): void
    {
        $profile = EmployeeProfile::factory()->create(['phone' => null]);
        $user = $profile->user;

        $this->actingAs($user)->put(route('profile.update'), [
            'phone' => '+63 917 555 0101',
            'name' => 'Changed Name',
            'employee_number' => 'HACKED',
        ])->assertRedirect(route('profile.edit'));

        $this->assertSame('+63 917 555 0101', $profile->fresh()->phone);
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertNotSame('HACKED', $profile->fresh()->employee_number);
    }

    public function test_employee_can_change_their_password_with_the_current_password(): void
    {
        $profile = EmployeeProfile::factory()->create();
        $user = $profile->user;

        $this->actingAs($user)->get(route('profile.password.edit'))
            ->assertOk()
            ->assertSee('Change password');

        $this->put(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'Private-Replacement-2026!',
            'password_confirmation' => 'Private-Replacement-2026!',
        ])->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('Private-Replacement-2026!', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->password_changed_at);
        $this->assertAuthenticatedAs($user);
    }
}
