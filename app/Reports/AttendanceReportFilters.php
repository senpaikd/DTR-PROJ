<?php

namespace App\Reports;

use App\Enums\AttendanceStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class AttendanceReportFilters
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public ?int $employeeId = null,
        public ?int $departmentId = null,
        public ?string $status = null,
    ) {}

    public static function fromArray(array $input): self
    {
        $data = Validator::make($input, [
            'period' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'custom'])],
            'anchor' => ['nullable', 'date'],
            'from' => ['nullable', 'required_if:period,custom', 'date'],
            'to' => ['nullable', 'required_if:period,custom', 'date'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::enum(AttendanceStatus::class)],
        ])->validate();

        $period = $data['period'] ?? 'monthly';
        $anchor = CarbonImmutable::parse($data['anchor'] ?? now()->toDateString());
        [$from, $to] = match ($period) {
            'daily' => [$anchor->startOfDay(), $anchor->startOfDay()],
            'weekly' => [$anchor->startOfWeek(), $anchor->endOfWeek()],
            'monthly' => [$anchor->startOfMonth(), $anchor->endOfMonth()],
            default => [CarbonImmutable::parse($data['from'] ?? ''), CarbonImmutable::parse($data['to'] ?? '')],
        };

        if ($to->lt($from)) {
            throw ValidationException::withMessages(['to' => 'The end date must be on or after the start date.']);
        }
        if ($from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['to' => 'Reports are limited to a 366-day range.']);
        }

        return new self(
            $from->startOfDay(),
            $to->startOfDay(),
            isset($data['employee_id']) ? (int) $data['employee_id'] : null,
            isset($data['department_id']) ? (int) $data['department_id'] : null,
            $data['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'period' => 'custom',
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'employee_id' => $this->employeeId,
            'department_id' => $this->departmentId,
            'status' => $this->status,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
