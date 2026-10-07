<?php

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

/**
 * A Training Plan as a Plan file carries it: no post IDs and no uids, with
 * Exercises named by library key. Valid by construction: only PlanFile and
 * PlanFileExporter build one.
 */
final readonly class PlanContent
{
    /**
     * @param list<PhaseRow> $phases
     * @param list<WeekRow>  $weeks
     */
    public function __construct(
        public string $title,
        public string $summary,
        public string $goal,
        public string $targetAudience,
        public string $difficulty,
        public array $phases,
        public array $weeks,
    ) {
    }

    /**
     * The `plan` object of a Plan file, every property in the format's order.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title'           => $this->title,
            'summary'         => $this->summary,
            'goal'            => $this->goal,
            'target_audience' => $this->targetAudience,
            'difficulty'      => $this->difficulty,
            'phases'          => array_map(static fn (PhaseRow $phase): array => $phase->toArray(), $this->phases),
            'weeks'           => array_map(static fn (WeekRow $week): array => $week->toArray(), $this->weeks),
        ];
    }

    /**
     * @return list<string> Every library key used, once each, in order of first use.
     */
    public function exerciseKeys(): array
    {
        $keys = [];

        foreach ($this->weeks as $week) {
            foreach ($week->workouts as $workout) {
                foreach ($workout->prescriptions as $prescription) {
                    $keys[$prescription->exercise] = true;
                }
            }
        }

        // A key of digits only becomes an integer array key.
        return array_map('strval', array_keys($keys));
    }

    public function workoutCount(): int
    {
        $count = 0;

        foreach ($this->weeks as $week) {
            $count += count($week->workouts);
        }

        return $count;
    }

    public function prescriptionCount(): int
    {
        $count = 0;

        foreach ($this->weeks as $week) {
            foreach ($week->workouts as $workout) {
                $count += count($workout->prescriptions);
            }
        }

        return $count;
    }

    /**
     * Prescription counts by Week, then Workout, for PlanFields::estimateInputs().
     *
     * @return list<list<int>>
     */
    public function prescriptionsPerWorkoutPerWeek(): array
    {
        return array_map(
            static fn (WeekRow $week): array => array_map(static fn (WorkoutRow $workout): int => count($workout->prescriptions), $week->workouts),
            $this->weeks
        );
    }
}
