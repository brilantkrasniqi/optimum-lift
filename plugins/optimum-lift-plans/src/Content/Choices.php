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
            'barbell'            => __('Barbell', 'optimum-lift-plans'),
            'trap_bar'           => __('Trap bar', 'optimum-lift-plans'),
            'dumbbell'           => __('Dumbbell', 'optimum-lift-plans'),
            'kettlebell'         => __('Kettlebell', 'optimum-lift-plans'),
            'machine'            => __('Machine', 'optimum-lift-plans'),
            'smith_machine'      => __('Smith machine', 'optimum-lift-plans'),
            'cable'              => __('Cable', 'optimum-lift-plans'),
            'band'               => __('Resistance band', 'optimum-lift-plans'),
            'bench'              => __('Bench', 'optimum-lift-plans'),
            'pullup_bar'         => __('Pull-up bar', 'optimum-lift-plans'),
            'dip_bars'           => __('Dip bars', 'optimum-lift-plans'),
            'rings'              => __('Rings', 'optimum-lift-plans'),
            'suspension_trainer' => __('Suspension trainer', 'optimum-lift-plans'),
            'medicine_ball'      => __('Medicine ball', 'optimum-lift-plans'),
            'exercise_ball'      => __('Exercise ball', 'optimum-lift-plans'),
            'ab_wheel'           => __('Ab wheel', 'optimum-lift-plans'),
            'rope'               => __('Rope', 'optimum-lift-plans'),
            'cardio_machine'     => __('Cardio machine', 'optimum-lift-plans'),
            'bodyweight'         => __('Bodyweight', 'optimum-lift-plans'),
        ];
    }

    /**
     * What an Exercise trains, by movement rather than by muscle. Internal: it
     * helps build balanced Plans and is never shown to a Customer. Grouped
     * upper body, lower body, core, then whole-body work.
     *
     * @return array<string, string>
     */
    public static function patterns(): array
    {
        return [
            'horizontal_push'     => __('Horizontal push', 'optimum-lift-plans'),
            'vertical_push'       => __('Vertical push', 'optimum-lift-plans'),
            'chest_fly'           => __('Chest fly', 'optimum-lift-plans'),
            'elbow_extension'     => __('Elbow extension', 'optimum-lift-plans'),
            'horizontal_pull'     => __('Horizontal pull', 'optimum-lift-plans'),
            'vertical_pull'       => __('Vertical pull', 'optimum-lift-plans'),
            'back_extension'      => __('Back extension', 'optimum-lift-plans'),
            'scapular'            => __('Scapular', 'optimum-lift-plans'),
            'shoulder_extension'  => __('Shoulder extension', 'optimum-lift-plans'),
            'rear_delt'           => __('Rear delt', 'optimum-lift-plans'),
            'shoulder_abduction'  => __('Shoulder abduction', 'optimum-lift-plans'),
            'external_rotation'   => __('External rotation', 'optimum-lift-plans'),
            'elbow_flexion'       => __('Elbow flexion', 'optimum-lift-plans'),
            'wrist_forearm'       => __('Wrist and forearm', 'optimum-lift-plans'),
            'squat'               => __('Squat', 'optimum-lift-plans'),
            'lunge'               => __('Lunge', 'optimum-lift-plans'),
            'hinge'               => __('Hinge', 'optimum-lift-plans'),
            'hip_extension'       => __('Hip extension', 'optimum-lift-plans'),
            'hip_abduction'       => __('Hip abduction', 'optimum-lift-plans'),
            'knee_extension'      => __('Knee extension', 'optimum-lift-plans'),
            'knee_flexion'        => __('Knee flexion', 'optimum-lift-plans'),
            'calf_raise'          => __('Calf raise', 'optimum-lift-plans'),
            'core_flexion'        => __('Core flexion', 'optimum-lift-plans'),
            'core_anti_extension' => __('Core anti-extension', 'optimum-lift-plans'),
            'core_lateral'        => __('Core lateral', 'optimum-lift-plans'),
            'core_rotation'       => __('Core rotation', 'optimum-lift-plans'),
            'anti_rotation'       => __('Anti-rotation', 'optimum-lift-plans'),
            'carry'               => __('Carry', 'optimum-lift-plans'),
            'squat_to_press'      => __('Squat to press', 'optimum-lift-plans'),
            'olympic_lift'        => __('Olympic lift', 'optimum-lift-plans'),
            'ballistic'           => __('Ballistic', 'optimum-lift-plans'),
            'plyometric'          => __('Plyometric', 'optimum-lift-plans'),
            'conditioning'        => __('Conditioning', 'optimum-lift-plans'),
            'mobility'            => __('Mobility', 'optimum-lift-plans'),
            'get_up'              => __('Get-up', 'optimum-lift-plans'),
        ];
    }

    /**
     * Where a Customer trains. An Exercise lists every Setting it works in.
     *
     * @return array<string, string>
     */
    public static function settings(): array
    {
        return [
            'home'     => __('Home', 'optimum-lift-plans'),
            'gym'      => __('Gym', 'optimum-lift-plans'),
            'crossfit' => __('CrossFit', 'optimum-lift-plans'),
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
