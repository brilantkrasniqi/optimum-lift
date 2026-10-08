<?php

/**
 * The cart drawer (ADR-0007): a server-rendered shell printed on every page
 * but checkout, whose body and foot are WooCommerce cart fragments, and three
 * `wc-ajax` endpoints that change the cart and answer with fresh fragments.
 * Every price in the drawer is rendered here; the JavaScript (modules/cart.js)
 * only swaps HTML.
 *
 * Bundle rules come from bundle.php through `woocommerce_add_to_cart_validation`,
 * which WC_Cart::add_to_cart() does not apply, so the endpoints apply it.
 */

declare(strict_types=1);

/**
 * The header's item count badge. Hidden at zero; `is-on` shows it.
 */
function optimum_lift_cart_badge_html(int $count): string
{
    $class = 'olc-badge absolute -top-1.5 -right-1.5 hidden h-[1.15rem] min-w-[1.15rem] place-items-center rounded-full bg-accent px-1 text-[.62rem] leading-none font-extrabold text-white [&.is-on]:grid' . ($count > 0 ? ' is-on' : '');

    return '<span data-cart-count class="' . esc_attr($class) . '">' . esc_html((string) $count) . '</span>';
}

/**
 * A drawer part (`body` or `foot`) as HTML.
 */
function optimum_lift_cart_part(string $part): string
{
    ob_start();
    get_template_part('template-parts/cart/' . $part);

    return (string) ob_get_clean();
}

/**
 * The drawer's fragments, keyed by the selector each one replaces.
 *
 * @return array<string, string>
 */
function optimum_lift_cart_fragments(): array
{
    return [
        'div.olc-body-inner'    => optimum_lift_cart_part('body'),
        'div.olc-foot-inner'    => optimum_lift_cart_part('foot'),
        'span[data-cart-count]' => optimum_lift_cart_badge_html(optimum_lift_cart_count()),
    ];
}

/**
 * The drawer's lines as Products, in cart order, keyed by cart item key. A
 * line for one Size is the variation; the theme's helpers read kinds, bundles,
 * fields and cross-sells from its parent (sizes.php).
 *
 * @return array<string, WC_Product>
 */
function optimum_lift_cart_lines(): array
{
    $lines = [];
    foreach (WC()->cart?->get_cart() ?? [] as $key => $item) {
        $product = $item['data'] ?? null;
        if ($product instanceof WC_Product) {
            $lines[(string) $key] = $product;
        }
    }

    return $lines;
}

add_filter('woocommerce_add_to_cart_fragments', static function (mixed $fragments): array {
    $fragments = is_array($fragments) ? $fragments : [];

    // WooCommerce always renders its mini-cart widget here; the theme has none.
    unset($fragments['div.widget_shopping_cart_content']);

    return array_merge($fragments, optimum_lift_cart_fragments());
});

add_action('wp_footer', static function (): void {
    if (optimum_lift_is_checkout_chrome() || WC()->cart === null) {
        return;
    }

    get_template_part('template-parts/cart/drawer');
});

/*
 * ---------------------------------------------------------------- endpoints
 */

/**
 * Answers an endpoint with the cart as it now is, and exits. Notices raised
 * while handling the request (a bundle rule, the coupon link's "applied")
 * travel as HTML and are cleared, so they do not show again on the next page.
 *
 * @param array<string, mixed> $extra
 */
function optimum_lift_cart_respond(bool $ok, array $extra = []): never
{
    $cart = WC()->cart;
    $cart?->calculate_totals();

    if (!$ok && wc_notice_count() === 0) {
        wc_add_notice(__('Something went wrong. Please try again.', 'optimum-lift'), 'error');
    }

    wp_send_json(array_merge([
        'ok'        => $ok,
        'notice'    => wc_notice_count() > 0 ? wc_print_notices(true) : '',
        'fragments' => apply_filters('woocommerce_add_to_cart_fragments', []),
        'cart_hash' => $cart?->get_cart_hash() ?? '',
        'count'     => optimum_lift_cart_count(),
    ], $extra));
}

/**
 * Common guards: POST only, a cart to work on, and a session cookie for a
 * first-time visitor, without which the cart is lost after this request.
 */
function optimum_lift_cart_endpoint_start(bool $check_nonce): WC_Cart
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        wp_send_json(['ok' => false], 405);
    }

    $cart    = WC()->cart;
    $session = WC()->session;
    if ($cart === null) {
        wp_send_json(['ok' => false], 500);
    }

    if ($session instanceof WC_Session_Handler && !$session->has_session()) {
        $session->set_customer_session_cookie(true);
    }

    $nonce = $_POST['nonce'] ?? '';
    if ($check_nonce && (!is_string($nonce) || !wp_verify_nonce(wp_unslash($nonce), 'ol-cart'))) {
        wc_add_notice(__('Your session has expired. Please reload the page and try again.', 'optimum-lift'), 'error');
        optimum_lift_cart_respond(false);
    }

    return $cart;
}

/**
 * Adds one Product, or one Size of it, through the same validation as
 * WooCommerce's own add paths (its variable handler passes the variation and
 * its attributes too). Returns whether it was added.
 */
