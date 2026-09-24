<?php

/**
 * The homepage's mobile sticky call to action: the bundle's price and a link to
 * the pricing section, fixed to the bottom of the screen.
 *
 * modules/sticky-cta.js slides it in after 700px of scroll and makes it inert
 * while it is out of view. Without JavaScript it simply stays visible. At most
 * 80px tall, the height of the footer's mobile spacer (optimum_lift_has_sticky_bar()).
 */

declare(strict_types=1);

/** @var array{bundle: WC_Product, anchor: string} $args */

$bundle = $args['bundle'];
$saving = optimum_lift_saving($bundle);
$url    = $args['anchor'] !== '' ? '#' . $args['anchor'] : wc_get_page_permalink('shop');

$label = $saving !== null
    /* translators: %d: discount percent. */
    ? sprintf(__('Get the plan — %d%% off', 'optimum-lift'), $saving['percent'])
    : __('Get the plan', 'optimum-lift');
?>
<div data-sticky-cta class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-surface/95 px-4 py-3 backdrop-blur-xl transition-transform duration-300 motion-reduce:transition-none js:translate-y-full js:data-shown:translate-y-0 md:hidden">
    <div class="flex items-center gap-3">
        <p class="grid leading-tight">
            <span class="h-display text-lg text-white"><?php echo wp_kses_post(wc_price(optimum_lift_current_price($bundle))); ?></span>
            <?php if ($saving !== null) : ?>
                <del class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                    <span class="screen-reader-text"><?php esc_html_e('Value without discount:', 'optimum-lift'); ?></span>
                    <?php echo wp_kses_post(wc_price(optimum_lift_anchor_price($bundle))); ?>
                </del>
            <?php endif; ?>
        </p>
        <a href="<?php echo esc_url($url); ?>" data-cta="sticky-mobile" class="btn btn-primary flex-1 rounded-xl px-4 py-3.5 text-[14px]"><?php echo esc_html($label); ?></a>
    </div>
</div>
