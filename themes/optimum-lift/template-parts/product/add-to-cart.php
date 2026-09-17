<?php

/**
 * The add-to-cart link, per the markup contract: `modules/cart.js` intercepts
 * it and opens the drawer; without JavaScript WooCommerce adds via the URL.
 *
 * `icon` prefixes the cart icon. The Product name is appended for screen
 * readers, since a card grid repeats the same visible label.
 */

declare(strict_types=1);

/** @var array{product: WC_Product, cta: string, class: string, label?: string|null, icon?: bool} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product || !$product->is_purchasable() || !$product->is_in_stock()) {
    return;
}
?>
<a href="<?php echo esc_url($product->add_to_cart_url()); ?>" rel="nofollow" data-add-to-cart="<?php echo esc_attr((string) $product->get_id()); ?>" data-cta="<?php echo esc_attr($args['cta']); ?>" class="<?php echo esc_attr($args['class']); ?>">
    <?php if (!empty($args['icon'])) : ?>
        <?php echo optimum_lift_icon('cart', 'w-4 h-4 shrink-0'); ?>
    <?php endif; ?>
    <?php echo esc_html($args['label'] ?? __('Add to cart', 'optimum-lift')); ?><span class="screen-reader-text">: <?php echo esc_html($product->get_name()); ?></span>
</a>
