<?php

/**
 * Coupon links. `?ol_coupon=CODE` on any page keeps a valid coupon in the
 * WooCommerce session, and the coupon is applied as soon as the cart has
 * something to discount, whichever way the Product got there (the drawer's
 * AJAX add, `?add-to-cart=`, Buy Now) and again when checkout renders.
 *
 * The homepage exit-intent offer links here, so the buyer never types a code
 * (typing a code at checkout is friction, and a mistyped one is an error
 * message at the worst moment).
 */

declare(strict_types=1);

/**
 * A coupon that can be offered right now: it exists, is published, gives a
 * discount, has not expired and has uses left. Rules that depend on the cart
 * or the buyer (minimum spend, Products, emails, per-customer limits) are
 * checked when it is applied.
 */
function optimum_lift_offerable_coupon(string $code): ?WC_Coupon
{
    $code = wc_format_coupon_code($code);
    if ($code === '' || !wc_coupons_enabled()) {
        return null;
    }

    $coupon = new WC_Coupon($code);
    if (
        $coupon->get_id() <= 0
        || !wc_is_same_coupon($coupon->get_code(), $code)
        || $coupon->get_status() !== 'publish'
        || (float) $coupon->get_amount() <= 0
    ) {
        return null;
    }

    $expires = $coupon->get_date_expires();
    if ($expires !== null && $expires->getTimestamp() <= time()) {
        return null;
    }

    $limit = $coupon->get_usage_limit();
    if ($limit > 0 && $coupon->get_usage_count() >= $limit) {
        return null;
    }

    return $coupon;
}

/**
 * The Customizer's exit-intent coupon, while it can be offered (ADR-0008: no
 * offer for a code that would not work).
 */
function optimum_lift_exit_coupon(): ?WC_Coupon
{
    $code = optimum_lift_shop_setting('exit_coupon');

    return is_string($code) ? optimum_lift_offerable_coupon($code) : null;
}

/**
 * The code a coupon link stored for this visitor, or ''.
 */
function optimum_lift_stored_coupon(): string
{
    $code = WC()->session?->get('ol_coupon');

    return is_string($code) ? $code : '';
}

function optimum_lift_forget_stored_coupon(): void
{
    WC()->session?->__unset('ol_coupon');
}

/**
 * Applies the stored coupon when the cart has items and does not carry it yet.
 *
 * The coupon is checked against the cart first, so a code the cart cannot use
 * (yet) never reaches it and never prints an error; WooCommerce's own success
 * notice confirms the discount once it is applied. A code that can no longer
 * be offered at all is forgotten.
 */
function optimum_lift_apply_stored_coupon(): void
{
    $code = optimum_lift_stored_coupon();
    $cart = WC()->cart;

    if ($code === '' || $cart === null || $cart->is_empty() || $cart->has_discount($code)) {
        return;
    }

    $coupon = optimum_lift_offerable_coupon($code);
    if ($coupon === null) {
        optimum_lift_forget_stored_coupon();
        return;
    }

    // Right after an add the totals still describe the old cart, and the
    // minimum-spend rule reads them.
    $cart->calculate_totals();

    if ((new WC_Discounts($cart))->is_coupon_valid($coupon) === true) {
        $cart->apply_coupon($coupon->get_code());
    }
}

/*
 * Stores the linked coupon, applies it at once when the cart already has
 * items, then redirects without the query argument, so the code does not stay
 * in a URL that gets shared or bookmarked. Browsers keep the #fragment across
 * the redirect, so the link still lands on its section.
 */
add_action('wp_loaded', static function (): void {
    $code = $_GET['ol_coupon'] ?? null;

    $session = WC()->session;

    if (!is_string($code) || is_admin() || wp_doing_ajax() || !$session instanceof WC_Session_Handler) {
        return;
    }

    $coupon = optimum_lift_offerable_coupon(wc_clean(wp_unslash($code)));

    if ($coupon !== null) {
        // A first-time visitor has no session cookie yet; without one the
        // stored code would be lost at the end of this request.
        if (!$session->has_session()) {
            $session->set_customer_session_cookie(true);
        }

        $session->set('ol_coupon', $coupon->get_code());
        optimum_lift_apply_stored_coupon();
    }

    wp_safe_redirect(remove_query_arg('ol_coupon'));
    exit;
});

add_action('woocommerce_add_to_cart', 'optimum_lift_apply_stored_coupon', 20, 0);

// Before WooCommerce prints the checkout notices (priority 10), so the
// "coupon applied" notice and the discounted totals appear on this render.
add_action('woocommerce_before_checkout_form', 'optimum_lift_apply_stored_coupon', 5, 0);

// A buyer who removes the coupon (or a coupon WooCommerce drops as invalid)
// must not see it come back on the next add to cart.
add_action('woocommerce_removed_coupon', static function (string $code): void {
    if (wc_is_same_coupon(optimum_lift_stored_coupon(), $code)) {
        optimum_lift_forget_stored_coupon();
    }
});

// The link's offer is spent once an order is placed.
add_action('woocommerce_checkout_order_processed', 'optimum_lift_forget_stored_coupon', 10, 0);
