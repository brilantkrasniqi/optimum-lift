<?php

/**
 * The withdrawal waiver at checkout (ADR-0009).
 *
 * The store gives no refunds on its digital Products. An EU buyer's 14-day
 * right of withdrawal only ends early when, before paying, they ask for
 * immediate access and acknowledge that they lose the right once it starts.
 * Checkout asks exactly that:
 *
 * - A box that starts unticked, above "Place order". That spot is inside
 *   #payment, which WooCommerce re-renders on every checkout refresh, so the
 *   box is re-ticked from the form the refresh posts, as WooCommerce does for
 *   its own terms box.
 * - An order placed without it fails with an error that names the box.
 * - The order keeps when the box was ticked and its exact wording; the order
 *   screen shows both, and the buyer's order emails confirm it (the
 *   confirmation on a durable medium the rule asks for).
 *
 * Only a cart that needs no shipping asks, which is every cart this store
 * sells: a physical Product would follow the normal return rules instead.
 */

declare(strict_types=1);

const OPTIMUM_LIFT_WAIVER_FIELD = 'ol_withdrawal_waiver';

/**
 * Whether this checkout has to ask: a cart of digital Products.
 */
function optimum_lift_waiver_needed(): bool
{
    $cart = WC()->cart;

    return $cart !== null && !$cart->is_empty() && !$cart->needs_shipping();
}

/**
 * The box's wording, stored on the order exactly as the buyer saw it.
 */
function optimum_lift_waiver_text(): string
{
    return __('I want my plan right away, and I understand that once I get it, I can no longer cancel the purchase within 14 days.', 'optimum-lift');
}

/**
 * Whether a checkout box is ticked in this request: on the order post itself,
 * or in the form a checkout refresh sends as `post_data`. Boxes inside
 * #payment are re-rendered on every refresh, so they read this to stay ticked.
 */
function optimum_lift_checkout_box_posted(string $field): bool
{
    if (!empty($_POST[$field])) {
        return true;
    }

    $form = $_POST['post_data'] ?? '';
    if (!is_string($form) || $form === '') {
        return false;
    }

    parse_str(stripslashes($form), $data);

    return !empty($data[$field]);
}

/**
 * Whether the waiver box is ticked in this request.
 */
function optimum_lift_waiver_posted(): bool
{
    return optimum_lift_checkout_box_posted(OPTIMUM_LIFT_WAIVER_FIELD);
}

add_action('woocommerce_review_order_before_submit', static function (): void {
    if (!optimum_lift_waiver_needed()) {
        return;
    }

    $policy_id = (int) get_option('woocommerce_refund_returns_page_id');
    $policy    = $policy_id > 0 && get_post_status($policy_id) === 'publish' ? get_permalink($policy_id) : false;
    ?>
    <p class="form-row ol-waiver validate-required" id="<?php echo esc_attr(OPTIMUM_LIFT_WAIVER_FIELD . '_field'); ?>">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
            <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="<?php echo esc_attr(OPTIMUM_LIFT_WAIVER_FIELD); ?>" id="<?php echo esc_attr(OPTIMUM_LIFT_WAIVER_FIELD); ?>" value="1"<?php checked(optimum_lift_waiver_posted()); ?>>
            <span>
                <?php echo esc_html(optimum_lift_waiver_text()); ?>
                <?php if (is_string($policy)) : ?>
                    <a href="<?php echo esc_url($policy); ?>" target="_blank" rel="noopener"><?php esc_html_e('Refund policy', 'optimum-lift'); ?></a>
                <?php endif; ?>
                <abbr class="required" title="<?php esc_attr_e('required', 'optimum-lift'); ?>">*</abbr>
            </span>
        </label>
    </p>
    <?php
});

add_action('woocommerce_after_checkout_validation', static function (mixed $data, mixed $errors): void {
    if ($errors instanceof WP_Error && optimum_lift_waiver_needed() && !optimum_lift_waiver_posted()) {
        $errors->add(
            OPTIMUM_LIFT_WAIVER_FIELD . '_required',
            __('To place the order, tick the box that asks for immediate access.', 'optimum-lift'),
            ['id' => OPTIMUM_LIFT_WAIVER_FIELD]
        );
    }
}, 10, 2);

add_action('woocommerce_checkout_create_order', static function (mixed $order): void {
    if (!$order instanceof WC_Order || !optimum_lift_waiver_needed() || !optimum_lift_waiver_posted()) {
        return;
    }

    $order->update_meta_data('_ol_withdrawal_waiver_at', (string) time());
    $order->update_meta_data('_ol_withdrawal_waiver_text', optimum_lift_waiver_text());
});

/**
 * When the order's buyer ticked the waiver, as a Unix time, or 0.
 */
function optimum_lift_order_waiver_at(WC_Order $order): int
{
    $at = $order->get_meta('_ol_withdrawal_waiver_at');

    return is_numeric($at) ? (int) $at : 0;
}

add_action('woocommerce_admin_order_data_after_billing_address', static function (mixed $order): void {
    if (!$order instanceof WC_Order) {
        return;
    }

    $at   = optimum_lift_order_waiver_at($order);
    $text = $order->get_meta('_ol_withdrawal_waiver_text');
    ?>
    <p>
        <strong><?php esc_html_e('Withdrawal waiver:', 'optimum-lift'); ?></strong>
        <?php if ($at > 0) : ?>
            <?php
            /* translators: %s: date and time the box was ticked. */
            echo esc_html(sprintf(__('ticked at checkout, %s', 'optimum-lift'), wp_date(get_option('date_format') . ' ' . get_option('time_format'), $at)));
            ?>
            <?php if (is_string($text) && $text !== '') : ?>
                <br><em><?php echo esc_html($text); ?></em>
            <?php endif; ?>
        <?php else : ?>
            <?php esc_html_e('not recorded for this order', 'optimum-lift'); ?>
        <?php endif; ?>
    </p>
    <?php
});

add_action('woocommerce_email_order_meta', static function (mixed $order, mixed $sent_to_admin, mixed $plain_text): void {
    if (!$order instanceof WC_Order || $sent_to_admin || optimum_lift_order_waiver_at($order) === 0) {
        return;
    }

    $line = __('You asked for your plans right away and confirmed that once you get them, you can no longer cancel the purchase within 14 days.', 'optimum-lift');

    if ($plain_text) {
        echo "\n" . esc_html($line) . "\n";
        return;
    }

    echo '<p style="margin:16px 0 0;font-size:13px;color:#636363;">' . esc_html($line) . '</p>';
}, 20, 3);
