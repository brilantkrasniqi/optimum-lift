<?php

/**
 * Where something is in a Plan, in the words an author uses: "Week 3,
 * Workout 2 ("Upper body"), Prescription 4". Numbers are 1-based positions.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

final class Places
{
    public static function file(): string
    {
        return __('File', 'optimum-lift-plans');
    }

    public static function plan(): string
    {
        return __('Plan', 'optimum-lift-plans');
    }

    public static function phase(int $phase): string
    {
        /* translators: %d: Phase number */
        return sprintf(__('Phase %d', 'optimum-lift-plans'), $phase);
    }

    public static function week(int $week): string
    {
        /* translators: %d: Week number */
        return sprintf(__('Week %d', 'optimum-lift-plans'), $week);
    }

    public static function workout(int $week, int $workout, string $name): string
    {
        if ($name === '') {
            /* translators: 1: Week number, 2: Workout number */
            return sprintf(__('Week %1$d, Workout %2$d', 'optimum-lift-plans'), $week, $workout);
        }

        /* translators: 1: Week number, 2: Workout number, 3: Workout name */
        return sprintf(__('Week %1$d, Workout %2$d ("%3$s")', 'optimum-lift-plans'), $week, $workout, $name);
    }

    public static function prescription(int $week, int $workout, string $name, int $prescription): string
    {
        /* translators: 1: a Workout's place, e.g. Week 3, Workout 2 ("Upper body"), 2: Prescription number */
        return sprintf(__('%1$s, Prescription %2$d', 'optimum-lift-plans'), self::workout($week, $workout, $name), $prescription);
    }

    /**
     * "Week 1, Workout 2, Prescription 4; Week 2, Workout 2, Prescription 4"
     *
     * @param list<string> $places
     */
    public static function list(array $places): string
    {
        return implode('; ', $places);
    }

    public static function at(string $place, string $message): string
    {
        return $place . ': ' . $message;
    }
}
