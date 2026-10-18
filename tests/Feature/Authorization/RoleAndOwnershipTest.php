<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\EmployeeProfile;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAndOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_employees_cannot_enter_the_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $employee = User::factory()->create(['role' => UserRole::Employee, 'must_change_password' => false]);
        EmployeeProfile::factory()->for($employee)->create();
        $this->actingAs($employee)->get('/admin')->assertForbidden();
    }

    public function test_active_administrators_can_enter_the_admin_panel(): void
    {
        $admin = User::factory()->administrator()->create(['must_change_password' => false]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_inactive_authenticated_users_are_logged_out(): void
    {
        $user = User::factory()->create(['is_active' => false, 'must_change_password' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_employees_can_only_view_their_own_attendance_and_requests(): void
    {
        $owner = EmployeeProfile::factory()->create();
        $other = EmployeeProfile::factory()->create();
        $record = AttendanceRecord::factory()->for($owner)->create();
        $request = AttendanceCorrectionRequest::factory()->for($owner)->for($record)->create();

        $this->assertTrue($owner->user->can('view', $record));
        $this->assertTrue($owner->user->can('view', $request));
        $this->assertFalse($other->user->can('view', $record));
        $this->assertFalse($other->user->can('view', $request));
    }

    public function test_administrators_can_view_all_attendance_records(): void
    {
        $admin = User::factory()->administrator()->create();
        $record = AttendanceRecord::factory()->create();

        $this->assertTrue($admin->can('view', $record));
        $this->assertTrue($admin->can('update', $record));
    }
}
