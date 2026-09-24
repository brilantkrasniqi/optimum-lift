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
