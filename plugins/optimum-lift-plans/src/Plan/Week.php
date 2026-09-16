<?php

declare(strict_types=1);

namespace OptimumLift\Plans\Plan;

final readonly class Week
{
    /**
     * @param list<Workout> $workouts
     */
    public function __construct(
        public int $number,
        public array $workouts,
        public ?Phase $phase,
    ) {
    }
}
