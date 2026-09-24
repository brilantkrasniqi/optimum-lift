<?php

/**
 * Buy Now (ADR-0007). `?ol_buy_now=ID` empties the cart, adds that one
 * Product and redirects to checkout. It is plain navigation, so it works
 * without JavaScript, and it shares no handler with add-to-cart: whatever was
 * in the cart before, the buyer pays for what the button said.
 *
 * A coupon stored by a coupon link lives in the session, not the cart, so it
 * survives the emptied cart and is applied again when the Product is added
 * (coupon.php).
 */

declare(strict_types=1);

/**
 * Sends the buyer back with a notice: to the Product's page when it can be
 * viewed, else to the shop.
 */
function optimum_lift_buy_now_fail(string $message, ?WC_Product $product): never
{
    wc_add_notice($message, 'notice');

    $url = $product !== null && $product->get_status() === 'publish'
        ? $product->get_permalink()
        : wc_get_page_permalink('shop');

    nocache_headers();
    wp_safe_redirect($url);
    exit;
}

add_action('wp_loaded', static function (): void {
    $id = $_GET['ol_buy_now'] ?? null;

    $cart    = WC()->cart;
    $session = WC()->session;

    if (!is_scalar($id) || is_admin() || wp_doing_ajax() || $cart === null || !$session instanceof WC_Session_Handler) {
        return;
    }

    // A first-time visitor has no session cookie yet; without one the cart,
    // or the notice on failure, would be lost at the end of this request.
    if (!$session->has_session()) {
        $session->set_customer_session_cookie(true);
    }

    $product = wc_get_product(absint($id));
    if (!$product instanceof WC_Product || $product->get_status() !== 'publish') {
        optimum_lift_buy_now_fail(__('This product is no longer available.', 'optimum-lift'), null);
    }

    $name = optimum_lift_plain_text($product->get_name());
    if (!$product->is_purchasable() || !$product->is_in_stock()) {
        /* translators: %s: Product name. */
        optimum_lift_buy_now_fail(sprintf(__('%s can\'t be bought right now.', 'optimum-lift'), $name), $product);
    }

    $cart->empty_cart();

    // WC_Cart::add_to_cart() does not apply the validation filter; its
    // callers do, and so does this one. With an empty cart the bundle rules
    // (bundle.php) pass.
    $valid = apply_filters('woocommerce_add_to_cart_validation', true, $product->get_id(), 1);

    try {
        $added = $valid && $cart->add_to_cart($product->get_id()) !== false;
    } catch (Exception $e) {
        $added = false;
    }

    if (!$added) {
        /* translators: %s: Product name. */
        optimum_lift_buy_now_fail(sprintf(__('%s can\'t be bought right now.', 'optimum-lift'), $name), $product);
    }

    nocache_headers();
    wp_safe_redirect(wc_get_checkout_url());
    exit;
});
