<?php

namespace App\Http\Controllers\Employee;

use App\Domain\Corrections\CorrectionRequestService;
use App\Domain\Settings\ApplicationSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreCorrectionRequest;
use App\Models\AttendanceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorrectionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employeeProfile;

        return view('employee.corrections.index', ['requests' => $employee->correctionRequests()->latest()->paginate(12)]);
    }

    public function create(Request $request): View
    {
        $employee = $request->user()->employeeProfile;
        $record = $request->integer('record') ? AttendanceRecord::query()->whereKey($request->integer('record'))->where('employee_profile_id', $employee->id)->firstOrFail() : null;

        return view('employee.corrections.create', compact('record'));
    }

    public function store(StoreCorrectionRequest $request, CorrectionRequestService $service, ApplicationSettings $settings): RedirectResponse
    {
        $data = $request->validated();
        foreach (['proposed_clock_in_at', 'proposed_clock_out_at'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = CarbonImmutable::parse($data[$field], $settings->timezone())->utc()->format('Y-m-d H:i:s');
            }
        }
        $service->submit($request->user(), $data);

        return redirect()->route('corrections.index')->with('status', 'Correction request submitted for administrator review.');
    }
}
