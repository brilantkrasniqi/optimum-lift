<?php

/**
 * Plugin Name:       Optimum Lift Plans
 * Description:       Exercises, Training Plans, Access, Downloads and the Portal. Plan content lives here, never in the theme (ADR-0003).
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.3
 * Requires Plugins:  woocommerce
 * Text Domain:       optimum-lift-plans
 */

declare(strict_types=1);

namespace OptimumLift\Plans;

if (!defined('ABSPATH')) {
    exit;
}

const VERSION = '0.1.0';
const FILE    = __FILE__;
const DIR     = __DIR__;

// Our own classes load without Composer, so the plugin boots (and can explain
// itself) even before `composer install`. vendor/ is only needed for Dompdf.
spl_autoload_register(static function (string $class): void {
    $prefix = __NAMESPACE__ . '\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = DIR . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

if (is_file(DIR . '/vendor/autoload.php')) {
    require DIR . '/vendor/autoload.php';
}

add_action('plugins_loaded', [Plugin::class, 'boot']);
