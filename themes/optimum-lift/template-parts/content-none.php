<?php

/**
 * The empty state of a list: no posts yet, or a search without results. Both
 * offer another search and the shop.
 */

declare(strict_types=1);

$searching = is_search();
?>
<div class="rounded-3xl border border-white/[.08] bg-surface px-6 py-12 text-center sm:px-12">
    <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl border border-white/10 bg-white/5 text-zinc-400" aria-hidden="true">
        <?php echo optimum_lift_icon($searching ? 'search' : 'list', 'w-6 h-6'); ?>
    </span>
    <p class="mt-5 text-[15px] font-bold text-white">
        <?php echo esc_html($searching ? __('Nothing matched your search.', 'optimum-lift') : __('Nothing here yet.', 'optimum-lift')); ?>
    </p>
    <p class="mx-auto mt-2 max-w-md text-[13.5px] leading-relaxed text-zinc-400">
        <?php echo esc_html($searching ? __('Try fewer or different words, or browse every program and plan in the shop.', 'optimum-lift') : __('We are working on new content. In the meantime, browse the programs and plans.', 'optimum-lift')); ?>
    </p>

    <?php if (!$searching) : ?>
        <div class="mx-auto mt-6 max-w-md text-left">
            <?php get_search_form(); ?>
        </div>
    <?php endif; ?>

    <?php if (function_exists('wc_get_page_permalink')) : ?>
        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" data-cta="empty-shop" class="btn btn-light btn-md mt-6"><?php esc_html_e('Browse the shop', 'optimum-lift'); ?></a>
    <?php endif; ?>
</div>
