<?php

/**
 * The add-to-cart link, per the markup contract: `modules/cart.js` intercepts
 * it and opens the drawer; without JavaScript WooCommerce adds via the URL.
 *
 * `icon` prefixes the cart icon. The Product name is appended for screen
 * readers, since a card grid repeats the same visible label.
 *
 * A Product that needs a Size (sizes.php): with `form` (the Size picker's form
 * id), a submit button for that form, which modules/cart.js sends through the
 * drawer once the browser has validated the choice. It is not
 * `[data-add-to-cart]`: that click handler would run before validation.
 * Without `form`, a "Choose your size" link to the picker.
 */

declare(strict_types=1);

/** @var array{product: WC_Product, cta: string, class: string, label?: string|null, icon?: bool, form?: string} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product || !$product->is_purchasable() || !$product->is_in_stock()) {
    return;
}

$choose = optimum_lift_needs_choice($product);
$form   = $choose ? ($args['form'] ?? '') : '';
?>
<?php if ($choose && $form === '') : ?>
<a href="<?php echo esc_url($product->get_permalink() . '#blej'); ?>" data-cta="<?php echo esc_attr(str_replace('-add', '-choose-size', $args['cta'])); ?>" class="<?php echo esc_attr($args['class']); ?>">
    <?php esc_html_e('Choose your size', 'optimum-lift'); ?><span class="screen-reader-text">: <?php echo esc_html($product->get_name()); ?></span>
</a>
<?php elseif ($choose) : ?>
<button type="submit" form="<?php echo esc_attr($form); ?>" name="add-to-cart" value="<?php echo esc_attr((string) $product->get_id()); ?>" data-size-add data-cta="<?php echo esc_attr($args['cta']); ?>" class="<?php echo esc_attr($args['class']); ?>">
    <?php if (!empty($args['icon'])) : ?>
        <?php echo optimum_lift_icon('cart', 'w-4 h-4 shrink-0'); ?>
    <?php endif; ?>
    <?php echo esc_html($args['label'] ?? __('Add to cart', 'optimum-lift')); ?>
</button>
<?php else : ?>
<a href="<?php echo esc_url($product->add_to_cart_url()); ?>" rel="nofollow" data-add-to-cart="<?php echo esc_attr((string) $product->get_id()); ?>" data-cta="<?php echo esc_attr($args['cta']); ?>" class="<?php echo esc_attr($args['class']); ?>">
    <?php if (!empty($args['icon'])) : ?>
        <?php echo optimum_lift_icon('cart', 'w-4 h-4 shrink-0'); ?>
    <?php endif; ?>
    <?php echo esc_html($args['label'] ?? __('Add to cart', 'optimum-lift')); ?><span class="screen-reader-text">: <?php echo esc_html($product->get_name()); ?></span>
</a>
<?php endif; ?>
