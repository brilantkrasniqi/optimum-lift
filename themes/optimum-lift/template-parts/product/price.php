<?php

/**
 * The current price, the struck-through anchor and the absolute saving.
 *
 * The anchor and saving appear only when the saving is real (ADR-0008); for a
 * bundle the anchor is the sum of its components (ADR-0006). `size`: `sm`
 * (cross-sell card), `md` (catalogue card), `lg` (bundle banner), `xl`
 * (Product price box). `show_percent` adds the "−X%" pill, `show_per_week` the
 * "≈ X per week · one-time payment" line.
 */

declare(strict_types=1);

/**
 * @var array{product: WC_Product, size?: string, show_saving?: bool, show_per_week?: bool, show_percent?: bool, class?: string} $args
 */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product || $product->get_price() === '') {
    return;
}

$styles = [
    'sm' => [
        'root'   => 'grid gap-1',
        'row'    => 'flex flex-wrap items-end gap-x-2 gap-y-1',
        'price'  => 'h-display text-2xl text-white',
        'anchor' => 'pb-1 text-[12px]',
        'inline' => true,
    ],
    'md' => [
        'root'   => 'grid gap-1',
        'row'    => 'flex flex-wrap items-end gap-x-2 gap-y-1',
        'price'  => 'h-display text-3xl text-white',
        'anchor' => 'pb-1 text-[12.5px]',
        'inline' => true,
    ],
    'lg' => [
        'root'   => 'grid gap-2',
        'row'    => 'flex flex-wrap items-end gap-x-2.5 gap-y-1',
        'price'  => 'h-display text-5xl text-white',
        'anchor' => 'pb-2 text-sm',
        'inline' => false,
    ],
    'xl' => [
        'root'   => 'grid gap-2',
        'row'    => 'flex flex-wrap items-end gap-x-3 gap-y-1',
        'price'  => 'h-display text-5xl text-white',
        'anchor' => 'pb-2 text-sm',
        'inline' => false,
    ],
];
$style = $styles[$args['size'] ?? 'md'] ?? $styles['md'];

$saving     = optimum_lift_saving($product);
$show_pill  = $saving !== null && !empty($args['show_saving']);
$percent    = $saving !== null && !empty($args['show_percent']) ? $saving['percent'] : null;
$per_week   = !empty($args['show_per_week']) ? optimum_lift_price_per_week($product) : null;
$second_row = ($show_pill && !$style['inline']) || $percent !== null || $per_week !== null;
?>
<div class="<?php echo esc_attr(trim($style['root'] . ' ' . ($args['class'] ?? ''))); ?>">
    <div class="<?php echo esc_attr($style['row']); ?>">
        <span class="<?php echo esc_attr($style['price']); ?>"><?php echo wp_kses_post(wc_price(optimum_lift_current_price($product))); ?></span>
        <?php if ($saving !== null) : ?>
            <del class="<?php echo esc_attr($style['anchor']); ?> font-bold text-zinc-500 line-through">
                <span class="screen-reader-text"><?php esc_html_e('Value without discount:', 'optimum-lift'); ?></span>
                <?php echo wp_kses_post(wc_price(optimum_lift_anchor_price($product))); ?>
            </del>
        <?php endif; ?>
        <?php if ($show_pill && $style['inline']) : ?>
            <span class="rounded-md bg-acid/15 px-1.5 py-0.5 text-[10.5px] font-extrabold uppercase tracking-wider text-acid"><?php
                /* translators: %s: amount saved, formatted price. */
                printf(esc_html__('Save %s', 'optimum-lift'), wp_kses_post(wc_price($saving['amount'])));
            ?></span>
        <?php endif; ?>
    </div>

    <?php if ($second_row) : ?>
        <div class="flex flex-wrap items-center gap-2">
            <?php if ($show_pill && !$style['inline']) : ?>
                <span class="rounded-md bg-acid px-2 py-0.5 text-[11px] font-extrabold uppercase tracking-wider text-paper"><?php
                    /* translators: %s: amount saved, formatted price. */
                    printf(esc_html__('Save %s', 'optimum-lift'), wp_kses_post(wc_price($saving['amount'])));
                ?></span>
            <?php endif; ?>
            <?php if ($percent !== null) : ?>
                <span class="rounded-md border border-acid/40 px-2 py-0.5 text-[11px] font-extrabold uppercase tracking-wider text-acid"><?php
                    /* translators: %d: discount percentage. */
                    echo esc_html(sprintf(__('−%d%%', 'optimum-lift'), $percent));
                ?></span>
            <?php endif; ?>
            <?php if ($per_week !== null) : ?>
                <span class="text-[11px] font-bold text-zinc-500"><?php
                    /* translators: %s: price per week, formatted price. */
                    printf(esc_html__('≈ %s per week · one-time payment', 'optimum-lift'), wp_kses_post(wc_price($per_week)));
                ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
