<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceReportExport;
use App\Http\Controllers\Controller;
use App\Reports\AttendanceReportFilters;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceReportExportController extends Controller
{
    public function __invoke(Request $request, string $format): BinaryFileResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);
        $filters = AttendanceReportFilters::fromArray($request->query());
        $writer = $format === 'csv' ? Excel::CSV : Excel::XLSX;

        return ExcelFacade::download(
            new AttendanceReportExport($filters),
            'attendance-'.$filters->from->format('Ymd').'-'.$filters->to->format('Ymd').'.'.$format,
            $writer,
        );
    }
}
