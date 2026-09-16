<?php

/**
 * The Exercise library's fields. The Exercise name is the post title.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Content;

final class ExerciseFields
{
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
                    'key'           => 'field_ol_exercise_primary_muscle',
                    'name'          => 'primary_muscle',
                    'label'         => __('Primary muscle', 'optimum-lift-plans'),
                    'type'          => 'select',
                    'choices'       => Choices::muscles(),
                    'required'      => 1,
                    'return_format' => 'value',
                    'wrapper'       => ['width' => '33'],
                ],
                [
                    'key'           => 'field_ol_exercise_secondary_muscles',
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
                    'key'           => 'field_ol_exercise_difficulty',
                    'name'          => 'difficulty',
                    'label'         => __('Difficulty', 'optimum-lift-plans'),
                    'type'          => 'select',
                    'choices'       => Choices::difficulty(),
                    'allow_null'    => 1,
                    'return_format' => 'value',
                    'wrapper'       => ['width' => '34'],
                ],
                [
                    'key'           => 'field_ol_exercise_equipment',
                    'name'          => 'equipment',
                    'label'         => __('Equipment', 'optimum-lift-plans'),
                    'type'          => 'checkbox',
                    'choices'       => Choices::equipment(),
                    'layout'        => 'horizontal',
                    'return_format' => 'value',
                ],
                [
                    'key'           => 'field_ol_exercise_image',
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
                    'key'           => 'field_ol_exercise_animation',
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
                    'key'          => 'field_ol_exercise_video_url',
                    'name'         => 'video_url',
                    'label'        => __('Video', 'optimum-lift-plans'),
                    'instructions' => __('Unlisted YouTube or Vimeo link.', 'optimum-lift-plans'),
                    'type'         => 'url',
                ],
                [
                    'key'          => 'field_ol_exercise_instructions',
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
