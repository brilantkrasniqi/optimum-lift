<?php
/**
 * Optimum Lift theme bootstrap.
 *
 * This file stays a table of contents. Behaviour lives in inc/.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('OPTIMUM_LIFT_VERSION', wp_get_theme()->get('Version'));
define('OPTIMUM_LIFT_DIR', get_template_directory());
define('OPTIMUM_LIFT_URI', get_template_directory_uri());

require_once OPTIMUM_LIFT_DIR . '/inc/setup.php';
require_once OPTIMUM_LIFT_DIR . '/inc/assets.php';
require_once OPTIMUM_LIFT_DIR . '/inc/template-tags.php';

if (class_exists('WooCommerce')) {
    require_once OPTIMUM_LIFT_DIR . '/inc/woocommerce.php';
}
