<?php
/**
 * Stylesheet and script registration.
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
    // stylesheet even though the real rules live in assets/css/.
    wp_enqueue_style(
        'optimum-lift',
        get_stylesheet_uri(),
        [],
        optimum_lift_asset_version('style.css')
    );

    wp_enqueue_style(
        'optimum-lift-main',
        OPTIMUM_LIFT_URI . '/assets/css/main.css',
        ['optimum-lift'],
        optimum_lift_asset_version('assets/css/main.css')
    );

    wp_enqueue_script(
        'optimum-lift-main',
        OPTIMUM_LIFT_URI . '/assets/js/main.js',
        [],
        optimum_lift_asset_version('assets/js/main.js'),
        ['strategy' => 'defer', 'in_footer' => true]
    );

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
});
