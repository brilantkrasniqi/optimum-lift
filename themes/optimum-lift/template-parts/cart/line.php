<?php

/**
 * One cart line in the drawer: thumb, category, name, price with the struck
 * anchor when there is a saving, and remove.
 */

declare(strict_types=1);

/** @var array{product: WC_Product, key: string, nonce: string} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$name   = $product->get_name();
$saving = optimum_lift_saving($product);
?>
<div class="olc-item">
    <div class="olc-thumb">
        <?php
        get_template_part('template-parts/product/thumb', null, [
            'product'   => $product,
            'size'      => 'woocommerce_gallery_thumbnail',
            'sizes'     => '50px',
            'class'     => 'absolute inset-0 h-full w-full',
            'icon_size' => 'w-6 h-6',
        ]);
        ?>
    </div>
    <div class="olc-item-main">
        <p class="olc-item-cat"><?php echo esc_html(optimum_lift_category_label($product)); ?></p>
        <p class="olc-item-name"><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($name); ?></a></p>
        <p class="olc-item-price">
            <span class="olc-now"><?php echo wp_kses_post(wc_price(optimum_lift_current_price($product))); ?></span>
            <?php if ($saving !== null) : ?>
                <del class="olc-was"><?php echo wp_kses_post(wc_price(optimum_lift_anchor_price($product))); ?></del>
            <?php endif; ?>
        </p>
    </div>
    <button type="button" class="olc-rm" data-cart-remove="<?php echo esc_attr($args['key']); ?>" data-nonce="<?php echo esc_attr($args['nonce']); ?>" aria-label="<?php
        /* translators: %s: Product name. */
        echo esc_attr(sprintf(__('Remove %s', 'optimum-lift'), optimum_lift_plain_text($name)));
    ?>"><?php esc_html_e('Remove', 'optimum-lift'); ?></button>
</div>
