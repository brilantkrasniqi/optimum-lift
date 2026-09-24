<?php

/**
 * The drawer's one next step, as decided by optimum_lift_cart_upsell(): swap
 * the lines for the bundle, add the complement, or upgrade to the bundle.
 * Names and prices come from the Products.
 */

declare(strict_types=1);

/**
 * @var array{upsell: array{type: 'swap'|'upgrade'|'complement', product: WC_Product, amount: float}, lines: list<WC_Product>, nonce: string} $args
 */

$upsell = $args['upsell'] ?? null;
if (!is_array($upsell) || !$upsell['product'] instanceof WC_Product) {
    return;
}

$product = $upsell['product'];
$name    = '<strong>' . esc_html($product->get_name()) . '</strong>';
$price   = '<strong>' . wc_price(optimum_lift_current_price($product)) . '</strong>';
$amount  = wc_price($upsell['amount']);
$nonce   = $args['nonce'];

switch ($upsell['type']) {
    case 'swap':
        $label = esc_html__('Better deal', 'optimum-lift');
        $text  = sprintf(
            /* translators: 1: bundle name, 2: bundle price. */
            esc_html__('%1$s includes every program and every diet for %2$s — less than what\'s in your cart.', 'optimum-lift'),
            $name,
            $price
        );
        /* translators: %s: amount saved by switching. */
        $button = sprintf(esc_html__('Switch to the full bundle and save %s', 'optimum-lift'), $amount);
        break;

    case 'upgrade':
        /* translators: %s: how much more the bundle costs than the cart's lines it covers. */
        $label = sprintf(esc_html__('Only +%s more for everything', 'optimum-lift'), $amount);
        $text  = sprintf(
            /* translators: 1: bundle name, 2: its components' total price, struck through, 3: bundle price. */
            esc_html__('%1$s includes every program and every diet, worth %2$s, for %3$s.', 'optimum-lift'),
            $name,
            '<del>' . wc_price(optimum_lift_anchor_price($product)) . '</del>',
            $price
        );
        /* translators: %s: bundle name. */
        $button = sprintf(esc_html__('Switch to %s', 'optimum-lift'), esc_html($product->get_name()));
        break;

    default:
        $label       = esc_html__('Goes well with this', 'optimum-lift');
        $for_program = optimum_lift_product_kind($product) === 'diet';
        $text        = $for_program
            /* translators: %s: meal plan name. */
            ? sprintf(esc_html__('Training without a meal plan only gets you halfway. Add %s and complete the system.', 'optimum-lift'), $name)
            /* translators: %s: training program name. */
            : sprintf(esc_html__('Eating well without training only gets you halfway. Add %s and complete the system.', 'optimum-lift'), $name);
        /* translators: 1: Product name, 2: its price. */
        $button = sprintf(esc_html__('Add %1$s — %2$s', 'optimum-lift'), esc_html($product->get_name()), $amount);
}
?>
<div class="olc-up">
    <p class="olc-up-label"><?php echo wp_kses_post($label); ?></p>
    <p class="olc-up-text"><?php echo wp_kses_post($text); ?></p>
    <?php if ($upsell['type'] === 'complement') : ?>
        <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" rel="nofollow" class="olc-up-btn" data-add-to-cart="<?php echo esc_attr((string) $product->get_id()); ?>" data-cta="drawer-complement"><?php echo wp_kses_post($button); ?></a>
    <?php else : ?>
        <button type="button" class="olc-up-btn" data-cart-swap="<?php echo esc_attr((string) $product->get_id()); ?>" data-nonce="<?php echo esc_attr($nonce); ?>" data-cta="<?php echo esc_attr('drawer-' . $upsell['type']); ?>"><?php echo wp_kses_post($button); ?></button>
    <?php endif; ?>
</div>
