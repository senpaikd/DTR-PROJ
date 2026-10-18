<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_administrator_can_open_core_admin_pages(): void
    {
        $administrator = User::factory()->administrator()->create();

        foreach ([
            '/admin',
            '/admin/departments',
            '/admin/positions',
            '/admin/work-schedules',
            '/admin/holidays',
            '/admin/employees',
            '/admin/attendance-records',
            '/admin/correction-requests',
            '/admin/audit-logs',
            '/admin/manage-application-settings',
            '/admin/reports/attendance',
        ] as $uri) {
            $this->actingAs($administrator)->get($uri)->assertOk();
        }
    }

    public function test_employee_cannot_open_admin_resources(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)->get('/admin/employees')->assertForbidden();
    }

    public function test_attendance_page_exposes_the_audited_direct_creation_action(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get('/admin/attendance-records')
            ->assertOk()
            ->assertSee('Add attendance');
    }
}
