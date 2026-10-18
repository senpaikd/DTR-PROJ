<?php

namespace App\Domain\Attendance;

use App\Domain\Settings\ApplicationSettings;
use App\Models\EmployeeProfile;
use App\Models\EmployeeScheduleAssignment;
use App\Models\Holiday;
use Carbon\CarbonImmutable;

class ScheduleResolver
{
    public function __construct(private readonly ApplicationSettings $settings) {}

    public function forEmployeeAt(EmployeeProfile $employee, CarbonImmutable $instant): ?ResolvedShift
    {
        $timezone = $this->settings->timezone()->getName();
        $localInstant = $instant->setTimezone($timezone);
        $currentDate = $localInstant->startOfDay();
        $candidates = collect([$currentDate, $currentDate->subDay()])
            ->map(fn (CarbonImmutable $date) => $this->forWorkDate($employee, $date, $timezone))
            ->filter();

        return $candidates->first(fn (ResolvedShift $shift) => $shift->contains($instant))
            ?? $candidates->first(fn (ResolvedShift $shift) => $shift->workDate->isSameDay($currentDate));
    }

    public function forWorkDate(EmployeeProfile $employee, CarbonImmutable $localDate, ?string $timezone = null): ?ResolvedShift
    {
        $timezone ??= $this->settings->timezone()->getName();
        $date = $localDate->setTimezone($timezone)->startOfDay();

        if (Holiday::query()->whereDate('date', $date->toDateString())->where('is_active', true)->exists()) {
            return null;
        }

        $assignment = EmployeeScheduleAssignment::query()
            ->with('workSchedule.days')
            ->where('employee_profile_id', $employee->id)
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date->toDateString()))
            ->latest('effective_from')
            ->first();
        $day = $assignment?->workSchedule?->days->firstWhere('weekday', $date->dayOfWeek);

        if (! $day?->is_workday || ! $day->starts_at || ! $day->ends_at) {
            return null;
        }

        $start = CarbonImmutable::parse($date->toDateString().' '.$day->starts_at, $timezone);
        $end = CarbonImmutable::parse($date->toDateString().' '.$day->ends_at, $timezone);
        if ($day->is_overnight || $end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return new ResolvedShift($date, $start->utc(), $end->utc(), $timezone);
    }
}
