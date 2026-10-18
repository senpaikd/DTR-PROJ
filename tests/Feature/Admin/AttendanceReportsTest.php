<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Reports\AttendanceReportFilters;
use App\Reports\AttendanceReportQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_query_applies_date_employee_department_and_status_filters(): void
    {
        $matching = AttendanceRecord::factory()->create([
            'work_date' => '2026-10-10',
            'status' => AttendanceStatus::Completed,
        ]);
        AttendanceRecord::factory()->create(['work_date' => '2026-09-10']);

        $filters = AttendanceReportFilters::fromArray([
            'period' => 'custom',
            'from' => '2026-10-01',
            'to' => '2026-10-31',
            'employee_id' => $matching->employee_profile_id,
            'department_id' => $matching->employeeProfile->department_id,
            'status' => AttendanceStatus::Completed->value,
        ]);

        $this->assertSame([$matching->id], app(AttendanceReportQuery::class)->build($filters)->pluck('id')->all());
    }

    public function test_report_rejects_ranges_longer_than_one_year(): void
    {
        $this->expectException(ValidationException::class);

        AttendanceReportFilters::fromArray([
            'period' => 'custom',
            'from' => '2025-01-01',
            'to' => '2026-10-01',
        ]);
    }

    public function test_only_active_administrators_can_download_reports(): void
    {
        $employee = User::factory()->create();
        $administrator = User::factory()->administrator()->create();
        $query = ['period' => 'daily', 'anchor' => '2026-10-10'];

        $this->actingAs($employee)->get(route('admin.reports.export', ['format' => 'csv'] + $query))->assertForbidden();
        $this->actingAs($administrator)->get(route('admin.reports.export', ['format' => 'csv'] + $query))->assertOk();
    }
}
