<?php

/**
 * Checkout Form
 *
 * Theme override (see woocommerce/README.md): the same hooks, form, ids and
 * classes as WooCommerce's template, laid out in two columns. Customer details,
 * payment and "Place order" on the left; the order summary on the right,
 * sticky, with the coupon form (moved after the form by inc/shop/checkout.php)
 * under it. On phones the summary comes first, collapsed, with the total in
 * view.
 *
 * `form.checkout` is `display: contents` (woocommerce.css), so its children
 * and the coupon form outside it share one grid.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined('ABSPATH') || exit;

/** @var WC_Checkout $checkout */

do_action('woocommerce_before_checkout_form', $checkout);

// If checkout registration is disabled and not logged in, the user cannot checkout.
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}

?>
<div class="ol-checkout">
    <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'woocommerce'); ?>">

        <?php
        // Before the fields: on phones the summary shows first, and focus
        // follows the source order. The grid puts it beside the form from lg.
        ?>
        <details class="ol-checkout-summary" open>
            <summary>
                <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
                <h2 id="order_review_heading"><?php esc_html_e('Your order', 'woocommerce'); ?></h2>
                <span class="ol-summary-toggle-label" aria-hidden="true"><?php esc_html_e('Show details', 'optimum-lift'); ?></span>
                <?php echo optimum_lift_checkout_summary_total_html(); ?>
                <?php echo optimum_lift_icon('chevron-down', 'ol-summary-chevron w-4 h-4'); ?>
            </summary>

            <?php do_action('woocommerce_checkout_before_order_review'); ?>

            <div id="order_review" class="woocommerce-checkout-review-order">
                <?php do_action('woocommerce_checkout_order_review'); ?>
            </div>

            <?php do_action('woocommerce_checkout_after_order_review'); ?>
        </details>
        <script>
            // Collapsed on phones, where the form matters more; always open
            // from the desktop breakpoint, where the summary sits beside it.
            (function (summary, wide) {
                if (!wide.matches) {
                    summary.open = false;
                }
                summary.addEventListener('toggle', function () {
                    if (wide.matches && !summary.open) {
                        summary.open = true;
                    }
                });
                wide.addEventListener('change', function (e) {
                    summary.open = e.matches;
                });
            })(document.currentScript.previousElementSibling, window.matchMedia('(min-width: 1024px)'));
        </script>

        <div class="ol-checkout-details">
            <?php if ($checkout->get_checkout_fields()) : ?>
                <?php do_action('woocommerce_checkout_before_customer_details'); ?>

                <div class="col2-set" id="customer_details">
                    <div class="col-1">
                        <?php do_action('woocommerce_checkout_billing'); ?>
                    </div>

                    <div class="col-2">
                        <?php do_action('woocommerce_checkout_shipping'); ?>
                    </div>
                </div>

                <?php do_action('woocommerce_checkout_after_customer_details'); ?>
            <?php endif; ?>
        </div>

    </form>

    <div class="ol-checkout-coupon">
        <?php do_action('woocommerce_after_checkout_form', $checkout); ?>
    </div>
</div>
