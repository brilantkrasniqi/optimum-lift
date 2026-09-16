<?php

declare(strict_types=1);

namespace OptimumLift\Plans\Plan;

final readonly class Phase
{
    public function __construct(
        public string $name,
        public int $firstWeek,
        public int $lastWeek,
    ) {
    }

    public function contains(int $week): bool
    {
        return $week >= $this->firstWeek && $week <= $this->lastWeek;
    }
}
