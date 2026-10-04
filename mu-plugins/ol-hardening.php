<?php
/**
 * Plugin Name: Optimum Lift - Hardening
 * Description: Hides account names from the public site (REST users, author
 *              archives, the users sitemap) and turns off XML-RPC. The store
 *              has no blog and no author pages, so nothing public needs them.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * /wp/v2/users lists every author's login slug to anyone. Signed-in requests
 * keep it: the block editor reads /users/me.
 *
 * @param array<string, mixed> $endpoints
 * @return array<string, mixed>
 */
function ol_hardening_rest_endpoints(array $endpoints): array
{
    if (is_user_logged_in()) {
        return $endpoints;
    }

    unset($endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)']);

    return $endpoints;
}

add_filter('rest_endpoints', 'ol_hardening_rest_endpoints');

/**
 * `?author=1` redirects to /author/<login>/, which gives the login away; the
 * archive itself is a 404 here.
 */
function ol_hardening_author_archives(): void
{
    if (!is_author() && !isset($_GET['author'])) {
        return;
    }

    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
    // Otherwise redirect_canonical() guesses a URL for the 404 and redirects.
    remove_action('template_redirect', 'redirect_canonical');
}

// Before redirect_canonical (priority 10), which performs the ?author= redirect.
add_action('template_redirect', 'ol_hardening_author_archives', 1);

function ol_hardening_sitemap_provider(mixed $provider, string $name): mixed
{
    return $name === 'users' ? false : $provider;
}

add_filter('wp_sitemaps_add_provider', 'ol_hardening_sitemap_provider', 10, 2);

/*
 * XML-RPC: nothing here uses it (no Jetpack, no mobile app), and its
 * system.multicall lets one request try hundreds of passwords. Pingbacks go
 * too. Blocking xmlrpc.php at the web server is better still.
 */
add_filter('xmlrpc_enabled', '__return_false');
add_filter('xmlrpc_methods', '__return_empty_array');
add_filter('wp_headers', static function (array $headers): array {
    unset($headers['X-Pingback']);

    return $headers;
});
