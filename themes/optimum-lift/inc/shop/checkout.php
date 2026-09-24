<?php

/**
 * The classic checkout, trimmed (ADR-0007). Nothing here rebuilds WooCommerce's
 * checkout: it removes what a digital order does not need and moves two blocks
 * so the templates can lay them out.
 *
 * - A cart without shipping asks only for email, first and last name and
 *   country. Every removed field raises completion.
 * - No order notes.
 * - The coupon form moves below the checkout form (never inside it: nested
 *   forms are invalid, and checkout.js binds form.checkout_coupon once).
 * - The payment block moves out of the order review into the form column, so
 *   the order summary can collapse on mobile without hiding "Place order".
 * - A trust block sits next to "Place order".
 */

declare(strict_types=1);

add_filter('woocommerce_checkout_fields', static function (mixed $fields): mixed {
    $cart = WC()->cart;
    if (!is_array($fields) || $cart === null || $cart->needs_shipping()) {
        return $fields;
    }

    $billing = is_array($fields['billing'] ?? null) ? $fields['billing'] : [];
    $keep    = [
        'billing_email'      => ['priority' => 10, 'class' => ['form-row-wide']],
        'billing_first_name' => ['priority' => 20, 'class' => ['form-row-first']],
        'billing_last_name'  => ['priority' => 30, 'class' => ['form-row-last']],
        'billing_country'    => ['priority' => 40, 'class' => ['form-row-wide', 'address-field', 'update_totals_on_change']],
    ];

    $trimmed = [];
    foreach ($keep as $key => $overrides) {
        if (isset($billing[$key]) && is_array($billing[$key])) {
            $trimmed[$key] = array_merge($billing[$key], $overrides);
        }
    }

    if (isset($trimmed['billing_email'])) {
        $trimmed['billing_email']['autocomplete']      = 'email';
        $trimmed['billing_email']['custom_attributes'] = array_merge(
            is_array($trimmed['billing_email']['custom_attributes'] ?? null) ? $trimmed['billing_email']['custom_attributes'] : [],
            ['inputmode' => 'email']
        );
    }

    $fields['billing']  = $trimmed;
    $fields['shipping'] = [];
    unset($fields['order']);

    return $fields;
}, 20);

add_filter('woocommerce_enable_order_notes_field', '__return_false');

add_action('init', static function (): void {
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
    add_action('woocommerce_after_checkout_form', 'woocommerce_checkout_coupon_form', 10);

    remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
    add_action('woocommerce_checkout_after_customer_details', 'woocommerce_checkout_payment', 10);
});

add_action('woocommerce_review_order_after_submit', static function (): void {
    get_template_part('template-parts/checkout/trust');
});

/*
 * The theme styles the classic shortcodes, not WooCommerce's Cart and
 * Checkout blocks, and the trimmed fields only apply to the shortcode.
 */
add_action('admin_notices', static function (): void {
    if (!current_user_can('edit_pages')) {
        return;
    }

    $pages = [
        'cart'     => ['block' => 'woocommerce/cart', 'shortcode' => '[woocommerce_cart]'],
        'checkout' => ['block' => 'woocommerce/checkout', 'shortcode' => '[woocommerce_checkout]'],
    ];

    foreach ($pages as $page => $expected) {
        $page_id = wc_get_page_id($page);
        $post    = $page_id > 0 ? get_post($page_id) : null;
        if (!$post instanceof WP_Post || !has_block($expected['block'], $post)) {
            continue;
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            sprintf(
                /* translators: 1: page title linked to its edit screen, 2: shortcode. */
                esc_html__('%1$s uses the WooCommerce block, which the Optimum Lift theme does not style. Replace the block with the %2$s shortcode.', 'optimum-lift'),
                '<a href="' . esc_url((string) get_edit_post_link($post->ID)) . '">' . esc_html(get_the_title($post)) . '</a>',
                '<code>' . esc_html($expected['shortcode']) . '</code>'
            )
        );
    }
});

/*
 * ------------------------------------------------------------ layout (10c)
 */

/**
 * The order total in the summary's toggle, so the collapsed summary on a
 * phone still shows what the buyer pays. Refreshed with the order review.
 */
function optimum_lift_checkout_summary_total_html(): string
{
    $total = WC()->cart?->get_total() ?? '';

    return '<span class="ol-summary-total">' . wp_kses_post($total) . '</span>';
}

add_filter('woocommerce_update_order_review_fragments', static function (mixed $fragments): mixed {
    if (is_array($fragments)) {
        $fragments['.ol-summary-total'] = optimum_lift_checkout_summary_total_html();
    }

    return $fragments;
});

