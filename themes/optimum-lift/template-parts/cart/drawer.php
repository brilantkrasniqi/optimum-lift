<?php

/**
 * The cart drawer's shell, printed on wp_footer (inc/shop/cart.php). The body
 * and foot are fragments that the endpoints re-render; the notice region is
 * not, so a message survives the fragment swap. `inert` until cart.js opens it.
 */

declare(strict_types=1);
?>
<div class="olc-backdrop" data-cart-close></div>
<aside id="ol-cart-drawer" class="olc-drawer" role="dialog" aria-modal="true" aria-labelledby="ol-cart-title" inert>
    <div class="olc-head">
        <h2 id="ol-cart-title" class="olc-title"><?php esc_html_e('Your cart', 'optimum-lift'); ?></h2>
        <button type="button" class="olc-close" data-cart-close aria-label="<?php esc_attr_e('Close cart', 'optimum-lift'); ?>">
            <?php echo optimum_lift_icon('close', 'w-4 h-4'); ?>
        </button>
    </div>
    <div class="olc-notice" data-cart-notice role="status" aria-live="polite"></div>
    <div class="olc-body">
        <?php echo optimum_lift_cart_part('body'); ?>
    </div>
    <div class="olc-foot">
        <?php echo optimum_lift_cart_part('foot'); ?>
    </div>
</aside>
