<?php

declare(strict_types=1);

namespace OptimumLift\Plans\Logging;

final readonly class WorkoutLog
{
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED   = 'completed';

    /**
     * @param int    $weekNumber  Snapshot at start, for display only.
     * @param string $workoutName Snapshot at start, for display only.
     */
    public function __construct(
        public int $id,
        public int $userId,
        public int $planId,
        public string $workoutUid,
        public int $weekNumber,
        public string $workoutName,
        public string $status,
        public string $notes,
        public string $startedAt,
        public ?string $completedAt,
    ) {
    }

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['plan_id'],
            (string) $row['workout_uid'],
            (int) $row['week_number'],
            (string) $row['workout_name'],
            (string) $row['status'],
            (string) ($row['notes'] ?? ''),
            (string) $row['started_at'],
            isset($row['completed_at']) ? (string) $row['completed_at'] : null,
        );
    }
}
