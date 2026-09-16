<?php
/**
 * Plugin Name: Optimum Lift - Dynamic Host
 * Description: Local/dev only. Serves home + siteurl from the current request host so the
 *              site works over a Cloudflare tunnel as well as http://localhost:8080.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Current request origin, or null when there is no HTTP request (WP-CLI, cron).
 */
function ol_dynamic_host_origin(): ?string
{
    if (empty($_SERVER['HTTP_HOST'])) {
        return null;
    }

    $host = wp_unslash($_SERVER['HTTP_HOST']);

    // Only reflect hosts we expect in dev; anything else falls back to the stored option.
    $allowed = '/^(localhost(:\d+)?|127\.0\.0\.1(:\d+)?|[a-z0-9-]+\.trycloudflare\.com)$/i';
    if (!preg_match($allowed, $host)) {
        return null;
    }

    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || str_contains($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '', 'https');

    return ($is_https ? 'https://' : 'http://') . $host;
}

function ol_dynamic_host_filter(mixed $value): mixed
{
    $origin = ol_dynamic_host_origin();

    return $origin === null ? $value : $origin;
}

add_filter('option_home', 'ol_dynamic_host_filter');
add_filter('option_siteurl', 'ol_dynamic_host_filter');
