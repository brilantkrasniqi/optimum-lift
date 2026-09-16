<?php

declare(strict_types=1);

namespace OptimumLift\Plans\Plan;

final readonly class Prescription
{
    /**
     * @param string $targetType "reps" or "seconds"
     * @param string $target     Free text: "8", "8-10", "AMRAP", "30".
     */
    public function __construct(
        public string $uid,
        public Exercise $exercise,
        public int $sets,
        public string $targetType,
        public string $target,
        public string $intensity,
        public ?int $restSeconds,
        public string $notes,
    ) {
    }

    public function isTimed(): bool
    {
        return $this->targetType === 'seconds';
    }
}
