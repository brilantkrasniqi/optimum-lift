<?php

/**
 * The archive's intro band: breadcrumb, heading and the catalogue sentence.
 *
 * The sentence counts the whole catalogue, bundles included, whichever
 * category is open: it describes the store, not the filter.
 */

declare(strict_types=1);

$catalog = optimum_lift_catalog_count();
$days    = (int) optimum_lift_setting('guarantee_days');
$crumbs  = optimum_lift_shop_breadcrumb();
$last    = count($crumbs) - 1;
?>
<section class="relative overflow-hidden border-b border-white/[.07]">
    <div class="pointer-events-none absolute -top-40 left-1/2 h-[40rem] w-[40rem] -translate-x-1/2 rounded-full bg-accent/15 blur-[130px]" aria-hidden="true"></div>
    <div class="grain pointer-events-none absolute inset-0 opacity-40" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-4 py-12 md:py-16">
        <nav aria-label="<?php esc_attr_e('Breadcrumb', 'optimum-lift'); ?>">
            <ol class="flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                <?php foreach ($crumbs as $i => $crumb) : ?>
                    <li class="flex items-center gap-2">
                        <?php if ($i > 0) : ?>
                            <span class="text-zinc-600" aria-hidden="true">/</span>
                        <?php endif; ?>
                        <?php if ($i === $last) : ?>
                            <span class="text-zinc-400" aria-current="page"><?php echo esc_html($crumb['label']); ?></span>
                        <?php else : ?>
                            <a href="<?php echo esc_url($crumb['url']); ?>" class="transition hover:text-zinc-300"><?php echo esc_html($crumb['label']); ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <h1 class="mt-5 h-display text-4xl text-white sm:text-5xl"><?php echo esc_html(optimum_lift_shop_title()); ?></h1>

        <?php if ($catalog > 0) : ?>
            <p class="mt-4 max-w-xl text-[15px] leading-relaxed text-zinc-400">
                <?php
                echo esc_html(sprintf(
                    /* translators: %s: number of Products in the catalogue. */
                    _n('%s digital product in Albanian — training programs and meal plans.', '%s digital products in Albanian — training programs and meal plans.', $catalog, 'optimum-lift'),
                    optimum_lift_format_number($catalog)
                ));
                echo ' ';
                echo esc_html($days > 0
                    /* translators: %d: number of days of the money-back guarantee. */
                    ? sprintf(_n('One-time payment, instant access, %d-day guarantee on all of them.', 'One-time payment, instant access, %d-day guarantee on all of them.', $days, 'optimum-lift'), $days)
                    : __('One-time payment and instant access.', 'optimum-lift'));
                ?>
            </p>
        <?php endif; ?>
    </div>
</section>
