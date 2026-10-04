<?php

/**
 * How much of a Workout one Workout Log covers, counted against the Workout as
 * the Plan prescribes it now. A Workout cannot be finished with nothing
 * performed, and finishing with much of it left needs the Customer to confirm.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Logging;

use OptimumLift\Plans\Plan\Workout;

final readonly class WorkoutProgress
{
    /**
     * Below this share of the prescribed sets, finishing asks first.
     */
    private const CONFIRM_BELOW = 0.75;

    /**
     * @param int $performedSets           Prescribed sets with at least one rep or second logged.
     * @param int $prescribedSets          Every set the Workout prescribes.
     * @param int $prescriptionsNotStarted Prescriptions without a single performed set.
     */
    public function __construct(
        public int $performedSets,
        public int $prescribedSets,
        public int $prescriptionsNotStarted,
    ) {
    }

    /**
     * @param array<string, array<int, LoggedSet>> $sets The log's sets, by prescription uid and set number.
     */
    public static function of(Workout $workout, array $sets): self
    {
        $performed  = 0;
        $prescribed = 0;
        $notStarted = 0;

        foreach ($workout->prescriptions as $prescription) {
            // Sets beyond the Prescription's count were logged before the Plan
            // was edited down; they are no longer part of this Workout.
            $done = count(array_filter(
                $sets[$prescription->uid] ?? [],
                static fn (LoggedSet $set): bool => $set->setNumber <= $prescription->sets && $set->isPerformed()
            ));

            $performed  += $done;
            $prescribed += $prescription->sets;
            $notStarted += $done === 0 ? 1 : 0;
        }

        return new self($performed, $prescribed, $notStarted);
    }

    public function isEmpty(): bool
    {
        return $this->performedSets === 0;
    }

    /**
     * Much of the Workout is left: a whole Prescription skipped, or under three
     * quarters of the sets performed. Missing the last set or two is not.
     */
    public function hasMuchLeft(): bool
    {
        return $this->prescriptionsNotStarted > 0 || $this->performedSets < self::CONFIRM_BELOW * $this->prescribedSets;
    }

    public function setsLeft(): int
    {
        return max(0, $this->prescribedSets - $this->performedSets);
    }

    /**
     * @return array{performed_sets: int, prescribed_sets: int, prescriptions_not_started: int}
     */
    public function toArray(): array
    {
        return [
            'performed_sets'            => $this->performedSets,
            'prescribed_sets'           => $this->prescribedSets,
            'prescriptions_not_started' => $this->prescriptionsNotStarted,
        ];
    }
}
