<?php

/**
 * The way back to the shop at the end of a content view (page, post, blog,
 * search, 404): what the store sells, the no-subscription line and one button.
 */

declare(strict_types=1);

if (!function_exists('wc_get_page_permalink')) {
    return;
}
?>
<section class="border-t border-white/[.07] bg-surface/50 py-14 md:py-16">
    <div class="mx-auto max-w-5xl px-4">
        <div class="flex flex-col items-start gap-6 rounded-[2rem] border border-white/[.08] bg-linear-to-br from-accent/[.10] via-surface to-surface p-7 sm:p-8 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="h-display text-2xl text-white sm:text-3xl"><?php esc_html_e('Ready to start?', 'optimum-lift'); ?></h2>
                <p class="mt-3 max-w-xl text-[14.5px] leading-relaxed text-zinc-400"><?php esc_html_e('Training programs and meal plans in Albanian, for real people with real schedules.', 'optimum-lift'); ?></p>
                <?php get_template_part('template-parts/product/trust-line', null, ['class' => 'mt-3 text-[12px] font-bold text-zinc-500']); ?>
            </div>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" data-cta="content-shop" class="btn btn-light btn-lg shrink-0">
                <?php esc_html_e('Browse the shop', 'optimum-lift'); ?>
                <?php echo optimum_lift_icon('arrow-right', 'w-4 h-4'); ?>
            </a>
        </div>
    </div>
</section>
