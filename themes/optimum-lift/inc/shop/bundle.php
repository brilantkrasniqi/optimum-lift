<?php

/**
 * Bundle rules for the cart (ADR-0006, ADR-0007). A bundle and its own
 * components never share a cart: adding the bundle removes them, and adding a
 * component the bundle already covers changes nothing and says why. The rules
 * run on WooCommerce's own add paths (`?add-to-cart=`, WC_AJAX); the theme's
 * endpoints apply the same validation filter themselves.
 *
 * Also warns in wp-admin when a bundle is not cheaper than its components,
 * since it then shows no saving.
 */

declare(strict_types=1);

/**
 * The bundle in the cart that contains the Product, if any.
 */
function optimum_lift_cart_bundle_covering(int $product_id): ?WC_Product
{
    $cart = WC()->cart;
    if ($cart === null) {
        return null;
    }

    foreach ($cart->get_cart() as $item) {
        $line = $item['data'] ?? null;
        if (!$line instanceof WC_Product || !optimum_lift_is_bundle($line)) {
            continue;
        }

        foreach (optimum_lift_bundle_components($line) as $component) {
            if ($component->get_id() === $product_id) {
                return $line;
            }
        }
    }

    return null;
}

/**
 * Whether the Product already has a line in the cart. A variation ID matches
 * only that Size; a parent ID matches any of its Sizes.
 */
function optimum_lift_cart_has_product(int $product_id): bool
{
    foreach (WC()->cart?->get_cart() ?? [] as $item) {
        if ((int) ($item['product_id'] ?? 0) === $product_id || (int) ($item['variation_id'] ?? 0) === $product_id) {
            return true;
        }
    }

    return false;
}

add_filter('woocommerce_add_to_cart_validation', static function (mixed $passed, mixed $product_id, mixed $quantity = 1, mixed $variation_id = 0): mixed {
    if (!$passed || !is_numeric($product_id)) {
        return $passed;
    }

    $product_id = (int) $product_id;
    $product    = wc_get_product($product_id);
    if (!$product instanceof WC_Product) {
        return $passed;
    }

    $bundle = optimum_lift_cart_bundle_covering($product_id);
    if ($bundle !== null) {
        wc_add_notice(sprintf(
            /* translators: 1: Product name, 2: bundle name. */
            __('%1$s is already included in %2$s in your cart.', 'optimum-lift'),
            optimum_lift_plain_text($product->get_name()),
            optimum_lift_plain_text($bundle->get_name())
        ), 'notice');

        return false;
    }

    // A Product sold in Sizes without a Size is refused by WooCommerce itself.
    $variation_id = is_numeric($variation_id) ? (int) $variation_id : 0;
    if ($variation_id === 0 && optimum_lift_needs_choice($product)) {
        return $passed;
    }

    // Virtual Products are sold individually (inc/woocommerce.php), so a
    // second add would otherwise end in WooCommerce's "cannot add another"
    // error. Another Size of the same Product replaces it instead (below).
    if (optimum_lift_cart_has_product($variation_id > 0 ? $variation_id : $product_id)) {
        wc_add_notice(sprintf(
            /* translators: %s: Product name. */
            __('%s is already in your cart.', 'optimum-lift'),
            optimum_lift_plain_text($product->get_name())
        ), 'notice');

        return false;
    }

    return $passed;
}, 10, 4);

// One Size per Product: a new Size replaces the one in the cart. It runs after
// the add, so a failed add never loses the old line.
add_action('woocommerce_add_to_cart', static function (mixed $cart_item_key, mixed $product_id, mixed $quantity = 1, mixed $variation_id = 0): void {
    $cart      = WC()->cart;
    $variation = is_numeric($variation_id) && (int) $variation_id > 0 ? wc_get_product((int) $variation_id) : null;
    if ($cart === null || !$variation instanceof WC_Product_Variation) {
        return;
    }

    $replaced = false;
    foreach ($cart->get_cart() as $key => $item) {
        if ($key !== $cart_item_key && (int) ($item['product_id'] ?? 0) === (int) $product_id) {
            $cart->remove_cart_item((string) $key);
            $replaced = true;
        }
    }

    if ($replaced) {
        /* translators: %s: the Size now in the cart, such as "Mashkull · 80–90 kg". */
        wc_add_notice(sprintf(__('Changed to %s', 'optimum-lift'), optimum_lift_size_label($variation)), 'notice');
    }
}, 5, 4);

// Priority 10: before the stored coupon is applied (coupon.php, priority 20),
// so the coupon is validated against the cleaned cart.
add_action('woocommerce_add_to_cart', static function (mixed $cart_item_key, mixed $product_id): void {
    $cart    = WC()->cart;
    $product = is_numeric($product_id) ? wc_get_product((int) $product_id) : null;
    if ($cart === null || !$product instanceof WC_Product || !optimum_lift_is_bundle($product)) {
        return;
    }

    $component_ids = array_map(static fn (WC_Product $c): int => $c->get_id(), optimum_lift_bundle_components($product));
    if ($component_ids === []) {
        return;
    }

    foreach ($cart->get_cart() as $key => $item) {
        if ($key !== $cart_item_key && in_array((int) ($item['product_id'] ?? 0), $component_ids, true)) {
            $cart->remove_cart_item((string) $key);
        }
    }
}, 10, 2);

/*
 * The price warning. ACF writes `ol_bundle_components` on `acf/save_post` at
 * priority 10, after WooCommerce has saved the price, so both are current here.
 */
add_action('acf/save_post', static function (mixed $post_id): void {
    if (!is_numeric($post_id) || get_post_type((int) $post_id) !== 'product') {
        return;
    }

    $bundle = wc_get_product((int) $post_id);
    if (!$bundle instanceof WC_Product || !optimum_lift_is_bundle($bundle)) {
        return;
    }

    $price  = optimum_lift_current_price($bundle);
    $anchor = optimum_lift_anchor_price($bundle);
    if ($price < $anchor) {
        return;
    }

    set_transient('optimum_lift_bundle_warning_' . get_current_user_id(), [
        'id'         => $bundle->get_id(),
        'components' => count(optimum_lift_bundle_components($bundle)),
        'price'      => $price,
        'anchor'     => $anchor,
    ], HOUR_IN_SECONDS);
}, 20);

add_action('admin_notices', static function (): void {
    $key     = 'optimum_lift_bundle_warning_' . get_current_user_id();
    $warning = get_transient($key);
    if (!is_array($warning)) {
        return;
    }

    delete_transient($key);

    $bundle = wc_get_product((int) ($warning['id'] ?? 0));
    if (!$bundle instanceof WC_Product) {
        return;
    }

    if ((int) ($warning['components'] ?? 0) === 0) {
        $message = sprintf(
            /* translators: %s: bundle name. */
            esc_html__('%s has no published components, so it shows no saving. Choose its components under "Bundle".', 'optimum-lift'),
            '<strong>' . esc_html($bundle->get_name()) . '</strong>'
        );
    } else {
        $message = sprintf(
            /* translators: 1: bundle name, 2: bundle price, 3: sum of its components' prices. */
            esc_html__('%1$s costs %2$s, which is not less than its components together (%3$s), so it shows no saving. Lower its price below the components\' total.', 'optimum-lift'),
            '<strong>' . esc_html($bundle->get_name()) . '</strong>',
            wp_kses_post(wc_price((float) ($warning['price'] ?? 0))),
            wp_kses_post(wc_price((float) ($warning['anchor'] ?? 0)))
        );
    }

    echo '<div class="notice notice-warning"><p>' . $message . '</p></div>';
});
