<?php

/**
 * Local development data.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Cli;

use OptimumLift\Plans\Content\PostTypes;
use WC_Product_Simple;
use WP_CLI;
use WP_Post;

final class SeedCommand
{
    /**
     * [muscle, equipment, target type]
     */
    private const EXERCISES = [
        'Barbell back squat'      => ['quads', ['barbell'], 'reps'],
        'Romanian deadlift'       => ['hamstrings', ['barbell'], 'reps'],
        'Barbell bench press'     => ['chest', ['barbell', 'bench'], 'reps'],
        'Pull-up'                 => ['back', ['pullup_bar'], 'reps'],
        'Dumbbell shoulder press' => ['shoulders', ['dumbbell'], 'reps'],
        'Seated cable row'        => ['back', ['cable'], 'reps'],
        'Walking lunge'           => ['glutes', ['dumbbell'], 'reps'],
        'Plank'                   => ['core', ['bodyweight'], 'seconds'],
    ];

    private const WORKOUTS = [
        'Lower body' => ['Barbell back squat', 'Romanian deadlift', 'Walking lunge', 'Plank'],
        'Upper body' => ['Barbell bench press', 'Pull-up', 'Dumbbell shoulder press', 'Seated cable row'],
        'Full body'  => ['Barbell back squat', 'Barbell bench press', 'Seated cable row', 'Plank'],
    ];

    /**
     * Creates demo Exercises, a Training Plan and a virtual Product that includes it.
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
        $ids   = [];

        foreach (self::EXERCISES as $name => [$muscle, $equipment]) {
            $ids[$name] = $this->exercise($name, $muscle, $equipment);
        }

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

            foreach (self::WORKOUTS as $workoutName => $exerciseNames) {
                $prescriptionRows = [];

                foreach ($exerciseNames as $exerciseName) {
                    $timed              = self::EXERCISES[$exerciseName][2] === 'seconds';
                    $prescriptionRows[] = [
                        'field_ol_prescription_exercise'     => $ids[$exerciseName],
                        'field_ol_prescription_sets'         => $timed ? 3 : 3 + ($week % 2),
                        'field_ol_prescription_target_type'  => $timed ? 'seconds' : 'reps',
                        'field_ol_prescription_target'       => $timed ? (string) (30 + 5 * $week) : '8-10',
                        'field_ol_prescription_intensity'    => $timed ? '' : 'RPE ' . min(9, 6 + $week % 4),
                        'field_ol_prescription_rest_seconds' => $timed ? 60 : 120,
                        'field_ol_prescription_notes'        => $exerciseName === 'Barbell back squat' ? 'Pause 2 seconds at the bottom.' : '',
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
     * @param list<string> $equipment
     */
    private function exercise(string $name, string $muscle, array $equipment): int
    {
        $existing = get_posts([
            'post_type'      => PostTypes::EXERCISE,
            'title'          => $name,
            'post_status'    => 'any',
            'posts_per_page' => 1,
        ]);

        if ($existing !== [] && $existing[0] instanceof WP_Post) {
            return $existing[0]->ID;
        }

        $id = (int) wp_insert_post(['post_type' => PostTypes::EXERCISE, 'post_status' => 'publish', 'post_title' => $name], true);

        update_field('field_ol_exercise_primary_muscle', $muscle, $id);
        update_field('field_ol_exercise_equipment', $equipment, $id);
        update_field('field_ol_exercise_instructions', '<p>Brace, control the lowering, drive up hard. Stop the set when form breaks.</p>', $id);

        return $id;
    }
}
