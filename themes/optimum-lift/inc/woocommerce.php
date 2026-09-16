<?php
/**
 * WooCommerce integration.
 *
 * Only loaded when WooCommerce is active, so nothing here needs to guard
 * against missing functions.
 *
 * Template overrides go in themes/optimum-lift/woocommerce/ — copy the file
 * from wp-content/plugins/woocommerce/templates/ and edit the copy. Prefer a
 * hook here over a template override; overrides are frozen copies that stop
 * receiving upstream fixes.
 */

declare(strict_types=1);

/**
 * Products per row and per page on shop archives.
 */
add_filter('loop_shop_columns', static fn (): int => 3);
add_filter('loop_shop_per_page', static fn (): int => 12, 20);

/**
 * WooCommerce's default sidebar renders the "sidebar-1" widget area, which
 * this theme does not register. Point it at the Shop sidebar instead.
 */
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
add_action('woocommerce_sidebar', static function (): void {
    if (!is_active_sidebar('shop')) {
        return;
    }

    echo '<aside class="shop-sidebar" role="complementary">';
    dynamic_sidebar('shop');
    echo '</aside>';
});

/**
 * Breadcrumb markup, matched to the theme rather than Storefront's.
 */
add_filter('woocommerce_breadcrumb_defaults', static function (array $defaults): array {
    $defaults['delimiter']   = '<span class="breadcrumb__sep" aria-hidden="true">/</span>';
    $defaults['wrap_before'] = '<nav class="breadcrumb" aria-label="' . esc_attr__('Breadcrumb', 'optimum-lift') . '">';
    $defaults['wrap_after']  = '</nav>';

    return $defaults;
});

/**
 * Live cart count in the header. WooCommerce refreshes any element matching a
 * fragment key over AJAX when the cart changes.
 */
function optimum_lift_cart_link(): void
{
    printf(
        '<a class="site-header__cart" href="%1$s"><span class="cart-count" data-count="%2$d">%2$d</span><span class="screen-reader-text">%3$s</span></a>',
        esc_url(wc_get_cart_url()),
        (int) WC()->cart?->get_cart_contents_count(),
        esc_html__('View cart', 'optimum-lift')
    );
}

add_filter('woocommerce_add_to_cart_fragments', static function (array $fragments): array {
    ob_start();
    $count = (int) WC()->cart?->get_cart_contents_count();
    printf('<span class="cart-count" data-count="%1$d">%1$d</span>', $count);
    $fragments['span.cart-count'] = ob_get_clean();

    return $fragments;
});

/**
 * A Plan is a digital Download: there is nothing to ship and no quantity to
 * pick, so hide the quantity input on virtual, downloadable Products.
 */
add_filter('woocommerce_is_sold_individually', static function (bool $individually, WC_Product $product): bool {
    return $product->is_downloadable() ? true : $individually;
}, 10, 2);
