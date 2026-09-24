<?php

/**
 * Review order table
 *
 * Theme override (see woocommerce/README.md): WooCommerce's table with a
 * thumbnail and the category on each line, the struck anchor price when the
 * line is on sale, and a "You save" row (the anchors' sum minus the
 * subtotal). Every hook and row of the original is kept.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.0.0
 */

defined('ABSPATH') || exit;

$ol_cart    = WC()->cart;
$ol_anchors = 0.0;
?>
<table class="shop_table woocommerce-checkout-review-order-table">
    <thead>
        <tr>
            <th class="product-name"><?php esc_html_e('Product', 'woocommerce'); ?></th>
            <th class="product-total"><?php esc_html_e('Subtotal', 'woocommerce'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        do_action('woocommerce_review_order_before_cart_contents');

        foreach ($ol_cart->get_cart() as $cart_item_key => $cart_item) {
            $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);

            /**
             * Filter whether this cart item is visible in the checkout review order table.
             *
             * @since 2.1.0
             * @param bool   $visible       Whether the cart item is visible. Default true.
             * @param array  $cart_item     The cart item data.
             * @param string $cart_item_key The cart item key.
             */
            $visible = apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key);

            if ($_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && $visible) {
                $ol_anchor   = optimum_lift_anchor_price($_product);
                $ol_anchors += $ol_anchor * (int) $cart_item['quantity'];
                $ol_saving   = optimum_lift_saving($_product);
                ?>
                <tr class="<?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
                    <td class="product-name">
                        <div class="ol-review-line">
                            <span class="ol-review-thumb">
                                <?php
                                get_template_part('template-parts/product/thumb', null, [
                                    'product'   => $_product,
                                    'size'      => 'woocommerce_gallery_thumbnail',
                                    'sizes'     => '48px',
                                    'class'     => 'absolute inset-0 h-full w-full',
                                    'icon_size' => 'w-5 h-5',
                                ]);
                                ?>
                            </span>
                            <span class="ol-review-text">
                                <span class="ol-review-cat"><?php echo esc_html(optimum_lift_category_label($_product)); ?></span>
                                <?php echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key)) . '&nbsp;'; ?>
                                <?php echo apply_filters('woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf('&times;&nbsp;%s', $cart_item['quantity']) . '</strong>', $cart_item, $cart_item_key); ?>
                                <?php echo wc_get_formatted_cart_item_data($cart_item); ?>
                            </span>
                        </div>
                    </td>
                    <td class="product-total">
                        <?php if ($ol_saving !== null) : ?>
                            <del class="ol-review-anchor"><?php echo wp_kses_post(wc_price($ol_anchor * (int) $cart_item['quantity'])); ?></del>
                        <?php endif; ?>
                        <?php echo apply_filters('woocommerce_cart_item_subtotal', $ol_cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
                    </td>
                </tr>
                <?php
            }
        }

        do_action('woocommerce_review_order_after_cart_contents');

        $ol_saved = round($ol_anchors - (float) $ol_cart->get_displayed_subtotal(), wc_get_price_decimals());
        ?>
    </tbody>
    <tfoot>

        <tr class="cart-subtotal">
            <th><?php esc_html_e('Subtotal', 'woocommerce'); ?></th>
            <td><?php wc_cart_totals_subtotal_html(); ?></td>
        </tr>

        <?php if ($ol_saved > 0) : ?>
            <tr class="ol-savings">
                <th><?php esc_html_e('You save', 'optimum-lift'); ?></th>
                <td>&minus;<?php echo wp_kses_post(wc_price($ol_saved)); ?></td>
            </tr>
        <?php endif; ?>

        <?php foreach ($ol_cart->get_coupons() as $code => $coupon) : ?>
            <tr class="cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
                <th><?php wc_cart_totals_coupon_label($coupon); ?></th>
                <td><?php wc_cart_totals_coupon_html($coupon); ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if ($ol_cart->needs_shipping() && $ol_cart->show_shipping()) : ?>
            <?php do_action('woocommerce_review_order_before_shipping'); ?>

            <?php wc_cart_totals_shipping_html(); ?>

            <?php do_action('woocommerce_review_order_after_shipping'); ?>
        <?php endif; ?>

        <?php foreach ($ol_cart->get_fees() as $fee) : ?>
            <tr class="fee">
                <th><?php echo esc_html($fee->name); ?></th>
                <td><?php wc_cart_totals_fee_html($fee); ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if (wc_tax_enabled() && !$ol_cart->display_prices_including_tax()) : ?>
            <?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
                <?php foreach ($ol_cart->get_tax_totals() as $code => $tax) : ?>
                    <tr class="tax-rate tax-rate-<?php echo esc_attr(sanitize_title($code)); ?>">
                        <th><?php echo esc_html($tax->label); ?></th>
                        <td><?php echo wp_kses_post($tax->formatted_amount); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr class="tax-total">
                    <th><?php echo esc_html(WC()->countries->tax_or_vat()); ?></th>
                    <td><?php wc_cart_totals_taxes_total_html(); ?></td>
                </tr>
            <?php endif; ?>
        <?php endif; ?>

        <?php do_action('woocommerce_review_order_before_order_total'); ?>

        <tr class="order-total">
            <th><?php esc_html_e('Total', 'woocommerce'); ?></th>
            <td><?php wc_cart_totals_order_total_html(); ?></td>
        </tr>

        <?php do_action('woocommerce_review_order_after_order_total'); ?>

    </tfoot>
</table>
