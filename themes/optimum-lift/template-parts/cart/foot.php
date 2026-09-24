<?php

/**
 * The drawer foot fragment (`div.olc-foot-inner`): value without discount,
 * saving, coupon, total, checkout, continue browsing and the trust line.
 * Marked `is-empty`, which hides `.olc-foot`, when the cart is empty.
 *
 * The total is the cart total, not the subtotal, so a coupon applied from a
 * coupon link (the exit-intent offer) shows here as it will at checkout.
 */

declare(strict_types=1);

$cart  = WC()->cart;
$lines = optimum_lift_cart_lines();

$anchors  = 0.0;
$current  = 0.0;
$items    = [];
foreach ($lines as $product) {
    $price    = optimum_lift_current_price($product);
    $anchors += optimum_lift_anchor_price($product);
    $current += $price;
    $items[]  = ['id' => $product->get_id(), 'name' => optimum_lift_plain_text($product->get_name()), 'price' => $price];
}

$decimals = wc_get_price_decimals();
$total    = $cart !== null ? (float) $cart->get_total('edit') : 0.0;
$discount = $cart !== null ? (float) $cart->get_discount_total() : 0.0;
$saving   = round($anchors - $current, $decimals);
$payload  = ['value' => $total, 'currency' => get_woocommerce_currency(), 'items' => $items];
?>
<div class="<?php echo esc_attr($lines === [] ? 'olc-foot-inner is-empty' : 'olc-foot-inner'); ?>">
<?php if ($lines !== []) : ?>
    <?php if (round($anchors, $decimals) > round($total, $decimals)) : ?>
        <p class="olc-row"><span><?php esc_html_e('Value without discount', 'optimum-lift'); ?></span><del><?php echo wp_kses_post(wc_price($anchors)); ?></del></p>
    <?php endif; ?>
    <?php if ($saving > 0) : ?>
        <p class="olc-row"><span><?php esc_html_e('You save', 'optimum-lift'); ?></span><span class="olc-saved">&minus;<?php echo wp_kses_post(wc_price($saving)); ?></span></p>
    <?php endif; ?>
    <?php if ($discount > 0) : ?>
        <p class="olc-row"><span><?php esc_html_e('Coupon discount', 'optimum-lift'); ?></span><span class="olc-saved">&minus;<?php echo wp_kses_post(wc_price($discount)); ?></span></p>
    <?php endif; ?>
    <p class="olc-row total"><span><?php esc_html_e('Total', 'optimum-lift'); ?></span><span><?php echo wp_kses_post(wc_price($total)); ?></span></p>

    <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="btn btn-primary btn-lg btn-block olc-checkout" data-cta="cart-checkout" data-begin-checkout="<?php echo esc_attr((string) wp_json_encode($payload)); ?>">
        <?php esc_html_e('Continue to checkout', 'optimum-lift'); ?>
        <?php echo optimum_lift_icon('arrow-right', 'w-[18px] h-[18px]', ['stroke-width' => '2.6']); ?>
    </a>
    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="olc-cont" data-cart-close><?php esc_html_e('Keep browsing', 'optimum-lift'); ?></a>

    <?php
    get_template_part('template-parts/product/trust-line', null, [
        'items' => ['guarantee', 'instant', __('Secure payment', 'optimum-lift')],
        'class' => 'olc-reassure',
    ]);
    ?>
<?php endif; ?>
</div>
