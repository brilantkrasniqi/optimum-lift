<?php

/**
 * The closed vocabularies used by Exercise and Plan fields. Adding a muscle or a
 * piece of equipment is a one-line change here; stored values are the keys, so
 * never rename a key that is already in use.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Content;

final class Choices
{
    /**
     * @return array<string, string>
     */
    public static function muscles(): array
    {
        return [
            'chest'      => __('Chest', 'optimum-lift-plans'),
            'back'       => __('Back', 'optimum-lift-plans'),
            'shoulders'  => __('Shoulders', 'optimum-lift-plans'),
            'biceps'     => __('Biceps', 'optimum-lift-plans'),
            'triceps'    => __('Triceps', 'optimum-lift-plans'),
            'forearms'   => __('Forearms', 'optimum-lift-plans'),
            'core'       => __('Core', 'optimum-lift-plans'),
            'glutes'     => __('Glutes', 'optimum-lift-plans'),
            'quads'      => __('Quads', 'optimum-lift-plans'),
            'hamstrings' => __('Hamstrings', 'optimum-lift-plans'),
            'calves'     => __('Calves', 'optimum-lift-plans'),
            'full_body'  => __('Full body', 'optimum-lift-plans'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function equipment(): array
    {
        return [
            'barbell'    => __('Barbell', 'optimum-lift-plans'),
            'dumbbell'   => __('Dumbbell', 'optimum-lift-plans'),
            'kettlebell' => __('Kettlebell', 'optimum-lift-plans'),
            'machine'    => __('Machine', 'optimum-lift-plans'),
            'cable'      => __('Cable', 'optimum-lift-plans'),
            'band'       => __('Resistance band', 'optimum-lift-plans'),
            'bench'      => __('Bench', 'optimum-lift-plans'),
            'pullup_bar' => __('Pull-up bar', 'optimum-lift-plans'),
            'bodyweight' => __('Bodyweight', 'optimum-lift-plans'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function difficulty(): array
    {
        return [
            'beginner'     => __('Beginner', 'optimum-lift-plans'),
            'intermediate' => __('Intermediate', 'optimum-lift-plans'),
            'advanced'     => __('Advanced', 'optimum-lift-plans'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function targetTypes(): array
    {
        return [
            'reps'    => __('Reps', 'optimum-lift-plans'),
            'seconds' => __('Seconds', 'optimum-lift-plans'),
        ];
    }

    /**
     * @param array<string, string> $choices
     * @param list<string>|string   $keys
     */
    public static function labels(array $choices, array|string $keys): string
    {
        $labels = array_map(
            static fn (string $key): string => $choices[$key] ?? $key,
            array_filter((array) $keys, 'is_string')
        );

        return implode(', ', $labels);
    }
}
