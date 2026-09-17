<?php

/**
 * "Bought together": the Product's cross-sells as compact cards, the bundle
 * last (optimum_lift_cross_sells()). The reader has just decided on this
 * Product, which is when a second one is easiest to accept.
 */

declare(strict_types=1);

/** @var array{product: WC_Product} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$products = optimum_lift_cross_sells($product);
if ($products === []) {
    return;
}

$intro = match (optimum_lift_product_kind($product)) {
    'program' => __('Customers who got this program also added these.', 'optimum-lift'),
    'diet'    => __('Food decides how much you lose. Training decides how you look once you lose it.', 'optimum-lift'),
    default   => '',
};
?>
<section class="border-t border-white/[.07] bg-surface/50 py-16 md:py-20" aria-labelledby="ol-cross-sells-title">
    <div class="mx-auto max-w-6xl px-4">
        <div class="reveal">
            <h2 id="ol-cross-sells-title" class="h-display text-2xl text-white sm:text-3xl"><?php esc_html_e('Frequently bought together', 'optimum-lift'); ?></h2>
            <?php if ($intro !== '') : ?>
                <p class="mt-2 text-[14px] text-zinc-400"><?php echo esc_html($intro); ?></p>
            <?php endif; ?>
        </div>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($products as $item) : ?>
                <?php
                get_template_part('template-parts/product/card', null, [
                    'product'    => $item,
                    'variant'    => 'compact',
                    'cta_prefix' => 'cross-sell',
                    'class'      => 'reveal',
                ]);
                ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
