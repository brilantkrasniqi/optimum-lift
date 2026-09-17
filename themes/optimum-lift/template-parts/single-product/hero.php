<?php

/**
 * The Product hero (#blej, the header CTA's target): breadcrumb, then three
 * grid areas. On a phone the head (pills, name, rating) comes before the
 * gallery, so the first screen says what the Product is and shows proof; on
 * desktop the gallery sticks on the left beside the head and the body. The
 * trust strip closes the section. The section clips the glow with
 * `overflow: clip` where supported: `overflow: hidden` would make it the
 * scroll container and the gallery would never stick.
 *
 * `reviews_anchor` is the section the rating links to ('' for no link).
 */

declare(strict_types=1);

/** @var array{product: WC_Product, reviews_anchor?: string} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$category    = optimum_lift_category_label($product);
$level       = optimum_lift_level_label($product);
$points      = array_slice(optimum_lift_points($product), 0, 6);
$description = $product->get_short_description();
$days        = (int) optimum_lift_setting('guarantee_days');

$glow = optimum_lift_product_kind($product) === 'diet'
    ? 'pointer-events-none absolute -top-40 -left-40 h-[42rem] w-[42rem] rounded-full bg-acid/10 blur-[130px]'
    : 'pointer-events-none absolute -top-40 -left-40 h-[42rem] w-[42rem] rounded-full bg-accent/20 blur-[130px]';
?>
<section id="blej" class="relative overflow-hidden supports-[overflow:clip]:overflow-clip">
    <div class="<?php echo esc_attr($glow); ?>" aria-hidden="true"></div>
    <div class="grain pointer-events-none absolute inset-0 opacity-40" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-4 pt-6 pb-14 md:pb-20">
        <?php if (wc_notice_count() > 0) : ?>
            <div class="mb-6"><?php woocommerce_output_all_notices(); ?></div>
        <?php endif; ?>

        <?php woocommerce_breadcrumb(); ?>

        <div class="mt-7 grid items-start gap-7 [grid-template-areas:'head'_'gallery'_'body'] lg:grid-cols-[.92fr_1.08fr] lg:grid-rows-[auto_1fr] lg:gap-x-14 lg:gap-y-0 lg:[grid-template-areas:'gallery_head'_'gallery_body']">
            <div class="min-w-0 [grid-area:head]">
                <?php if ($category !== '' || $level !== null) : ?>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <?php if ($category !== '') : ?>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-400">
                                <?php get_template_part('template-parts/product/kind-icon', null, ['product' => $product, 'class' => 'w-3.5 h-3.5 shrink-0']); ?>
                                <?php echo esc_html($category); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($level !== null) : ?>
                            <span class="rounded-full border border-acid/30 bg-acid/15 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[.2em] text-acid"><?php echo esc_html($level); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <h1 class="mt-5 h-display text-[2.3rem] text-white sm:text-5xl lg:text-[3.4rem] [&>span]:block"><?php echo optimum_lift_title_html($product); ?></h1>

                <?php
                get_template_part('template-parts/product/rating', null, [
                    'product'   => $product,
                    'size'      => 'lg',
                    'link'      => $args['reviews_anchor'] ?? '',
                    'show_sold' => true,
                    'class'     => 'mt-4',
                ]);
                ?>
            </div>

            <div class="min-w-0 [grid-area:gallery] lg:sticky lg:top-[calc(var(--ol-sticky-top)+6rem)]">
                <?php get_template_part('template-parts/single-product/gallery', null, ['product' => $product]); ?>

                <?php if ($days > 0) : ?>
                    <div class="mt-4 flex items-center gap-3 rounded-2xl border border-white/[.08] bg-surface p-4">
                        <?php echo optimum_lift_icon('shield-check', 'w-8 h-8 shrink-0 text-acid'); ?>
                        <p class="text-[12.5px] leading-relaxed text-zinc-400">
                            <strong class="text-white"><?php echo esc_html(optimum_lift_guarantee_label()); ?>.</strong>
                            <?php esc_html_e('Try it. If it does not convince you, we refund every cent — and you keep the materials.', 'optimum-lift'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="min-w-0 [grid-area:body]">
                <?php if (trim($description) !== '') : ?>
                    <div class="max-w-xl text-[15px] leading-relaxed text-zinc-400 lg:mt-5 [&_p+p]:mt-3 [&_strong]:font-semibold [&_strong]:text-zinc-200">
                        <?php echo wp_kses_post(apply_filters('woocommerce_short_description', $description)); ?>
                    </div>
                <?php endif; ?>

                <?php if ($points !== []) : ?>
                    <ul class="mt-6 grid gap-x-5 gap-y-2.5 text-[13.5px] font-semibold text-zinc-300 sm:grid-cols-2">
                        <?php foreach ($points as $point) : ?>
                            <li class="flex gap-2.5">
                                <?php echo optimum_lift_icon('check', 'w-4 h-4 shrink-0 mt-0.5 text-acid'); ?>
                                <span><?php echo esc_html($point); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php
                get_template_part('template-parts/product/versions', null, ['product' => $product, 'class' => 'mt-8']);
                get_template_part('template-parts/single-product/price-box', null, ['product' => $product]);
                get_template_part('template-parts/single-product/bundle-hint', null, ['product' => $product]);
                ?>
            </div>
        </div>
    </div>

    <?php get_template_part('template-parts/single-product/trust-strip', null, ['product' => $product]); ?>
</section>
