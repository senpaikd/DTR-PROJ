<?php

namespace App\Exports;

use App\Domain\Settings\ApplicationSettings;
use App\Reports\AttendanceReportFilters;
use App\Reports\AttendanceReportQuery;
use App\Support\SpreadsheetTextSanitizer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly AttendanceReportFilters $filters) {}

    public function query(): Builder
    {
        return app(AttendanceReportQuery::class)->build($this->filters);
    }

    public function headings(): array
    {
        return ['Work date', 'Employee number', 'Employee', 'Department', 'Clock in', 'Clock out', 'Hours', 'Status'];
    }

    public function map($record): array
    {
        $timezone = app(ApplicationSettings::class)->timezone();
        $clockIn = $record->clock_in_at?->setTimezone($timezone);
        $clockOut = $record->clock_out_at?->setTimezone($timezone);
        $hours = ($record->clock_in_at && $record->clock_out_at)
            ? round(CarbonImmutable::instance($record->clock_in_at)->diffInMinutes($record->clock_out_at) / 60, 2)
            : null;

        return [
            $record->work_date->toDateString(),
            SpreadsheetTextSanitizer::literal((string) $record->employeeProfile->employee_number),
            SpreadsheetTextSanitizer::literal((string) $record->employeeProfile->user->name),
            SpreadsheetTextSanitizer::literal((string) ($record->employeeProfile->department?->name ?? '')),
            $clockIn?->format('Y-m-d H:i:s'),
            $clockOut?->format('Y-m-d H:i:s'),
            $hours,
            SpreadsheetTextSanitizer::literal((string) $record->status->value),
        ];
    }
}
