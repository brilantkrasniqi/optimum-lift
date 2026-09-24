<?php

/**
 * Thankyou page
 *
 * Theme override (see woocommerce/README.md): a success hero with the page's
 * h1, what happens next, WooCommerce's order details (the `woocommerce_thankyou`
 * hook, after which the Plans plugin prints the buyer's Plans), cross-sells,
 * and the `purchase` tracking payload (modules/track.js, once per browser).
 * The failed branch keeps WooCommerce's pay-again and account links.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order|false $order
 */

defined('ABSPATH') || exit;
?>

<div class="woocommerce-order ol-thankyou">

    <?php
    if ($order) :
        do_action('woocommerce_before_thankyou', $order->get_id());
        ?>

        <?php if ($order->has_status('failed')) : ?>
            <div class="ol-thankyou-failed">
                <h1 class="ol-thankyou-title"><?php esc_html_e('Your payment did not go through', 'optimum-lift'); ?></h1>
                <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce'); ?></p>

                <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
                    <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>" class="button pay btn btn-primary btn-lg"><?php esc_html_e('Pay', 'woocommerce'); ?></a>
                    <?php if (is_user_logged_in()) : ?>
                        <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="button pay btn btn-ghost btn-lg"><?php esc_html_e('My account', 'woocommerce'); ?></a>
                    <?php endif; ?>
                </p>
            </div>

        <?php else : ?>
            <?php
            $ol_name      = $order->get_billing_first_name();
            $ol_new_guest = $order->get_user_id() > 0 && !is_user_logged_in();
            $ol_items     = [];
            foreach ($order->get_items() as $ol_item) {
                if ($ol_item instanceof WC_Order_Item_Product) {
                    $ol_items[] = [
                        'id'    => $ol_item->get_product_id(),
                        'name'  => optimum_lift_plain_text($ol_item->get_name()),
                        'price' => round((float) $order->get_item_total($ol_item, true), wc_get_price_decimals()),
                    ];
                }
            }
            $ol_payload = [
                'once'           => true,
                'transaction_id' => (string) $order->get_order_number(),
                'eventID'        => $order->get_order_key(),
                'order_key'      => $order->get_order_key(),
                'value'          => (float) $order->get_total(),
                'currency'       => $order->get_currency(),
                'items'          => $ol_items,
            ];
            ?>
            <section class="ol-thankyou-hero">
                <span class="ol-thankyou-check" aria-hidden="true"><?php echo optimum_lift_icon('check', 'w-7 h-7', ['stroke-width' => '3']); ?></span>
                <h1 class="ol-thankyou-title">
                    <?php
                    echo esc_html($ol_name !== ''
                        /* translators: %s: buyer's first name. */
                        ? sprintf(__('Thank you, %s!', 'optimum-lift'), $ol_name)
                        : __('Thank you!', 'optimum-lift'));
                    ?>
                </h1>
                <p class="ol-thankyou-lead"><?php esc_html_e('Your order is confirmed. Your plan is ready.', 'optimum-lift'); ?></p>

                <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
                    <li class="woocommerce-order-overview__order order">
                        <?php esc_html_e('Order number:', 'woocommerce'); ?>
                        <strong><?php echo esc_html((string) $order->get_order_number()); ?></strong>
                    </li>
                    <li class="woocommerce-order-overview__date date">
                        <?php esc_html_e('Date:', 'woocommerce'); ?>
                        <strong><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></strong>
                    </li>
                    <?php if (is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email()) : ?>
                        <li class="woocommerce-order-overview__email email">
                            <?php esc_html_e('Email:', 'woocommerce'); ?>
                            <strong><?php echo esc_html($order->get_billing_email()); ?></strong>
                        </li>
                    <?php endif; ?>
                    <li class="woocommerce-order-overview__total total">
                        <?php esc_html_e('Total:', 'woocommerce'); ?>
                        <strong><?php echo wp_kses_post($order->get_formatted_order_total()); ?></strong>
                    </li>
                    <?php if ($order->get_payment_method_title()) : ?>
                        <li class="woocommerce-order-overview__payment-method method">
                            <?php esc_html_e('Payment method:', 'woocommerce'); ?>
                            <strong><?php echo wp_kses_post($order->get_payment_method_title()); ?></strong>
                        </li>
                    <?php endif; ?>
                </ul>
            </section>

            <section class="ol-thankyou-next" aria-labelledby="ol-next-title">
                <h2 id="ol-next-title" class="ol-thankyou-h2"><?php esc_html_e('What happens next', 'optimum-lift'); ?></h2>
                <ol>
                    <li>
                        <strong><?php esc_html_e('Check your email', 'optimum-lift'); ?></strong>
                        <?php
                        echo esc_html(sprintf(
                            /* translators: %s: billing email address. */
                            __('The order confirmation arrives at %s within a few minutes. Check spam if it is not there.', 'optimum-lift'),
                            $order->get_billing_email()
                        ));
                        ?>
                    </li>
                    <?php if ($ol_new_guest) : ?>
                        <li>
                            <strong><?php esc_html_e('Set your password', 'optimum-lift'); ?></strong>
                            <?php esc_html_e('On your first order we create your account and email you a link to set its password.', 'optimum-lift'); ?>
                        </li>
                    <?php endif; ?>
                    <li>
                        <strong><?php esc_html_e('Open your plan', 'optimum-lift'); ?></strong>
                        <?php esc_html_e('Your plans are always in your account, ready to open on your phone or download as a PDF.', 'optimum-lift'); ?>
                        <a class="ol-thankyou-link" href="<?php echo esc_url(wc_get_account_endpoint_url('plans')); ?>"><?php esc_html_e('Go to my plans', 'optimum-lift'); ?></a>
                    </li>
                </ol>
            </section>

            <script type="application/json" data-ol-track="purchase"><?php echo wp_json_encode($ol_payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?></script>
        <?php endif; ?>

        <div class="ol-thankyou-details">
            <?php do_action('woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id()); ?>
            <?php do_action('woocommerce_thankyou', $order->get_id()); ?>
        </div>

        <?php if (!$order->has_status('failed')) : ?>
            <?php
            // What goes with what was bought: the ordered Products' cross-sells,
            // without anything bought or covered by a bundle that was bought.
            $ol_owned    = [];
            $ol_bought   = [];
            foreach ($order->get_items() as $ol_item) {
                $ol_product = $ol_item instanceof WC_Order_Item_Product ? $ol_item->get_product() : null;
                if ($ol_product instanceof WC_Product) {
                    $ol_bought[]  = $ol_product;
                    $ol_owned[]   = $ol_product->get_id();
                    foreach (optimum_lift_bundle_components($ol_product) as $ol_component) {
                        $ol_owned[] = $ol_component->get_id();
                    }
                }
            }
            $ol_suggest = [];
            foreach ($ol_bought as $ol_product) {
                foreach (optimum_lift_cross_sells($ol_product) as $ol_candidate) {
                    $ol_id = $ol_candidate->get_id();
                    if (!in_array($ol_id, $ol_owned, true) && !isset($ol_suggest[$ol_id])) {
                        $ol_suggest[$ol_id] = $ol_candidate;
                    }
                }
            }
            $ol_suggest = array_slice(array_values($ol_suggest), 0, 3);
            ?>
            <?php if ($ol_suggest !== []) : ?>
                <section class="ol-thankyou-more" aria-labelledby="ol-more-title">
                    <h2 id="ol-more-title" class="ol-thankyou-h2"><?php esc_html_e('Complete your system', 'optimum-lift'); ?></h2>
                    <p class="mt-2 text-[14px] text-zinc-400"><?php esc_html_e('Most customers pair training with a meal plan.', 'optimum-lift'); ?></p>
                    <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($ol_suggest as $ol_candidate) : ?>
                            <?php
                            get_template_part('template-parts/product/card', null, [
                                'product'    => $ol_candidate,
                                'variant'    => 'compact',
                                'cta_prefix' => 'thankyou',
                            ]);
                            ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>

    <?php else : ?>
        <?php wc_get_template('checkout/order-received.php', ['order' => false]); ?>
    <?php endif; ?>

</div>
