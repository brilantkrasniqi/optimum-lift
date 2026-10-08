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
 *
 * A Product sold in Sizes (sizes.php) takes the Size from the same query
 * (`variation_id` or `attribute_*`), or `ol_buy_now` is the variation's own
 * ID. Without a Size it can be bought in, the buyer goes back to the picker
 * and the cart is left as it was.
 */

declare(strict_types=1);

/**
 * Sends the buyer back with a notice: to the Product's page when it can be
 * viewed, else to the shop. With $size, to the Size picker (`#blej`), with the
 * values already chosen kept in the query.
 *
 * @param array<string, string>|null $size
 */
function optimum_lift_buy_now_fail(string $message, ?WC_Product $product, ?array $size = null): never
{
    wc_add_notice($message, 'notice');

    $url = $product !== null && $product->get_status() === 'publish'
        ? $product->get_permalink()
        : wc_get_page_permalink('shop');

    if ($size !== null && $product !== null) {
        $url = add_query_arg(array_map('rawurlencode', $size), $url) . '#blej';
    }

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

    // Resolve the Size before anything in the cart changes.
    $variation = null;
    if ($product instanceof WC_Product_Variation) {
        $parent    = optimum_lift_base_product($product);
        $variation = optimum_lift_resolve_variation($parent, ['variation_id' => $product->get_id()]);
        $product   = $parent;
        if ($variation === null) {
            optimum_lift_buy_now_fail(__('This size is not available.', 'optimum-lift'), $product, []);
        }
    } elseif (optimum_lift_needs_choice($product)) {
        $request   = wp_unslash($_GET);
        $request   = is_array($request) ? $request : [];
        $variation = optimum_lift_resolve_variation($product, $request);
        if ($variation === null) {
            $size = optimum_lift_requested_size($product, $request);
            optimum_lift_buy_now_fail(optimum_lift_size_error($product, $request), $product, $size);
        }
    }

    $name = optimum_lift_plain_text($product->get_name());
    $item = $variation ?? $product;
    if ($product->get_status() !== 'publish' || !$item->is_purchasable() || !$item->is_in_stock()) {
        /* translators: %s: Product name. */
        optimum_lift_buy_now_fail(sprintf(__('%s can\'t be bought right now.', 'optimum-lift'), $name), $product);
    }

    $cart->empty_cart();

    // WC_Cart::add_to_cart() does not apply the validation filter; its
    // callers do, and so does optimum_lift_cart_add(). With an empty cart the
    // bundle rules (bundle.php) pass.
    $added = optimum_lift_cart_add($cart, $product, $variation);

    if (!$added) {
        /* translators: %s: Product name. */
        optimum_lift_buy_now_fail(sprintf(__('%s can\'t be bought right now.', 'optimum-lift'), $name), $product);
    }

    nocache_headers();
    wp_safe_redirect(wc_get_checkout_url());
    exit;
});
