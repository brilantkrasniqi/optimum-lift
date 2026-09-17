<?php

/**
 * The homepage's pricing showcase (brief §3): the bundle as the anchor card,
 * the featured Products beside it, a way out to the full catalogue, and the
 * payment reassurance row. It is not the catalogue: the shop is.
 *
 * The countdown chip shows only while the site offer has a real end date
 * (ADR-0008); without one the eyebrow shows, if set. "{rest}" counts the
 * catalogue Products this section leaves out, and the panel that mentions
 * them hides when there are none.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block        = $args['block'];
$eyebrow      = is_string($block['eyebrow'] ?? null) ? trim($block['eyebrow']) : '';
$heading      = is_string($block['heading'] ?? null) ? trim($block['heading']) : '';
$intro        = is_string($block['intro'] ?? null) ? trim($block['intro']) : '';
$rest_heading = is_string($block['rest_heading'] ?? null) ? trim($block['rest_heading']) : '';
$rest_text    = is_string($block['rest_text'] ?? null) ? trim($block['rest_text']) : '';
$limit        = is_numeric($block['featured_limit'] ?? null) ? max(0, (int) $block['featured_limit']) : 3;
$offer        = !empty($block['show_countdown']) ? optimum_lift_offer() : null;

$bundle = is_numeric($block['bundle'] ?? null) ? wc_get_product((int) $block['bundle']) : null;
if (
    !$bundle instanceof WC_Product
    || $bundle->get_status() !== 'publish'
    || !$bundle->is_purchasable()
    || !optimum_lift_is_bundle($bundle)
) {
    $bundle = optimum_lift_find_bundle();
}

$featured = optimum_lift_featured_products($limit);
$catalog  = optimum_lift_catalog_count();
$rest     = max(0, $catalog - count($featured) - ($bundle !== null ? 1 : 0));
$shop_url = wc_get_page_permalink('shop');

$grid = match (count($featured)) {
    1       => 'mx-auto max-w-md',
    2       => 'sm:grid-cols-2',
    default => 'sm:grid-cols-2 lg:grid-cols-3',
};

/* translators: %s: number of Products in the catalogue. */
$view_all = __('View all (%s)', 'optimum-lift');
/* translators: %s: number of Products in the catalogue. */
$view_all_products = __('View all products (%s)', 'optimum-lift');
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'pricing')); ?>" class="<?php echo esc_attr(trim('relative overflow-hidden py-20 md:py-28 ' . optimum_lift_block_tone_class($block))); ?>">
    <div class="pointer-events-none absolute top-0 left-1/2 h-[46rem] w-[46rem] -translate-x-1/2 -translate-y-1/3 rounded-full bg-accent/15 blur-[140px]" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-4">
        <div class="reveal mx-auto max-w-2xl text-center">
            <?php if ($offer !== null) : ?>
                <p data-countdown-scope class="inline-flex flex-wrap items-center justify-center gap-2 rounded-full border border-accent/40 bg-accent/15 px-3.5 py-1.5 text-[10px] font-extrabold uppercase tracking-[.2em] text-accent-light">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent-light" aria-hidden="true"></span>
                    <?php esc_html_e('Offer ends in', 'optimum-lift'); ?>
                    <?php get_template_part('template-parts/components/countdown', null, ['ends_at' => $offer['ends_at'], 'variant' => 'inline']); ?>
                </p>
            <?php elseif ($eyebrow !== '') : ?>
                <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
            <?php endif; ?>
            <?php if ($heading !== '') : ?>
                <h2 class="<?php echo esc_attr($offer !== null || $eyebrow !== '' ? 'mt-5 h-display text-3xl text-white sm:text-5xl' : 'h-display text-3xl text-white sm:text-5xl'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
            <?php endif; ?>
            <?php if ($intro !== '') : ?>
                <p class="mt-4 text-[15px] leading-relaxed text-zinc-400"><?php echo esc_html($intro); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($catalog > 0) : ?>
            <div class="reveal mt-8 flex justify-center">
                <a href="<?php echo esc_url($shop_url); ?>" data-cta="catalog-header-all" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-white/10 bg-surface px-4 py-2.5 text-[12px] font-extrabold text-zinc-300 transition hover:border-white/25 hover:text-white">
                    <?php echo esc_html(sprintf($view_all, optimum_lift_format_number($catalog))); ?>
                    <?php echo optimum_lift_icon('arrow-right', 'w-4 h-4 shrink-0'); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($bundle !== null) : ?>
            <?php get_template_part('template-parts/product/bundle-banner', null, ['product' => $bundle, 'variant' => 'home', 'class' => 'reveal mt-10']); ?>
        <?php endif; ?>

        <?php if ($featured !== []) : ?>
            <div class="<?php echo esc_attr('mt-6 grid gap-5 ' . $grid); ?>">
                <?php foreach ($featured as $product) : ?>
                    <?php get_template_part('template-parts/product/card', null, ['product' => $product, 'variant' => 'featured', 'class' => 'reveal']); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($rest > 0) : ?>
            <div class="reveal mt-10 rounded-3xl border border-white/[.08] bg-surface/60 p-7 text-center">
                <?php if ($rest_heading !== '') : ?>
                    <h3 class="h-display text-xl text-white sm:text-2xl"><?php echo esc_html($rest_heading); ?></h3>
                <?php endif; ?>
                <?php if ($rest_text !== '') : ?>
                    <p class="mx-auto mt-2 max-w-md text-[13.5px] leading-relaxed text-zinc-400"><?php
                        // The number is computed here, for this section's own limit and bundle.
                        echo optimum_lift_replace_tokens(str_replace('{rest}', optimum_lift_format_number($rest), $rest_text), $bundle);
                    ?></p>
                <?php endif; ?>
                <a href="<?php echo esc_url($shop_url); ?>" data-cta="catalog-view-all" class="btn btn-light btn-lg mt-5 gap-2.5 text-[14px]">
                    <?php echo esc_html(sprintf($view_all_products, optimum_lift_format_number($catalog))); ?>
                    <?php echo optimum_lift_icon('arrow-right', 'w-5 h-5 shrink-0'); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($block['show_trust'])) : ?>
            <div class="reveal mt-10 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                <span class="flex items-center gap-1.5">
                    <?php echo optimum_lift_icon('lock', 'w-4 h-4 shrink-0 text-zinc-400'); ?>
                    <?php esc_html_e('100% secure payment', 'optimum-lift'); ?>
                </span>
                <?php get_template_part('template-parts/product/payment-badges', null, ['show_ssl' => false, 'class' => 'justify-center']); ?>
                <span class="flex items-center gap-1.5">
                    <?php echo optimum_lift_icon('send', 'w-4 h-4 shrink-0 text-zinc-400'); ?>
                    <?php esc_html_e('Instant delivery by email', 'optimum-lift'); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
</section>
