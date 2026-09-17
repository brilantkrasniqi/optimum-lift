<?php

/**
 * Shown when the archive has nothing at all to show (the bundle banner
 * counts): on a category or a search it points back to the whole shop.
 */

declare(strict_types=1);

$filtered = is_product_taxonomy() || is_search();
?>
<div class="rounded-3xl border border-white/[.08] bg-surface p-12 text-center">
    <?php if (is_search()) : ?>
        <p class="text-[15px] font-bold text-white"><?php esc_html_e('No products match your search.', 'optimum-lift'); ?></p>
    <?php elseif ($filtered) : ?>
        <p class="text-[15px] font-bold text-white"><?php esc_html_e('No products in this category.', 'optimum-lift'); ?></p>
    <?php else : ?>
        <p class="text-[15px] font-bold text-white"><?php esc_html_e('No products yet.', 'optimum-lift'); ?></p>
    <?php endif; ?>

    <?php if ($filtered) : ?>
        <p class="mt-2 text-[13.5px] text-zinc-400"><?php esc_html_e('We are working on new products — in the meantime, see them all.', 'optimum-lift'); ?></p>
        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn btn-light btn-md mt-5"><?php esc_html_e('See all', 'optimum-lift'); ?></a>
    <?php else : ?>
        <p class="mt-2 text-[13.5px] text-zinc-400"><?php esc_html_e('We are working on new products.', 'optimum-lift'); ?></p>
    <?php endif; ?>
</div>
