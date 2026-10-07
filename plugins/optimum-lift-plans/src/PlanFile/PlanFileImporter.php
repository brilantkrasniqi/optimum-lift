<?php

/**
 * Imports a Plan file as a new Training Plan. It never updates an existing
 * Plan: replacing a live Plan's rows would give them new uids and orphan the
 * Workout Logs that point at the old ones (ADR-0003).
 *
 * Writes the way SeedCommand does, with update_field() on field keys;
 * PlanFields gives every Workout and Prescription its uid. Then reads the new
 * Plan back and compares it with the file: any difference deletes it, so a
 * silent ACF write problem shows up now and not in a Customer's Portal.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

use OptimumLift\Plans\Content\LibraryKeys;
use OptimumLift\Plans\Content\PlanFields;
use OptimumLift\Plans\Content\PostTypes;
use WP_Error;

final class PlanFileImporter
{
    public const STATUSES = ['draft', 'publish'];

    public function __construct(
        private readonly LibraryKeys $keys,
        private readonly PlanFileExporter $exporter,
    ) {
    }

    public function run(PlanFile $file, bool $dryRun, string $status = 'draft'): PlanFileReport
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown Plan status "%s".', $status));
        }

        $report = new PlanFileReport($dryRun, $status);
        $file   = $file->resolveExercises($this->keys);

        if ($file->content === null) {
            $report->problems = $file->problems;

            return $report;
        }

        $content        = $file->content;
        $report->counts = [
            'weeks'         => count($content->weeks),
            'workouts'      => $content->workoutCount(),
            'prescriptions' => $content->prescriptionCount(),
            'exercises'     => count($content->exerciseKeys()),
        ];
        $report->notes  = $this->notes($content);

        if ($dryRun) {
            return $report;
        }

        $planId = $this->insert($content->title, $status);

        if ($planId instanceof WP_Error) {
            /* translators: %s: error message */
            $report->problems[] = sprintf(__('The Plan could not be created: %s', 'optimum-lift-plans'), $planId->get_error_message());

            return $report;
        }

        try {
            $this->writeFields($planId, $content, $file->exerciseIds);
            $problem = $this->readBack($planId, $content);
        } catch (\Throwable $e) {
            /* translators: %s: error message */
            $problem = sprintf(__('The import failed while writing the Plan: %s', 'optimum-lift-plans'), $e->getMessage());
        }

        if ($problem !== null) {
            wp_delete_post($planId, true);
            $report->problems[] = $problem . ' ' . __('The new Plan was deleted; nothing was imported.', 'optimum-lift-plans');

            return $report;
        }

        $report->planId  = $planId;
        $report->editUrl = admin_url(sprintf('post.php?post=%d&action=edit', $planId));

        return $report;
    }

    /**
     * Things worth knowing that do not stop an import.
     *
     * @return list<string>
     */
    private function notes(PlanContent $content): array
    {
        global $wpdb;

        $notes    = [];
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_title = %s AND post_status NOT IN ('trash', 'auto-draft') ORDER BY ID LIMIT 1",
            PostTypes::PLAN,
            $content->title
        ));

        if ($existing !== null) {
            /* translators: %d: post ID */
            $notes[] = sprintf(__('A Training Plan with this title already exists (ID %d). Importing creates another one; it does not replace it.', 'optimum-lift-plans'), (int) $existing);
        }

        $limit  = (int) ini_get('max_input_vars');
        $inputs = PlanFields::estimateInputs(count($content->phases), $content->prescriptionsPerWorkoutPerWeek());

        if ($limit > 0 && $inputs > $limit) {
            $notes[] = sprintf(
                /* translators: 1: estimated form inputs, 2: max_input_vars */
                __('This Plan\'s edit form sends about %1$d inputs and this site accepts %2$d (max_input_vars). It imports fine, but it cannot be saved from wp-admin until the limit is raised.', 'optimum-lift-plans'),
                $inputs,
                $limit
            );
        }

        return $notes;
    }

    private function insert(string $title, string $status): int|WP_Error
    {
        // Without a user (WP-CLI), kses filters the title and turns "&" into
        // "&amp;". The title is plain text already: PlanFile sanitised it.
        $kses = has_filter('title_save_pre', 'wp_filter_kses') !== false;

        if ($kses) {
            kses_remove_filters();
        }

        try {
            // wp_insert_post() unslashes what it is given; addslashes() is
            // wp_slash() for a string.
            return wp_insert_post([
                'post_type'   => PostTypes::PLAN,
                'post_status' => $status,
                'post_title'  => addslashes($title),
            ], true);
        } finally {
            if ($kses) {
                kses_init_filters();
            }
        }
    }

    /**
     * Field keys, not names: on a new post ACF cannot find a field by name.
     * update_field() unslashes through update_metadata(), so values go in
     * slashed. No uids: PlanFields assigns them as the Weeks are written.
     *
     * A new Plan has no meta yet, so each value is added without the lookup
     * update_metadata() makes first. ACF clears the meta cache with every
     * row, so that lookup reloaded all of the Plan's meta each time: a
     * 16-Week Plan took 16 seconds. A key written twice, or one the Plan
     * already has, goes the normal way. The read-back checks the result.
     *
     * @param array<string, int> $exerciseIds
     */
    private function writeFields(int $planId, PlanContent $content, array $exerciseIds): void
    {
        $written = array_fill_keys(array_keys((array) get_post_meta($planId)), true);
        $add     = static function (
            mixed $check,
            mixed $objectId,
            mixed $metaKey,
            mixed $metaValue,
            mixed $previous
        ) use (
            $planId,
            &$written
        ): mixed {
            $ours = $check === null && (int) $objectId === $planId && is_string($metaKey);

            if (!$ours || isset($written[$metaKey]) || !in_array($previous, ['', null], true)) {
                return $check;
            }

            $written[$metaKey] = true;

            // update_metadata() has unslashed the value already; add_metadata() unslashes again.
            $id = add_metadata('post', $planId, $metaKey, wp_slash($metaValue));

            return $id === false ? null : $id;
        };

        add_filter('update_post_metadata', $add, 10, 5);

        try {
            $this->writeValues($planId, $content, $exerciseIds);
        } finally {
            remove_filter('update_post_metadata', $add, 10);
        }
    }

    /**
     * @param array<string, int> $exerciseIds
     */
    private function writeValues(int $planId, PlanContent $content, array $exerciseIds): void
    {
        update_field(PlanFields::SUMMARY, wp_slash($content->summary), $planId);
        update_field(PlanFields::GOAL, wp_slash($content->goal), $planId);
        update_field(PlanFields::TARGET_AUDIENCE, wp_slash($content->targetAudience), $planId);
        update_field(PlanFields::DIFFICULTY, $content->difficulty, $planId);

        update_field(PlanFields::PHASES, wp_slash(array_map(
            static fn (PhaseRow $phase): array => [
                PlanFields::PHASE_NAME  => $phase->name,
                PlanFields::PHASE_FIRST => $phase->firstWeek,
                PlanFields::PHASE_LAST  => $phase->lastWeek,
            ],
            $content->phases
        )), $planId);

        $weeks = [];

        foreach ($content->weeks as $week) {
            $workouts = [];

            foreach ($week->workouts as $workout) {
                $prescriptions = [];

                foreach ($workout->prescriptions as $row) {
                    $prescriptions[] = [
                        PlanFields::PRESCRIPTION_EXERCISE    => $exerciseIds[$row->exercise],
                        PlanFields::PRESCRIPTION_SETS        => $row->sets,
                        PlanFields::PRESCRIPTION_TARGET_TYPE => $row->targetType,
                        PlanFields::PRESCRIPTION_TARGET      => $row->target,
                        PlanFields::PRESCRIPTION_INTENSITY   => $row->intensity,
                        // What the edit form saves for an empty number field.
                        PlanFields::PRESCRIPTION_REST        => $row->restSeconds ?? '',
                        PlanFields::PRESCRIPTION_NOTES       => $row->notes,
                    ];
                }

                $workouts[] = [
                    PlanFields::WORKOUT_NAME          => $workout->name,
                    PlanFields::WORKOUT_PRESCRIPTIONS => $prescriptions,
                ];
            }

            $weeks[] = [PlanFields::WEEK_WORKOUTS => $workouts];
        }

        update_field(PlanFields::WEEKS, wp_slash($weeks), $planId);
    }

    /**
     * The problem when the stored Plan differs from the file, else null.
     */
    private function readBack(int $planId, PlanContent $content): ?string
    {
        $stored = $this->exporter->read($planId);

        if ($stored->content === null) {
            /* translators: %s: the problems found, joined */
            return sprintf(__('The Plan was written but could not be read back: %s', 'optimum-lift-plans'), implode(' ', $stored->problems));
        }

        $difference = $this->firstDifference(['plan' => $content->toArray()], ['plan' => $stored->content->toArray()], '');

        if ($difference === null) {
            return null;
        }

        /* translators: %s: where the first difference is, e.g. plan.weeks[2].workouts[0].name */
        return sprintf(__('The Plan was written but did not read back as the file says; the first difference is at %s.', 'optimum-lift-plans'), $difference);
    }

    private function firstDifference(mixed $expected, mixed $actual, string $path): ?string
    {
        if (!is_array($expected) || !is_array($actual)) {
            return $expected === $actual ? null : $path;
        }

        foreach (array_unique([...array_keys($expected), ...array_keys($actual)]) as $key) {
            $at = is_int($key) ? sprintf('%s[%d]', $path, $key) : ltrim($path . '.' . $key, '.');

            if (!array_key_exists($key, $expected) || !array_key_exists($key, $actual)) {
                return $at;
            }

            $difference = $this->firstDifference($expected[$key], $actual[$key], $at);

            if ($difference !== null) {
                return $difference;
            }
        }

        return null;
    }
}
