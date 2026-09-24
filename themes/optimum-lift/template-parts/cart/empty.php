<?php

/**
 * The drawer's empty state, with the way back to the shop.
 */

declare(strict_types=1);

$shop = wc_get_page_permalink('shop');
?>
<div class="olc-empty">
    <div class="olc-empty-ico"><?php echo optimum_lift_icon('box', 'w-5 h-5'); ?></div>
    <p>
        <?php esc_html_e('Your cart is empty.', 'optimum-lift'); ?><br>
        <?php esc_html_e('Pick a training program or a meal plan and start today.', 'optimum-lift'); ?>
    </p>
    <a href="<?php echo esc_url($shop); ?>" class="btn btn-ghost btn-md"><?php esc_html_e('Keep browsing the products', 'optimum-lift'); ?></a>
</div>
