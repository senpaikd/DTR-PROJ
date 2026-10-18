<?php

namespace App\Reports;

use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Builder;

class AttendanceReportQuery
{
    public function build(AttendanceReportFilters $filters): Builder
    {
        return AttendanceRecord::query()
            ->with(['employeeProfile.user', 'employeeProfile.department'])
            ->whereBetween('work_date', [$filters->from->toDateString(), $filters->to->toDateString()])
            ->when($filters->employeeId, fn (Builder $query, int $employeeId) => $query->where('employee_profile_id', $employeeId))
            ->when($filters->departmentId, fn (Builder $query, int $departmentId) => $query->whereHas('employeeProfile', fn (Builder $profile) => $profile->where('department_id', $departmentId)))
            ->when($filters->status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('work_date')
            ->orderBy('employee_profile_id');
    }
}
