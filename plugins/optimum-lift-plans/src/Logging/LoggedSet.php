<?php

declare(strict_types=1);

namespace OptimumLift\Plans\Logging;

use OptimumLift\Plans\Format;

final readonly class LoggedSet
{
    /**
     * @param float|null $loadKg Null for bodyweight, or when no load was entered.
     */
    public function __construct(
        public int $workoutLogId,
        public string $prescriptionUid,
        public int $exerciseId,
        public int $setNumber,
        public ?float $loadKg,
        public ?int $reps,
        public ?int $seconds,
        public string $performedAt,
    ) {
    }

    /**
     * Whether the set was actually done: at least one rep or second. A set of
     * zero reps is a failed attempt, and counts towards neither a Personal
     * Record nor a finished Workout.
     */
    public function isPerformed(): bool
    {
        return ($this->reps ?? 0) > 0 || ($this->seconds ?? 0) > 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['workout_log_id'],
            (string) $row['prescription_uid'],
            (int) $row['exercise_id'],
            (int) $row['set_number'],
            isset($row['load_kg']) ? (float) $row['load_kg'] : null,
            isset($row['reps']) ? (int) $row['reps'] : null,
            isset($row['seconds']) ? (int) $row['seconds'] : null,
            (string) $row['performed_at'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'set_number'   => $this->setNumber,
            'load_kg'      => $this->loadKg,
            'reps'         => $this->reps,
            'seconds'      => $this->seconds,
            'performed_at' => Format::utc($this->performedAt),
        ];
    }
}
