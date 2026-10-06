<?php

/**
 * The Exercise library's fields. The Exercise name is the post title.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Content;

final class ExerciseFields
{
    public const PRIMARY_MUSCLE    = 'field_ol_exercise_primary_muscle';
    public const SECONDARY_MUSCLES = 'field_ol_exercise_secondary_muscles';
    public const DIFFICULTY        = 'field_ol_exercise_difficulty';
    public const MOVEMENT_PATTERN  = 'field_ol_exercise_movement_pattern';
    public const SETTINGS          = 'field_ol_exercise_settings';
    public const EQUIPMENT         = 'field_ol_exercise_equipment';
    public const IMAGE             = 'field_ol_exercise_image';
    public const ANIMATION         = 'field_ol_exercise_animation';
    public const VIDEO_URL         = 'field_ol_exercise_video_url';
    public const INSTRUCTIONS      = 'field_ol_exercise_instructions';

    public function register(): void
    {
        add_action('acf/include_fields', [$this, 'registerFields']);
        add_filter('manage_' . PostTypes::EXERCISE . '_posts_columns', [$this, 'columns']);
        add_action('manage_' . PostTypes::EXERCISE . '_posts_custom_column', [$this, 'renderColumn'], 10, 2);
    }

    public function registerFields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_ol_exercise',
            'title'    => __('Exercise', 'optimum-lift-plans'),
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => PostTypes::EXERCISE]]],
            'position' => 'acf_after_title',
            'style'    => 'seamless',
            'fields'   => [
                [
                    'key'           => self::PRIMARY_MUSCLE,
                    'name'          => 'primary_muscle',
                    'label'         => __('Primary muscle', 'optimum-lift-plans'),
                    'type'          => 'select',
                    'choices'       => Choices::muscles(),
                    'required'      => 1,
                    'return_format' => 'value',
                    'wrapper'       => ['width' => '33'],
                ],
                [
                    'key'           => self::SECONDARY_MUSCLES,
                    'name'          => 'secondary_muscles',
                    'label'         => __('Secondary muscles', 'optimum-lift-plans'),
                    'type'          => 'select',
                    'choices'       => Choices::muscles(),
                    'multiple'      => 1,
                    'ui'            => 1,
                    'allow_null'    => 1,
                    'return_format' => 'value',
                    'wrapper'       => ['width' => '33'],
                ],
                [
                    'key'           => self::DIFFICULTY,
                    'name'          => 'difficulty',
                    'label'         => __('Difficulty', 'optimum-lift-plans'),
                    'type'          => 'select',
                    'choices'       => Choices::difficulty(),
                    'allow_null'    => 1,
                    'return_format' => 'value',
                    'wrapper'       => ['width' => '34'],
                ],
                [
                    'key'           => self::MOVEMENT_PATTERN,
                    'name'          => 'movement_pattern',
                    'label'         => __('Movement pattern', 'optimum-lift-plans'),
                    'instructions'  => __('Not shown to Customers. Helps balance a Plan, for example pushing against pulling.', 'optimum-lift-plans'),
                    'type'          => 'select',
                    'choices'       => Choices::patterns(),
                    'ui'            => 1,
                    'allow_null'    => 1,
                    'return_format' => 'value',
                    'wrapper'       => ['width' => '33'],
                ],
                [
                    'key'           => self::SETTINGS,
                    'name'          => 'settings',
                    'label'         => __('Settings', 'optimum-lift-plans'),
                    'instructions'  => __('Where this Exercise can be done.', 'optimum-lift-plans'),
                    'type'          => 'checkbox',
                    'choices'       => Choices::settings(),
                    'layout'        => 'horizontal',
                    'return_format' => 'value',
                    'wrapper'       => ['width' => '33'],
                ],
                [
                    'key'          => LibraryKeys::FIELD,
                    'name'         => LibraryKeys::META,
                    'label'        => __('Library key', 'optimum-lift-plans'),
                    'instructions' => __('Names this Exercise in Plan files. Set when first saved, never changes.', 'optimum-lift-plans'),
                    'type'         => 'text',
                    'readonly'     => 1,
                    'placeholder'  => __('Created when you save', 'optimum-lift-plans'),
                    'wrapper'      => ['width' => '34'],
                ],
                [
                    'key'           => self::EQUIPMENT,
                    'name'          => 'equipment',
                    'label'         => __('Equipment', 'optimum-lift-plans'),
                    'type'          => 'checkbox',
                    'choices'       => Choices::equipment(),
                    'layout'        => 'horizontal',
                    'return_format' => 'value',
                ],
                [
                    'key'           => self::IMAGE,
                    'name'          => 'image',
                    'label'         => __('Image', 'optimum-lift-plans'),
                    'instructions'  => __('A still photo. Used in the PDF Download, and in the Portal when there is no animation.', 'optimum-lift-plans'),
                    'type'          => 'image',
                    'return_format' => 'id',
                    'preview_size'  => 'medium',
                    'mime_types'    => 'jpg,jpeg,png',
                    'wrapper'       => ['width' => '50'],
                ],
                [
                    'key'           => self::ANIMATION,
                    'name'          => 'animation',
                    'label'         => __('Animation', 'optimum-lift-plans'),
                    'instructions'  => __('A GIF for the Portal. Never used in the PDF.', 'optimum-lift-plans'),
                    'type'          => 'image',
                    'return_format' => 'id',
                    'preview_size'  => 'medium',
                    'mime_types'    => 'gif,webp',
                    'wrapper'       => ['width' => '50'],
                ],
                [
                    'key'          => self::VIDEO_URL,
                    'name'         => 'video_url',
                    'label'        => __('Video', 'optimum-lift-plans'),
                    'instructions' => __('Unlisted YouTube or Vimeo link.', 'optimum-lift-plans'),
                    'type'         => 'url',
                ],
                [
                    'key'          => self::INSTRUCTIONS,
                    'name'         => 'instructions',
                    'label'        => __('Instructions', 'optimum-lift-plans'),
                    'type'         => 'wysiwyg',
                    'toolbar'      => 'basic',
                    'media_upload' => 0,
                ],
            ],
        ]);
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function columns(array $columns): array
    {
        $date = $columns['date'] ?? null;
        unset($columns['date']);

        $columns['ol_primary_muscle'] = __('Primary muscle', 'optimum-lift-plans');
        $columns['ol_equipment']      = __('Equipment', 'optimum-lift-plans');

        if ($date !== null) {
            $columns['date'] = $date;
        }

        return $columns;
    }

    public function renderColumn(string $column, int $postId): void
    {
        match ($column) {
            'ol_primary_muscle' => print(esc_html(Choices::labels(Choices::muscles(), (string) get_field('primary_muscle', $postId)))),
            'ol_equipment'      => print(esc_html(Choices::labels(Choices::equipment(), (array) get_field('equipment', $postId)))),
            default             => null,
        };
    }
}
