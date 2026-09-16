<?php

/**
 * Exercises and Training Plans. Neither is public: Plans are paid content, seen
 * only through the Portal and the Download, and Exercises only through Plans.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Content;

final class PostTypes
{
    public const EXERCISE = 'ol_exercise';
    public const PLAN     = 'ol_training_plan';
    public const MENU     = 'ol-training';

    public function register(): void
    {
        add_action('init', [$this, 'registerPostTypes']);
        // The parent menu must exist before WordPress attaches the post types'
        // submenus at priority 10.
        add_action('admin_menu', [$this, 'registerMenu'], 9);
        // Drop the auto-added "Training" submenu, so the top-level item links
        // straight to the first real screen.
        add_action('admin_menu', static function (): void {
            remove_submenu_page(self::MENU, self::MENU);
        }, 99);
    }

    public function registerPostTypes(): void
    {
        $shared = [
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => self::MENU,
            'show_in_rest'        => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'map_meta_cap'        => true,
            'capability_type'     => 'post',
        ];

        register_post_type(self::EXERCISE, $shared + [
            'labels'   => [
                'name'          => __('Exercises', 'optimum-lift-plans'),
                'singular_name' => __('Exercise', 'optimum-lift-plans'),
                'add_new_item'  => __('Add Exercise', 'optimum-lift-plans'),
                'edit_item'     => __('Edit Exercise', 'optimum-lift-plans'),
                'search_items'  => __('Search Exercises', 'optimum-lift-plans'),
                'not_found'     => __('No Exercises found.', 'optimum-lift-plans'),
                'all_items'     => __('Exercises', 'optimum-lift-plans'),
            ],
            'supports' => ['title', 'thumbnail'],
        ]);

        register_post_type(self::PLAN, $shared + [
            'labels'   => [
                'name'          => __('Training Plans', 'optimum-lift-plans'),
                'singular_name' => __('Training Plan', 'optimum-lift-plans'),
                'add_new_item'  => __('Add Training Plan', 'optimum-lift-plans'),
                'edit_item'     => __('Edit Training Plan', 'optimum-lift-plans'),
                'search_items'  => __('Search Training Plans', 'optimum-lift-plans'),
                'not_found'     => __('No Training Plans found.', 'optimum-lift-plans'),
                'all_items'     => __('Training Plans', 'optimum-lift-plans'),
            ],
            // No revisions: ACF copies every field into each revision, which
            // for a 12-week Plan is thousands of meta rows per save.
            'supports' => ['title', 'thumbnail'],
        ]);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('Training', 'optimum-lift-plans'),
            __('Training', 'optimum-lift-plans'),
            'edit_posts',
            self::MENU,
            '__return_null',
            'dashicons-performance',
            26
        );
    }
}