function optimum_lift_cart_add(WC_Cart $cart, WC_Product $product, ?WC_Product_Variation $variation = null): bool
{
    $product_id   = optimum_lift_base_id($product);
    $variation_id = $variation?->get_id() ?? 0;
    $attributes   = $variation?->get_variation_attributes() ?? [];

    if (!apply_filters('woocommerce_add_to_cart_validation', true, $product_id, 1, $variation_id, $attributes)) {
        return false;
    }

    try {
        return $cart->add_to_cart($product_id, 1, $variation_id, $attributes) !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * The tracking item for a Product, or one Size of it: the parent's ID and
 * name, the price paid and, for a Size, its label as `variant`.
 *
 * @return array<string, mixed>
 */
function optimum_lift_cart_item_payload(WC_Product $product, ?WC_Product_Variation $variation = null): array
{
    $base = optimum_lift_base_product($product);
    $item = [
        'id'       => $base->get_id(),
        'name'     => optimum_lift_plain_text($base->get_name()),
        'price'    => optimum_lift_current_price($variation ?? $product),
        'currency' => get_woocommerce_currency(),
    ];

    if ($variation !== null) {
        $item['variant'] = optimum_lift_size_label($variation);
    }

    return $item;
}

/**
 * Answers that a Size must be chosen first, with the Product page's picker.
 *
 * @param array<mixed> $request Unslashed request values.
 */
function optimum_lift_cart_needs_choice(WC_Product $product, array $request): never
{
    wc_add_notice(optimum_lift_size_error($product, $request), 'notice');
    optimum_lift_cart_respond(false, ['needs_choice' => true, 'url' => $product->get_permalink() . '#blej']);
}

add_action('wc_ajax_ol_add_to_cart', static function (): void {
    $cart    = optimum_lift_cart_endpoint_start(false);
    $product = wc_get_product(absint($_POST['product_id'] ?? 0));

    if (!$product instanceof WC_Product || $product->get_status() !== 'publish') {
        wc_add_notice(__('This product is no longer available.', 'optimum-lift'), 'error');
        optimum_lift_cart_respond(false);
    }

    // A variation's ID stands for that Size of its parent.
    $request = wp_unslash($_POST);
    $request = is_array($request) ? $request : [];
    if ($product instanceof WC_Product_Variation) {
        $request = ['variation_id' => $product->get_id()];
        $product = optimum_lift_base_product($product);
    }

    $variation = null;
    if (optimum_lift_needs_choice($product)) {
        $variation = optimum_lift_resolve_variation($product, $request);
        if ($variation === null) {
            optimum_lift_cart_needs_choice($product, $request);
        }
    }

    $item = optimum_lift_cart_item_payload($product, $variation);

    // Already there: nothing to change, and nothing to complain about.
    if (optimum_lift_cart_has_product($variation?->get_id() ?? $product->get_id())) {
        optimum_lift_cart_respond(true, ['item' => $item, 'added' => false]);
    }

    $added = optimum_lift_cart_add($cart, $product, $variation);

    optimum_lift_cart_respond($added, ['item' => $item, 'added' => $added]);
});

add_action('wc_ajax_ol_remove_from_cart', static function (): void {
    $cart = optimum_lift_cart_endpoint_start(true);
    $key  = $_POST['cart_item_key'] ?? '';
    $key  = is_string($key) ? wc_clean(wp_unslash($key)) : '';

    // A line that is already gone is the outcome the buyer asked for.
    if (is_string($key) && $key !== '' && $cart->get_cart_item($key) !== []) {
        $cart->remove_cart_item($key);
    }

    optimum_lift_cart_respond(true);
});

add_action('wc_ajax_ol_swap_to_bundle', static function (): void {
    $cart   = optimum_lift_cart_endpoint_start(true);
    $bundle = wc_get_product(absint($_POST['bundle_id'] ?? 0));

    if (!$bundle instanceof WC_Product || $bundle->get_status() !== 'publish' || !optimum_lift_is_bundle($bundle)) {
        wc_add_notice(__('This product is no longer available.', 'optimum-lift'), 'error');
        optimum_lift_cart_respond(false);
    }

    // A bundle sold in Sizes takes the Size of a component already in the cart.
    $variation = null;
    if (optimum_lift_needs_choice($bundle)) {
        $variation = optimum_lift_cart_bundle_size($bundle);
        if ($variation === null) {
            optimum_lift_cart_needs_choice($bundle, []);
        }
    }

    $item = optimum_lift_cart_item_payload($bundle, $variation);

    if (optimum_lift_cart_has_product($variation?->get_id() ?? $bundle->get_id())) {
        optimum_lift_cart_respond(true, ['item' => $item, 'added' => false]);
    }

    // bundle.php removes the lines the bundle covers once it is added; if it
    // cannot be added, nothing has been removed.
    $added = optimum_lift_cart_add($cart, $bundle, $variation);

    optimum_lift_cart_respond($added, ['item' => $item, 'added' => $added]);
});
