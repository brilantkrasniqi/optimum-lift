<?php

/**
 * The cart drawer's shell, printed on wp_footer (inc/shop/cart.php). The body
 * and foot are fragments that the endpoints re-render; the notice region is
 * not, so a message survives the fragment swap. `inert` until cart.js opens it.
 *
 * While a request runs, cart.js sets `data-busy` (`add` or `update`): the bar
 * under the head moves, the totals dim, and an add shows the placeholder line
 * where the new one will appear. The status region says the same to screen
 * readers, in the words of the `data-label-*` attributes.
 */

declare(strict_types=1);
?>
<div class="olc-backdrop" data-cart-close></div>
<aside id="ol-cart-drawer" class="olc-drawer" role="dialog" aria-modal="true" aria-labelledby="ol-cart-title" inert
       data-label-add="<?php esc_attr_e('Adding to your cart…', 'optimum-lift'); ?>"
       data-label-update="<?php esc_attr_e('Updating your cart…', 'optimum-lift'); ?>">
    <div class="olc-head">
        <h2 id="ol-cart-title" class="olc-title"><?php esc_html_e('Your cart', 'optimum-lift'); ?></h2>
        <button type="button" class="olc-close" data-cart-close aria-label="<?php esc_attr_e('Close cart', 'optimum-lift'); ?>">
            <?php echo optimum_lift_icon('close', 'w-4 h-4'); ?>
        </button>
    </div>
    <div class="olc-progress" aria-hidden="true"></div>
    <p class="screen-reader-text" data-cart-status role="status" aria-live="polite"></p>
    <div class="olc-notice" data-cart-notice role="status" aria-live="polite"></div>
    <div class="olc-body">
        <div class="olc-item olc-adding" aria-hidden="true">
            <div class="olc-thumb"><span class="olc-spinner"></span></div>
            <div class="olc-item-main">
                <p class="olc-item-cat"><?php esc_html_e('Adding to your cart…', 'optimum-lift'); ?></p>
                <span class="olc-skel"></span>
                <span class="olc-skel olc-skel--short"></span>
            </div>
        </div>
        <?php echo optimum_lift_cart_part('body'); ?>
    </div>
    <div class="olc-foot">
        <?php echo optimum_lift_cart_part('foot'); ?>
    </div>
</aside>
