<?php

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

/**
 * A Week is numbered by its position in the Plan, so it carries only Workouts.
 */
final readonly class WeekRow
{
    /**
     * @param list<WorkoutRow> $workouts
     */
    public function __construct(public array $workouts)
    {
    }

    /**
     * @return array{workouts: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'workouts' => array_map(static fn (WorkoutRow $workout): array => $workout->toArray(), $this->workouts),
        ];
    }
}
