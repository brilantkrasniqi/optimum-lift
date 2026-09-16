<?php

/**
 * The only place that reads Plan and Exercise fields. The Portal, the Download
 * and the REST API work with the value objects this returns, so a change to
 * the ACF structure is absorbed here.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Plan;

use OptimumLift\Plans\Content\PostTypes;
use WP_Post;

final class PlanRepository
{
    /** @var array<int, Plan|null> */
    private array $plans = [];

    public function find(int $planId): ?Plan
    {
        if (!array_key_exists($planId, $this->plans)) {
            $this->plans[$planId] = $this->load($planId);
        }

        return $this->plans[$planId];
    }

    private function load(int $planId): ?Plan
    {
        $post = get_post($planId);

        if (!$post instanceof WP_Post || $post->post_type !== PostTypes::PLAN || $post->post_status === 'trash') {
            return null;
        }

        $weekRows  = $this->rows(get_field('weeks', $planId));
        $exercises = $this->loadExercises($this->exerciseIds($weekRows));

        $phases = [];

        foreach ($this->rows(get_field('phases', $planId)) as $row) {
            $phases[] = new Phase(
                $this->string($row['name'] ?? ''),
                (int) ($row['first_week'] ?? 0),
                (int) ($row['last_week'] ?? 0),
            );
        }

        $weeks = [];

        foreach ($weekRows as $index => $weekRow) {
            $weekNumber = $index + 1;
            $workouts   = [];

            foreach ($this->rows($weekRow['workouts'] ?? null) as $workoutRow) {
                $uid = $this->string($workoutRow['uid'] ?? '');

                if ($uid === '') {
                    continue;
                }

                $workouts[] = new Workout(
                    $uid,
                    $this->string($workoutRow['name'] ?? ''),
                    count($workouts) + 1,
                    $weekNumber,
                    $this->prescriptions($workoutRow['prescriptions'] ?? null, $exercises),
                );
            }

            $phase = null;

            foreach ($phases as $candidate) {
                if ($candidate->contains($weekNumber)) {
                    $phase = $candidate;
                    break;
                }
            }

            $weeks[] = new Week($weekNumber, $workouts, $phase);
        }

        return new Plan(
            $planId,
            get_the_title($post),
            $post->post_status,
            $this->string(get_field('summary', $planId)),
            $this->string(get_field('goal', $planId)),
            $this->string(get_field('target_audience', $planId)),
            $this->string(get_field('difficulty', $planId)),
            (int) get_post_thumbnail_id($post),
            $post->post_modified_gmt,
            $phases,
            $weeks,
        );
    }

    /**
     * @param array<int, Exercise> $exercises
     * @return list<Prescription>
     */
    private function prescriptions(mixed $rows, array $exercises): array
    {
        $prescriptions = [];

        foreach ($this->rows($rows) as $row) {
            $exercise = $exercises[(int) ($row['exercise'] ?? 0)] ?? null;
            $uid      = $this->string($row['uid'] ?? '');

            // An unpublished or deleted Exercise drops out of the Plan. Logged
            // Sets that point at it are kept.
            if ($exercise === null || $uid === '') {
                continue;
            }

            $rest = $row['rest_seconds'] ?? null;

            $prescriptions[] = new Prescription(
                $uid,
                $exercise,
                max(1, (int) ($row['sets'] ?? 1)),
                ($row['target_type'] ?? '') === 'seconds' ? 'seconds' : 'reps',
                $this->string($row['target'] ?? ''),
                $this->string($row['intensity'] ?? ''),
                is_numeric($rest) ? (int) $rest : null,
                $this->string($row['notes'] ?? ''),
            );
        }

        return $prescriptions;
    }

    /**
     * @param list<array<string, mixed>> $weekRows
     * @return list<int>
     */
    private function exerciseIds(array $weekRows): array
    {
        $ids = [];

        foreach ($weekRows as $week) {
            foreach ($this->rows($week['workouts'] ?? null) as $workout) {
                foreach ($this->rows($workout['prescriptions'] ?? null) as $row) {
                    $ids[] = (int) ($row['exercise'] ?? 0);
                }
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * One query for every Exercise, with meta primed, instead of one per
     * Prescription.
     *
     * @param list<int> $ids
     * @return array<int, Exercise>
     */
    private function loadExercises(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $posts = get_posts([
            'post_type'        => PostTypes::EXERCISE,
            'post_status'      => 'publish',
            'post__in'         => $ids,
            'posts_per_page'   => -1,
            'orderby'          => 'post__in',
            'suppress_filters' => true,
        ]);

        $exercises = [];

        foreach ($posts as $post) {
            $exercises[$post->ID] = new Exercise(
                $post->ID,
                get_the_title($post),
                $this->string(get_field('primary_muscle', $post->ID)),
                $this->strings(get_field('secondary_muscles', $post->ID)),
                $this->strings(get_field('equipment', $post->ID)),
                $this->string(get_field('difficulty', $post->ID)),
                (int) get_field('image', $post->ID),
                (int) get_field('animation', $post->ID),
                $this->string(get_field('video_url', $post->ID)),
                $this->string(get_field('instructions', $post->ID)),
                $post->post_modified_gmt,
            );
        }

        return $exercises;
    }

    /**
     * ACF returns false for an empty repeater.
     *
     * @return list<array<string, mixed>>
     */
    private function rows(mixed $rows): array
    {
        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    private function string(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $values): array
    {
        return is_array($values) ? array_values(array_map('strval', array_filter($values, 'is_scalar'))) : [];
    }
}
