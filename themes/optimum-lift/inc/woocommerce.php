<?php
/**
 * WooCommerce integration that is not part of a storefront module.
 *
 * Only loaded when WooCommerce is active, so nothing here needs to guard
 * against missing functions. Storefront behaviour lives in inc/shop/.
 *
 * Template overrides go in themes/optimum-lift/woocommerce/ — copy the file
 * from wp-content/plugins/woocommerce/templates/ and edit the copy. Prefer a
 * hook over a template override; overrides are frozen copies that stop
 * receiving upstream fixes.
 */

declare(strict_types=1);

/**
 * woocommerce_breadcrumb() in the mock's style: small uppercase crumbs, the
 * current one brighter.
 */
add_filter('woocommerce_breadcrumb_defaults', static function (array $defaults): array {
    $defaults['delimiter']   = '<span aria-hidden="true">/</span>';
    $defaults['wrap_before'] = '<nav class="flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-zinc-600 [&_a]:transition [&_a:hover]:text-zinc-300 [&>span:last-child]:text-zinc-400" aria-label="' . esc_attr__('Breadcrumb', 'optimum-lift') . '">';
    $defaults['wrap_after']  = '</nav>';
    $defaults['before']      = '<span>';
    $defaults['after']       = '</span>';
    $defaults['home']        = _x('Home', 'breadcrumb', 'optimum-lift');

    return $defaults;
});

/**
 * The mock's trail is Home / Shop / category / Product. WooCommerce only adds
 * the shop crumb when the Product permalink base contains the shop page's
 * slug, which the default /product/ base does not. The label matches the
 * header's Shop link.
 */
add_filter('woocommerce_get_breadcrumb', static function (array $crumbs): array {
    $shop_id = wc_get_page_id('shop');

    if (!(is_product() || is_product_taxonomy()) || $shop_id <= 0 || $shop_id === (int) get_option('page_on_front')) {
        return $crumbs;
    }

    $shop_url = (string) get_permalink($shop_id);

    if (in_array($shop_url, array_column($crumbs, 1), true)) {
        return $crumbs;
    }

    // After the Home crumb, which the defaults above always set.
    array_splice($crumbs, 1, 0, [[__('Shop', 'optimum-lift'), $shop_url]]);

    return $crumbs;
});

/**
 * The design's review cards carry no avatar, and a Gravatar image sends a hash
 * of the reviewer's email to a third party on every Product view; the theme
 * self-hosts its fonts for the same reason.
 */
remove_action('woocommerce_review_before', 'woocommerce_review_display_gravatar', 10);

/**
 * Virtual Products (Plans, delivered by the optimum-lift-plans plugin) have
 * nothing to ship and no quantity to pick, so hide the quantity input.
 */
add_filter('woocommerce_is_sold_individually', static function (bool $individually, WC_Product $product): bool {
    return $product->is_virtual() ? true : $individually;
}, 10, 2);
