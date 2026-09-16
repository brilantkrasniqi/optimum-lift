<?php

declare(strict_types=1);

namespace OptimumLift\Plans\Plan;

final readonly class Workout
{
    /**
     * @param int                $number 1-based position within its Week.
     * @param list<Prescription> $prescriptions
     */
    public function __construct(
        public string $uid,
        public string $name,
        public int $number,
        public int $weekNumber,
        public array $prescriptions,
    ) {
    }

    public function prescription(string $uid): ?Prescription
    {
        foreach ($this->prescriptions as $prescription) {
            if ($prescription->uid === $uid) {
                return $prescription;
            }
        }

        return null;
    }
}