// The payment block moved to the form column (above) gets its own heading.
add_action('woocommerce_checkout_after_customer_details', static function (): void {
    if (WC()->cart?->needs_payment()) {
        echo '<h2 class="ol-checkout-heading">' . esc_html__('Payment', 'optimum-lift') . '</h2>';
    }
}, 5);

// A digital order has no billing address to speak of: the form asks who the
// buyer is and where to send the access.
add_filter('gettext_woocommerce', static function (string $translation, string $text): string {
    if ($text === 'Billing details' && did_action('wp') > 0 && is_checkout() && WC()->cart !== null && !WC()->cart->needs_shipping()) {
        return __('Your details', 'optimum-lift');
    }

    return $translation;
}, 10, 2);

/*
 * The thank-you page right after a guest checkout. The Plans plugin creates
 * an account for a guest order (Access/OrderAccess.php), which makes the order
 * a "known shopper" one, and WooCommerce then asks the buyer to log in with a
 * password they have not set yet instead of showing the order and its Plans.
 * The buyer who just placed it is let through, as WooCommerce lets a guest
 * through: this browser's session holds the order's billing email, and the
 * order is within WooCommerce's email-verification grace period.
 */
add_filter('woocommerce_order_received_verify_known_shoppers', static function (mixed $verify): mixed {
    global $wp;

    $order_id = absint($wp->query_vars['order-received'] ?? 0);
    $key      = isset($_GET['key']) && is_string($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
    $order    = $order_id > 0 ? wc_get_order($order_id) : null;
    $customer = WC()->customer;

    if (!$order instanceof WC_Order || !is_string($key) || !hash_equals($order->get_order_key(), $key) || $customer === null) {
        return $verify;
    }

    $created = $order->get_date_created();
    $grace   = (int) apply_filters('woocommerce_order_email_verification_grace_period', 10 * MINUTE_IN_SECONDS, $order, 'order-received');
    $email   = $customer->get_billing_email();

    if ($created !== null && $created->getTimestamp() > time() - $grace && $email !== '' && strcasecmp($email, $order->get_billing_email()) === 0) {
        return false;
    }

    return $verify;
});

/*
 * ------------------------------------------------------------ cart page (10e)
 */

/**
 * What goes with a set of Products: their cross-sells, without the Products
 * themselves or anything a bundle among them covers, at most $limit.
 *
 * @param list<WC_Product> $products
 * @return list<WC_Product>
 */
function optimum_lift_cross_sells_for(array $products, int $limit = 3): array
{
    $owned = [];
    foreach ($products as $product) {
        $owned[] = $product->get_id();
        foreach (optimum_lift_bundle_components($product) as $component) {
            $owned[] = $component->get_id();
        }
    }

    $suggest = [];
    foreach ($products as $product) {
        foreach (optimum_lift_cross_sells($product) as $candidate) {
            $id = $candidate->get_id();
            if (!in_array($id, $owned, true) && !isset($suggest[$id])) {
                $suggest[$id] = $candidate;
            }
        }
    }

    return array_slice(array_values($suggest), 0, $limit);
}

// The theme's thumbnail (the kind-icon tile while a Product has no photo)
// instead of WooCommerce's light placeholder image.
add_filter('woocommerce_cart_item_thumbnail', static function (mixed $thumbnail, mixed $cart_item): mixed {
    $product = is_array($cart_item) ? ($cart_item['data'] ?? null) : null;
    if (!$product instanceof WC_Product) {
        return $thumbnail;
    }

    ob_start();
    echo '<span class="ol-cart-thumb">';
    get_template_part('template-parts/product/thumb', null, [
        'product'   => $product,
        'size'      => 'woocommerce_gallery_thumbnail',
        'sizes'     => '64px',
        'class'     => 'absolute inset-0 h-full w-full',
        'icon_size' => 'w-6 h-6',
    ]);
    echo '</span>';

    return (string) ob_get_clean();
}, 10, 2);

// WooCommerce's cross-sells use the loop templates, which the theme does not
// style: the cart page shows the theme's compact cards instead.
add_action('init', static function (): void {
    remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
});

add_action('woocommerce_after_cart', static function (): void {
    $products = array_values(optimum_lift_cart_lines());
    $suggest  = $products !== [] ? optimum_lift_cross_sells_for($products) : [];
    if ($suggest === []) {
        return;
    }
    ?>
    <section class="ol-cart-cross-sells" aria-labelledby="ol-cart-cross-sells-title">
        <h2 id="ol-cart-cross-sells-title" class="h-display text-2xl text-white sm:text-3xl"><?php esc_html_e('Frequently bought together', 'optimum-lift'); ?></h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <?php
            foreach ($suggest as $product) {
                get_template_part('template-parts/product/card', null, [
                    'product'    => $product,
                    'variant'    => 'compact',
                    'cta_prefix' => 'cart-page',
                ]);
            }
            ?>
        </div>
    </section>
    <?php
});
