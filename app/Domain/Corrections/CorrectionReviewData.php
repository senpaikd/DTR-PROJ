<?php

namespace App\Domain\Corrections;

final readonly class CorrectionReviewData
{
    public function __construct(
        public ?string $clockInAt,
        public ?string $clockOutAt,
        public ?string $explanation,
    ) {}
}
