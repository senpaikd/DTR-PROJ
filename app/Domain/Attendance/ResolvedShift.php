<?php

namespace App\Domain\Attendance;

use Carbon\CarbonImmutable;

final readonly class ResolvedShift
{
    public function __construct(
        public CarbonImmutable $workDate,
        public CarbonImmutable $startsAtUtc,
        public CarbonImmutable $endsAtUtc,
        public string $timezone,
    ) {}

    public function startsAtLocal(): CarbonImmutable
    {
        return $this->startsAtUtc->setTimezone($this->timezone);
    }

    public function endsAtLocal(): CarbonImmutable
    {
        return $this->endsAtUtc->setTimezone($this->timezone);
    }

    public function contains(CarbonImmutable $instant): bool
    {
        return $instant->betweenIncluded($this->startsAtUtc, $this->endsAtUtc);
    }
}
