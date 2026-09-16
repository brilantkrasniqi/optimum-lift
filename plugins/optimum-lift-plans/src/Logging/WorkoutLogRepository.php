<?php

/**
 * Workout Logs and Logged Sets (ADR-0004). Nothing here checks Access; callers
 * do that first.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Logging;

use OptimumLift\Plans\Plan\Plan;
use OptimumLift\Plans\Plan\Prescription;
use OptimumLift\Plans\Plan\Workout;
use OptimumLift\Plans\Schema;

final class WorkoutLogRepository
{
    public function find(int $logId): ?WorkoutLog
    {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . Schema::workoutLogs() . ' WHERE id = %d', $logId), ARRAY_A);

        return is_array($row) ? WorkoutLog::fromRow($row) : null;
    }

    /**
     * The log the Workout screen shows: the one in progress, else the latest.
     */
    public function current(int $userId, string $workoutUid): ?WorkoutLog
    {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . Schema::workoutLogs() . "
             WHERE user_id = %d AND workout_uid = %s
             ORDER BY status = 'in_progress' DESC, started_at DESC, id DESC
             LIMIT 1",
            $userId,
            $workoutUid
        ), ARRAY_A);

        return is_array($row) ? WorkoutLog::fromRow($row) : null;
    }

    /**
     * Resume the Workout's in-progress log, or start a new one.
     */
    public function start(int $userId, Plan $plan, Workout $workout): WorkoutLog
    {
        $current = $this->current($userId, $workout->uid);

        if ($current !== null && !$current->isCompleted()) {
            return $current;
        }

        global $wpdb;

        $now = current_time('mysql', true);

        $wpdb->insert(Schema::workoutLogs(), [
            'user_id'      => $userId,
            'plan_id'      => $plan->id,
            'workout_uid'  => $workout->uid,
            'week_number'  => $workout->weekNumber,
            'workout_name' => $workout->name,
            'status'       => WorkoutLog::IN_PROGRESS,
            'started_at'   => $now,
            'updated_at'   => $now,
        ], ['%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s']);

        return $this->find((int) $wpdb->insert_id) ?? throw new \RuntimeException('Workout Log was not created.');
    }

    public function complete(WorkoutLog $log, string $notes): void
    {
        global $wpdb;

        $now = current_time('mysql', true);

        $wpdb->update(
            Schema::workoutLogs(),
            [
                'status'       => WorkoutLog::COMPLETED,
                'notes'        => $notes,
                'completed_at' => $log->completedAt ?? $now,
                'updated_at'   => $now,
            ],
            ['id' => $log->id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
    }

    /**
     * Status of each Workout in a Plan: completed if any log of it was
     * completed, otherwise in progress if one was started.
     *
     * @return array<string, string> workout uid => status
     */
    public function statuses(int $userId, int $planId): array
    {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT workout_uid, MAX(status = %s) AS completed FROM ' . Schema::workoutLogs() . '
             WHERE user_id = %d AND plan_id = %d GROUP BY workout_uid',
            WorkoutLog::COMPLETED,
            $userId,
            $planId
        ), ARRAY_A) ?: [];

        $statuses = [];

        foreach ($rows as $row) {
            $statuses[(string) $row['workout_uid']] = $row['completed'] ? WorkoutLog::COMPLETED : WorkoutLog::IN_PROGRESS;
        }

        return $statuses;
    }

    /**
     * Upsert one Logged Set.
     *
     * @return bool Whether it set a new Personal Record.
     */
    public function saveSet(WorkoutLog $log, Prescription $prescription, int $setNumber, ?float $loadKg, ?int $reps, ?int $seconds): bool
    {
        global $wpdb;

        $now = current_time('mysql', true);

        // Placeholders cannot express NULL, so NULL-able columns are inlined
        // from values that are already typed as int or float.
        $wpdb->query($wpdb->prepare(
            'INSERT INTO ' . Schema::loggedSets() . '
                (workout_log_id, user_id, exercise_id, prescription_uid, set_number, load_kg, reps, seconds, performed_at)
             VALUES (%d, %d, %d, %s, %d, ' . $this->sqlNumber($loadKg) . ', ' . $this->sqlNumber($reps) . ', ' . $this->sqlNumber($seconds) . ', %s)
             ON DUPLICATE KEY UPDATE load_kg = VALUES(load_kg), reps = VALUES(reps), seconds = VALUES(seconds), performed_at = VALUES(performed_at)',
            $log->id,
            $log->userId,
            $prescription->exercise->id,
            $prescription->uid,
            $setNumber,
            $now
        ));

        $wpdb->update(Schema::workoutLogs(), ['updated_at' => $now], ['id' => $log->id], ['%s'], ['%d']);

        if ($loadKg === null || !($reps > 0 || $seconds > 0)) {
            return false;
        }

        $previous = $this->personalRecord($log->userId, $prescription->exercise->id, [$log->id, $prescription->uid, $setNumber]);

        return $previous !== null && $loadKg > $previous;
    }

    public function deleteSet(WorkoutLog $log, string $prescriptionUid, int $setNumber): void
    {
        global $wpdb;

        $wpdb->delete(
            Schema::loggedSets(),
            ['workout_log_id' => $log->id, 'prescription_uid' => $prescriptionUid, 'set_number' => $setNumber],
            ['%d', '%s', '%d']
        );
    }

    /**
     * @return array<string, array<int, LoggedSet>> prescription uid => set number => set
     */
    public function sets(int $logId): array
    {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM ' . Schema::loggedSets() . ' WHERE workout_log_id = %d ORDER BY set_number',
            $logId
        ), ARRAY_A) ?: [];

        $sets = [];

        foreach ($rows as $row) {
            $set = LoggedSet::fromRow($row);
            $sets[$set->prescriptionUid][$set->setNumber] = $set;
        }

        return $sets;
    }

    /**
     * The heaviest load logged for the Exercise, counting only sets with at
     * least one rep or second.
     *
     * @param array{int, string, int}|null $excluding [log ID, prescription uid, set number]
     */
    public function personalRecord(int $userId, int $exerciseId, ?array $excluding = null): ?float
    {
        global $wpdb;

        $sql  = 'SELECT MAX(load_kg) FROM ' . Schema::loggedSets() . '
                 WHERE user_id = %d AND exercise_id = %d AND load_kg IS NOT NULL AND (reps > 0 OR seconds > 0)';
        $args = [$userId, $exerciseId];

        if ($excluding !== null) {
            $sql   .= ' AND NOT (workout_log_id = %d AND prescription_uid = %s AND set_number = %d)';
            $args[] = $excluding[0];
            $args[] = $excluding[1];
            $args[] = $excluding[2];
        }

        $max = $wpdb->get_var($wpdb->prepare($sql, ...$args));

        return $max === null ? null : (float) $max;
    }

    /**
     * The Customer's most recent logs of an Exercise, newest first, each with
     * its sets.
     *
     * @return list<array{log_id: int, performed_at: string, sets: list<LoggedSet>}>
     */
    public function history(int $userId, int $exerciseId, int $logs = 10, int $excludingLogId = 0): array
    {
        global $wpdb;

        $logIds = $wpdb->get_col($wpdb->prepare(
            'SELECT workout_log_id FROM ' . Schema::loggedSets() . '
             WHERE user_id = %d AND exercise_id = %d AND workout_log_id <> %d
             GROUP BY workout_log_id ORDER BY MAX(performed_at) DESC LIMIT %d',
            $userId,
            $exerciseId,
            $excludingLogId,
            $logs
        ));

        if ($logIds === []) {
            return [];
        }

        $in   = implode(',', array_map('intval', $logIds));
        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM ' . Schema::loggedSets() . "
             WHERE exercise_id = %d AND workout_log_id IN ($in)
             ORDER BY set_number",
            $exerciseId
        ), ARRAY_A) ?: [];

        $history = [];

        foreach ($logIds as $logId) {
            $history[(int) $logId] = ['log_id' => (int) $logId, 'performed_at' => '', 'sets' => []];
        }

        foreach ($rows as $row) {
            $set = LoggedSet::fromRow($row);
            $history[$set->workoutLogId]['sets'][] = $set;
            $history[$set->workoutLogId]['performed_at'] = max($history[$set->workoutLogId]['performed_at'], $set->performedAt);
        }

        return array_values($history);
    }

    /**
     * @return list<WorkoutLog>
     */
    public function logsForUser(int $userId, int $limit, int $offset): array
    {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM ' . Schema::workoutLogs() . ' WHERE user_id = %d ORDER BY id LIMIT %d OFFSET %d',
            $userId,
            $limit,
            $offset
        ), ARRAY_A) ?: [];

        return array_map([WorkoutLog::class, 'fromRow'], $rows);
    }

    /**
     * @return int Workout Logs deleted.
     */
    public function deleteForUser(int $userId): int
    {
        global $wpdb;

        $wpdb->delete(Schema::loggedSets(), ['user_id' => $userId], ['%d']);

        return (int) $wpdb->delete(Schema::workoutLogs(), ['user_id' => $userId], ['%d']);
    }

    private function sqlNumber(int|float|null $value): string
    {
        return $value === null ? 'NULL' : (string) $value;
    }
}
