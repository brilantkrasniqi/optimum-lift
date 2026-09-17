<?php
/**
 * Optimum Lift theme bootstrap.
 *
 * This file stays a table of contents. Behaviour lives in inc/ (site-wide) and
 * inc/shop/ (the storefront). Each module is loaded only if its file exists, so
 * a module that is missing or not written yet never takes the site down.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('OPTIMUM_LIFT_VERSION', wp_get_theme()->get('Version'));
define('OPTIMUM_LIFT_DIR', get_template_directory());
define('OPTIMUM_LIFT_URI', get_template_directory_uri());

(static function (): void {
    $modules = [
        'inc/setup.php',
        'inc/assets.php',
        'inc/customizer.php',
        'inc/icons.php',
        'inc/template-tags.php',
    ];

    // The storefront modules call WooCommerce on load or in their hooks.
    if (class_exists('WooCommerce')) {
        array_push(
            $modules,
            'inc/woocommerce.php',
            'inc/shop/fields.php',
            'inc/shop/product-data.php',
            'inc/shop/pricing.php',
            'inc/shop/proof.php',
            'inc/shop/offer.php',
            'inc/shop/blocks.php',
            'inc/shop/coupon.php',
            'inc/shop/archive.php',
            'inc/shop/cart.php',
            'inc/shop/bundle.php',
            'inc/shop/buy-now.php',
            'inc/shop/upsell.php',
            'inc/shop/checkout.php',
        );
    }

    if (defined('WP_CLI') && WP_CLI) {
        $modules[] = 'inc/cli.php';
    }

    foreach ($modules as $module) {
        $path = OPTIMUM_LIFT_DIR . '/' . $module;

        if (is_file($path)) {
            require_once $path;
        }
    }
})();
