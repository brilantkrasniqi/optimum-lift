<?php

/**
 * The drawer body fragment (`div.olc-body-inner`): the lines, then at most one
 * upsell box, or the empty state.
 */

declare(strict_types=1);

$lines = optimum_lift_cart_lines();
?>
<div class="olc-body-inner">
    <?php if ($lines === []) : ?>
        <?php get_template_part('template-parts/cart/empty'); ?>
    <?php else : ?>
        <?php
        $nonce = wp_create_nonce('ol-cart');
        foreach ($lines as $key => $product) {
            get_template_part('template-parts/cart/line', null, ['product' => $product, 'key' => $key, 'nonce' => $nonce]);
        }

        $upsell = optimum_lift_cart_upsell();
        if ($upsell !== null) {
            get_template_part('template-parts/cart/upsell', null, ['upsell' => $upsell, 'lines' => array_values($lines), 'nonce' => $nonce]);
        }
        ?>
    <?php endif; ?>
</div>
