<?php

/**
 * The sticky buy bar: on a long sales page the reader never has to scroll back
 * up to buy. modules/buybar.js sets `data-shown` once the price box is out of
 * view; until then the bar is off-screen and invisible, so it is neither seen
 * through the translucent header nor reachable by Tab.
 *
 * Phones: at the bottom, name hidden, Buy Now only. It stays under the
 * footer's 80px spacer (see optimum_lift_has_sticky_bar()), so the footer's
 * last line is never covered. Desktop: under the sticky header, with the
 * trust line and add to cart.
 */

declare(strict_types=1);

/** @var array{product: WC_Product} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product || !$product->is_purchasable() || !$product->is_in_stock()) {
    return;
}

$saving = optimum_lift_saving($product);
?>
<div data-buybar class="invisible fixed inset-x-0 bottom-0 z-30 translate-y-full border-t border-white/10 bg-surface/95 px-4 py-3 backdrop-blur-xl transition-[translate,visibility] duration-300 data-shown:visible data-shown:translate-y-0 motion-reduce:transition-none md:top-[calc(var(--ol-sticky-top)+4rem)] md:bottom-auto md:-translate-y-full md:border-t-0 md:border-b">
    <div class="mx-auto flex max-w-7xl items-center gap-3">
        <div class="hidden min-w-0 leading-tight sm:block">
            <p class="truncate text-[13px] font-extrabold text-white"><?php echo esc_html($product->get_name()); ?></p>
            <?php get_template_part('template-parts/product/trust-line', null, ['items' => ['instant', 'guarantee'], 'class' => 'truncate text-[11px] font-bold text-zinc-500']); ?>
        </div>

        <div class="shrink-0 leading-tight sm:ml-auto sm:text-right">
            <p class="h-display text-lg text-white"><?php echo wp_kses_post(wc_price(optimum_lift_current_price($product))); ?></p>
            <?php if ($saving !== null) : ?>
                <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                    <del>
                        <span class="screen-reader-text"><?php esc_html_e('Value without discount:', 'optimum-lift'); ?></span>
                        <?php echo wp_kses_post(wc_price(optimum_lift_anchor_price($product))); ?>
                    </del>
                </p>
            <?php endif; ?>
        </div>

        <?php
        get_template_part('template-parts/product/buy-buttons', null, [
            'product'    => $product,
            'layout'     => 'row',
            'size'       => 'sm',
            'cta_prefix' => 'buybar',
            'class'      => 'flex-1 sm:flex-none [&>a]:whitespace-nowrap [&>[data-add-to-cart]]:hidden md:[&>[data-add-to-cart]]:inline-flex',
        ]);
        ?>
    </div>
</div>
