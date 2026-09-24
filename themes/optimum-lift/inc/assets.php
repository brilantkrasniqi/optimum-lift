<?php
/**
 * Stylesheet, script and font loading.
 *
 * CSS and JS are authored in assets/src/ and built into assets/dist/ by
 * `npm run dev` (watch) or `npm run build` (minified). assets/dist/ is not
 * committed: run a build after cloning or the site renders unstyled.
 *
 * Fonts are self-hosted and declared in theme.json, which WordPress prints as
 * @font-face rules; only the preload hints live here.
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

// Before any stylesheet, so .reveal content is hidden before first paint and
// never flashes in and out.
add_action('wp_head', static function (): void {
    wp_print_inline_script_tag("document.documentElement.classList.add('js');");
}, 0);

// Both families are used above the fold: fetch them with the stylesheet
// instead of after layout. The URLs match what theme.json's fontFace prints.
add_action('wp_head', static function (): void {
    foreach (['anton-latin-400-normal.woff2', 'inter-latin-wght-normal.woff2'] as $font) {
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
            esc_url(get_theme_file_uri('assets/fonts/' . $font))
        );
    }
}, 1);

// Every phone and browser the store targets draws emoji natively. WordPress's
// fallback adds a detection script and swaps emoji for images from s.w.org, a
// third-party request. The styles are switched off by unhooking the legacy
// print_emoji_styles, which wp_enqueue_emoji_styles() checks for; unhooking
// wp_enqueue_emoji_styles instead leaves the deprecated function to run.
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

add_action('wp_enqueue_scripts', static function (): void {
    // style.css only carries the theme header, which WordPress reads from the
    // file; loading it would be a render-blocking request for no rules.
    wp_enqueue_style(
        'optimum-lift-main',
        OPTIMUM_LIFT_URI . '/assets/dist/main.css',
        [],
        optimum_lift_asset_version('assets/dist/main.css')
    );

    wp_enqueue_script(
        'optimum-lift-main',
        OPTIMUM_LIFT_URI . '/assets/dist/main.js',
        [],
        optimum_lift_asset_version('assets/dist/main.js'),
        ['strategy' => 'defer', 'in_footer' => true]
    );

    wp_add_inline_script(
        'optimum-lift-main',
        'window.optimumLift = ' . wp_json_encode(optimum_lift_script_data(), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) . ';',
        'before'
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

    // Product reviews are comments too, but they are never threaded replies.
    if (is_singular() && !is_singular('product') && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
});

/**
 * What main.js needs from the server. No user-facing strings: templates print
 * those into data-* attributes.
 *
 * @return array{wcAjaxUrl: string, checkoutUrl: string, cartUrl: string, currency: string}
 */
function optimum_lift_script_data(): array
{
    if (!class_exists('WooCommerce')) {
        return ['wcAjaxUrl' => '', 'checkoutUrl' => '', 'cartUrl' => '', 'currency' => ''];
    }

    return [
        'wcAjaxUrl'   => WC_AJAX::get_endpoint('%%endpoint%%'),
        'checkoutUrl' => wc_get_checkout_url(),
        'cartUrl'     => wc_get_cart_url(),
        'currency'    => get_woocommerce_currency(),
    ];
}

/*
 * WooCommerce's front-end CSS and jQuery scripts (ADR-0007). Its stylesheets
 * are unlayered, so they would beat every Tailwind utility; the theme styles
 * WooCommerce markup itself. The cart drawer and add-to-cart are the theme's
 * own vanilla JS.
 */
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

$optimum_lift_dequeue_wc_styles = static function (): void {
    foreach (['wc-blocks-style', 'woocommerce-inline', 'select2', 'brands-styles'] as $style) {
        wp_dequeue_style($style);
    }
};
add_action('wp_enqueue_scripts', $optimum_lift_dequeue_wc_styles, 100);
// Blocks enqueue their styles while rendering, after wp_enqueue_scripts.
add_action('wp_footer', $optimum_lift_dequeue_wc_styles, 0);

add_action('wp_enqueue_scripts', static function (): void {
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Without selectWoo, WooCommerce's country field stays a native <select>,
    // which is faster to use on a phone.
    foreach (['wc-add-to-cart', 'wc-cart-fragments', 'selectWoo'] as $script) {
        wp_dequeue_script($script);
    }

    // woocommerce.js (and the jQuery it pulls in) only serves WooCommerce's
    // forms: keep it where those forms are.
    if (!is_cart() && !is_checkout() && !is_account_page()) {
        wp_dequeue_script('woocommerce');
    }

    // single-product.js drives WooCommerce's gallery, tabs and review-star
    // widget, none of which the theme's Product page prints, and it loads
    // jQuery in <head> on the page paid traffic lands on. Without it the
    // review form keeps its native rating <select>.
    if (is_product()) {
        wp_dequeue_script('wc-single-product');
    }
}, 100);
