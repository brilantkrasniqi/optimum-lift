<?php

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

final readonly class PhaseRow
{
    public function __construct(
        public string $name,
        public int $firstWeek,
        public int $lastWeek,
    ) {
    }

    /**
     * @return array{name: string, first_week: int, last_week: int}
     */
    public function toArray(): array
    {
        return [
            'name'       => $this->name,
            'first_week' => $this->firstWeek,
            'last_week'  => $this->lastWeek,
        ];
    }
}
