<?php

/**
 * The hero's price box: price with the saving and the per-week price, Buy Now
 * and add to cart, what happens after paying, and the payment methods.
 *
 * The sticky buy bar (modules/buybar.js) appears once this box has scrolled
 * up out of view. It watches `data-buybar-target`, a sentinel that runs from
 * far above the box (clipped to the hero) down to the box's bottom edge: it
 * leaves the viewport only when the box is above it, and a jump that skips
 * over the box (Home key, an anchor link) still changes its state.
 */

declare(strict_types=1);

/** @var array{product: WC_Product} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$delivery = [
    __('Arrives in your email within 60 seconds', 'optimum-lift'),
    __('One-time payment — no subscription to cancel', 'optimum-lift'),
    __('Lifetime access + updates', 'optimum-lift'),
];
?>
<div class="relative mt-8 rounded-3xl border border-white/10 bg-surface p-6">
    <span data-buybar-target class="pointer-events-none absolute inset-x-0 -top-[1000rem] bottom-0" aria-hidden="true"></span>
    <?php
    get_template_part('template-parts/product/price', null, [
        'product'       => $product,
        'size'          => 'xl',
        'show_saving'   => true,
        'show_per_week' => true,
    ]);

    get_template_part('template-parts/product/buy-buttons', null, [
        'product'    => $product,
        'size'       => 'lg',
        'cta_prefix' => 'pdp',
        'buy_label'  => __('Buy now — instant access', 'optimum-lift'),
        'pulse'      => true,
        'class'      => 'mt-6',
    ]);
    ?>

    <ul class="mt-5 grid gap-2 text-[12px] font-semibold text-zinc-400">
        <?php foreach ($delivery as $line) : ?>
            <li class="flex items-center gap-2">
                <?php echo optimum_lift_icon('check', 'w-4 h-4 shrink-0 text-acid'); ?>
                <?php echo esc_html($line); ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php get_template_part('template-parts/product/payment-badges', null, ['class' => 'mt-5 border-t border-white/[.07] pt-4']); ?>
</div>
