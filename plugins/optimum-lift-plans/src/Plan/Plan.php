<?php

declare(strict_types=1);

namespace OptimumLift\Plans\Plan;

final readonly class Plan
{
    /**
     * @param list<Phase> $phases
     * @param list<Week>  $weeks
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $status,
        public string $summary,
        public string $goal,
        public string $targetAudience,
        public string $difficulty,
        public int $coverId,
        public string $modifiedGmt,
        public array $phases,
        public array $weeks,
    ) {
    }

    public function week(int $number): ?Week
    {
        return $this->weeks[$number - 1] ?? null;
    }

    public function workout(string $uid): ?Workout
    {
        foreach ($this->weeks as $week) {
            foreach ($week->workouts as $workout) {
                if ($workout->uid === $uid) {
                    return $workout;
                }
            }
        }

        return null;
    }

    public function workoutCount(): int
    {
        return array_sum(array_map(static fn (Week $week): int => count($week->workouts), $this->weeks));
    }

    /**
     * Every Exercise the Plan prescribes, once each, in order of first use.
     *
     * @return array<int, Exercise>
     */
    public function exercises(): array
    {
        $exercises = [];

        foreach ($this->weeks as $week) {
            foreach ($week->workouts as $workout) {
                foreach ($workout->prescriptions as $prescription) {
                    $exercises[$prescription->exercise->id] ??= $prescription->exercise;
                }
            }
        }

        return $exercises;
    }
}
