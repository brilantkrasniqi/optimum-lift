<?php
/**
 * Stylesheet and script registration.
 *
 * CSS and JS are authored in assets/src/ and built into assets/dist/ by
 * `npm run dev` (watch) or `npm run build` (minified). assets/dist/ is not
 * committed: run a build after cloning or the site renders unstyled.
 */

declare(strict_types=1);

/**
 * Cache-bust from file mtime in local development, from the theme version in
 * production. Editing a stylesheet should never require a hard refresh here.
 */
function optimum_lift_asset_version(string $relative_path): string
{
    $absolute = OPTIMUM_LIFT_DIR . '/' . ltrim($relative_path, '/');

    if (wp_get_environment_type() === 'local' && file_exists($absolute)) {
        return (string) filemtime($absolute);
    }

    return OPTIMUM_LIFT_VERSION;
}

add_action('wp_enqueue_scripts', static function (): void {
    // style.css carries the theme header, so it must stay the registered
    // stylesheet even though the real rules live in assets/dist/.
    wp_enqueue_style(
        'optimum-lift',
        get_stylesheet_uri(),
        [],
        optimum_lift_asset_version('style.css')
    );

    wp_enqueue_style(
        'optimum-lift-main',
        OPTIMUM_LIFT_URI . '/assets/dist/main.css',
        ['optimum-lift'],
        optimum_lift_asset_version('assets/dist/main.css')
    );

    wp_enqueue_script(
        'optimum-lift-main',
        OPTIMUM_LIFT_URI . '/assets/dist/main.js',
        [],
        optimum_lift_asset_version('assets/dist/main.js'),
        ['strategy' => 'defer', 'in_footer' => true]
    );

    // Registered only. Templates that output a slider enqueue it themselves;
    // see assets/src/js/slider.js for the markup it expects.
    wp_register_script(
        'optimum-lift-slider',
        OPTIMUM_LIFT_URI . '/assets/dist/slider.js',
        [],
        optimum_lift_asset_version('assets/dist/slider.js'),
        ['strategy' => 'defer', 'in_footer' => true]
    );

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
});
