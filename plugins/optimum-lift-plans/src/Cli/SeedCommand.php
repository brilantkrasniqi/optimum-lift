<?php

/**
 * Local development data.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Cli;

use OptimumLift\Plans\Content\LibraryKeys;
use OptimumLift\Plans\Content\PostTypes;
use OptimumLift\Plans\Library\ExerciseImporter;
use OptimumLift\Plans\Library\ImportOptions;
use OptimumLift\Plans\Library\LibraryFile;
use WC_Product_Simple;
use WP_CLI;

final class SeedCommand
{
    /**
     * Library key => target type.
     */
    private const EXERCISES = [
        'barbell-back-squat'               => 'reps',
        'barbell-romanian-deadlift'        => 'reps',
        'barbell-bench-press'              => 'reps',
        'pull-up'                          => 'reps',
        'dumbbell-standing-overhead-press' => 'reps',
        'cable-seated-row'                 => 'reps',
        'walking-lunge'                    => 'reps',
        'incline-side-plank'               => 'seconds',
    ];

    private const WORKOUTS = [
        'Lower body' => ['barbell-back-squat', 'barbell-romanian-deadlift', 'walking-lunge', 'incline-side-plank'],
        'Upper body' => ['barbell-bench-press', 'pull-up', 'dumbbell-standing-overhead-press', 'cable-seated-row'],
        'Full body'  => ['barbell-back-squat', 'barbell-bench-press', 'cable-seated-row', 'incline-side-plank'],
    ];

    public function __construct(private readonly ExerciseImporter $importer)
    {
    }

    /**
     * Imports the Exercise library, then creates a demo Training Plan from it
     * and a virtual Product that includes the Plan.
     *
     * ## OPTIONS
     *
     * [--weeks=<weeks>]
     * : Weeks in the demo Plan. Raise it to test the max_input_vars guard.
     * ---
     * default: 4
     * ---
     *
     * ## EXAMPLES
     *
     *     wp ol-plans seed
     *     wp ol-plans seed --weeks=16
     *
     * @param list<string>          $args
     * @param array<string, string> $assoc
     */
    public function seed(array $args, array $assoc): void
    {
        $weeks = max(1, (int) ($assoc['weeks'] ?? 4));
        $ids   = $this->libraryExercises();

        $planId = (int) wp_insert_post([
            'post_type'   => PostTypes::PLAN,
            'post_status' => 'publish',
            'post_title'  => sprintf('Demo: %d-week body recomposition', $weeks),
        ], true);

        update_field('field_ol_plan_summary', 'Three full-body sessions a week. Loads climb each Week; the last Week of each Phase is lighter.', $planId);
        update_field('field_ol_plan_goal', 'Body recomposition', $planId);
        update_field('field_ol_plan_target_audience', 'Beginners with gym access', $planId);
        update_field('field_ol_plan_difficulty', 'beginner', $planId);

        $half = intdiv($weeks, 2);

        update_field('field_ol_plan_phases', array_values(array_filter([
            $half > 0 ? ['field_ol_phase_name' => 'Foundation', 'field_ol_phase_first_week' => 1, 'field_ol_phase_last_week' => $half] : null,
            ['field_ol_phase_name' => 'Build', 'field_ol_phase_first_week' => $half + 1, 'field_ol_phase_last_week' => $weeks],
        ])), $planId);

        $weekRows = [];

        for ($week = 1; $week <= $weeks; $week++) {
            $workoutRows = [];

            foreach (self::WORKOUTS as $workoutName => $keys) {
                $prescriptionRows = [];

                foreach ($keys as $key) {
                    $timed              = self::EXERCISES[$key] === 'seconds';
                    $prescriptionRows[] = [
                        'field_ol_prescription_exercise'     => $ids[$key],
                        'field_ol_prescription_sets'         => $timed ? 3 : 3 + ($week % 2),
                        'field_ol_prescription_target_type'  => $timed ? 'seconds' : 'reps',
                        'field_ol_prescription_target'       => $timed ? (string) (30 + 5 * $week) : '8-10',
                        'field_ol_prescription_intensity'    => $timed ? '' : 'RPE ' . min(9, 6 + $week % 4),
                        'field_ol_prescription_rest_seconds' => $timed ? 60 : 120,
                        'field_ol_prescription_notes'        => $key === 'barbell-back-squat' ? 'Pause 2 seconds at the bottom.' : '',
                    ];
                }

                $workoutRows[] = [
                    'field_ol_workout_name'          => $workoutName,
                    'field_ol_workout_prescriptions' => $prescriptionRows,
                ];
            }

            $weekRows[] = ['field_ol_week_workouts' => $workoutRows];
        }

        update_field('field_ol_plan_weeks', $weekRows, $planId);

        $product = new WC_Product_Simple();
        $product->set_name(get_the_title($planId));
        $product->set_status('publish');
        $product->set_virtual(true);
        $product->set_regular_price('7.99');
        $productId = $product->save();

        update_field('field_ol_product_plans', [$planId], $productId);

        WP_CLI::success(sprintf('Plan %d (%d Weeks) and Product %d created.', $planId, $weeks, $productId));
    }

    /**
     * Imports the library (a no-op when it is already there) and returns the
     * demo's Exercises by key.
     *
     * @return array<string, int>
     */
    private function libraryExercises(): array
    {
        $file = LibraryFile::bundled();

        if ($file->problems !== []) {
            WP_CLI::error('The Exercise library file has problems; run `wp ol-plans import-exercises --dry-run` to see them.');
        }

        $report = $this->importer->run($file, new ImportOptions());

        if ($report->errors !== []) {
            WP_CLI::error(implode(' ', $report->errors));
        }

        WP_CLI::log(sprintf('Exercise library: %d created, %d already there.', $report->counts['created'], $report->counts['skipped'] + $report->counts['filled'] + $report->counts['adopted']));

        $keys = new LibraryKeys();
        $ids  = [];

        foreach (array_keys(self::EXERCISES) as $key) {
            $id = $keys->find($key);

            if ($id === null || get_post_status($id) !== 'publish') {
                WP_CLI::error(sprintf('The library Exercise "%s" is missing or not published.', $key));
            }

            $ids[$key] = $id;
        }

        return $ids;
    }
}
