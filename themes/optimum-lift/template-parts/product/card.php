<?php

/**
 * A Product card in one of three variants:
 *
 * - `shop`: the catalogue card (brief §4.1). Every element answers the buyer's
 *   next question in order: photo + badge, goal, name, stars, blurb + bullets,
 *   price + saving, "not a subscription", sold count, view + add to cart.
 * - `featured`: the homepage pricing card; lists what the Product leaves out,
 *   which is what the bundle next to it adds.
 * - `compact`: the cross-sell card; a bundle gets the accent frame.
 *
 * `data-cta` ids are `{cta_prefix}-view-{id}` and `{cta_prefix}-add-{id}`;
 * `class` is added to the card (e.g. `reveal`). The view button's label and
 * its screen-reader suffix share one span, so the button's gap applies once.
 */

declare(strict_types=1);

/** @var array{product: WC_Product, variant?: string, cta_prefix?: string, class?: string} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$variant = $args['variant'] ?? 'shop';
$variant = in_array($variant, ['shop', 'featured', 'compact'], true) ? $variant : 'shop';

$id        = $product->get_id();
$url       = $product->get_permalink();
$name      = $product->get_name();
$kind      = optimum_lift_product_kind($product);
$is_bundle = optimum_lift_is_bundle($product);
$category  = optimum_lift_category_label($product);
$blurb     = optimum_lift_card_blurb($product);
$points    = array_slice(optimum_lift_points($product), 0, 3);
$saving    = optimum_lift_saving($product);
$prefix    = $args['cta_prefix'] ?? match ($variant) {
    'featured' => 'card',
    'compact'  => 'cross-sell',
    default    => 'shop',
};

$card_class = match ($variant) {
    'featured' => 'flex flex-col rounded-3xl border border-white/[.08] bg-surface p-6',
    'compact'  => $is_bundle
        ? 'flex flex-col rounded-3xl border-2 border-accent bg-linear-to-b from-accent/[.15] to-paper p-6 sm:col-span-2 lg:col-span-1'
        : 'flex flex-col rounded-3xl border border-white/[.08] bg-paper p-6',
    default    => $is_bundle
        ? 'group flex flex-col overflow-hidden rounded-3xl border border-accent/50 bg-linear-to-b from-accent/[.12] to-surface transition hover:border-white/20'
        : 'group flex flex-col overflow-hidden rounded-3xl border border-white/[.08] bg-surface transition hover:border-white/20',
};

$view_label = match (true) {
    $variant === 'featured' && $kind === 'program' => __('View the program', 'optimum-lift'),
    $variant === 'featured' && $kind === 'diet'    => __('View the plan', 'optimum-lift'),
    $variant !== 'shop' && $is_bundle              => __('View the bundle', 'optimum-lift'),
    default                                        => __('View product', 'optimum-lift'),
};
$view_class = $variant === 'compact'
    ? ($is_bundle ? 'btn btn-primary btn-sm min-h-11 text-[12.5px]' : 'btn btn-light btn-sm min-h-11 text-[12.5px]')
    : ($is_bundle ? 'btn btn-primary btn-md btn-block min-h-11' : 'btn btn-light btn-md btn-block min-h-11');
$actions_class = match ($variant) {
    'featured' => 'mt-auto pt-6',
    'compact'  => 'mt-auto grid gap-2 pt-5',
    default    => 'mt-auto pt-5',
};
$add_class = $variant === 'compact'
    ? 'btn btn-outline btn-sm min-h-11'
    : 'btn btn-outline btn-sm btn-block mt-2 min-h-11 px-5';

// "Pa plan ushqimor" is only worth saying when a bundle exists to fill the gap.
$missing = null;
if ($variant === 'featured' && optimum_lift_find_bundle($product) !== null) {
    $missing = match (optimum_lift_complement_kind($product)) {
        'diet'    => __('No nutrition plan', 'optimum-lift'),
        'program' => __('No training program', 'optimum-lift'),
        default   => null,
    };
}
?>
<article class="<?php echo esc_attr(trim($card_class . ' ' . ($args['class'] ?? ''))); ?>">
    <?php if ($variant === 'shop') : ?>
        <a href="<?php echo esc_url($url); ?>" class="relative block aspect-[4/3] overflow-hidden" tabindex="-1" aria-hidden="true">
            <?php get_template_part('template-parts/product/thumb', null, ['product' => $product]); ?>
            <?php get_template_part('template-parts/product/badge', null, ['product' => $product, 'class' => 'absolute top-3 left-3']); ?>
            <?php if ($saving !== null) : ?>
                <span class="absolute top-3 right-3 rounded-full bg-black/65 px-2.5 py-1 text-[9.5px] font-extrabold uppercase tracking-widest text-acid backdrop-blur"><?php
                    /* translators: %d: discount percentage. */
                    echo esc_html(sprintf(__('−%d%%', 'optimum-lift'), $saving['percent']));
                ?></span>
            <?php endif; ?>
        </a>
    <?php endif; ?>

    <div class="<?php echo esc_attr($variant === 'shop' ? 'flex flex-1 flex-col p-5' : 'flex flex-1 flex-col'); ?>">
        <?php if ($variant === 'shop') : ?>
            <?php $goal = optimum_lift_goal($product); ?>
            <?php if ($goal !== null && $goal !== '') : ?>
                <div class="mb-3 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-acid/30 bg-acid/10 px-2.5 py-1 text-[9.5px] font-extrabold uppercase tracking-wider text-acid">
                        <?php echo optimum_lift_icon('target', 'w-3 h-3 shrink-0'); ?>
                        <?php echo esc_html($goal); ?>
                    </span>
                </div>
            <?php endif; ?>
            <?php if ($category !== '') : ?>
                <div class="text-[9.5px] font-extrabold uppercase tracking-[.2em] text-zinc-500"><?php echo esc_html($category); ?></div>
            <?php endif; ?>
            <h3 class="mt-1.5 h-display text-xl text-white">
                <a href="<?php echo esc_url($url); ?>" class="transition hover:text-accent-light"><?php echo esc_html($name); ?></a>
            </h3>
            <?php get_template_part('template-parts/product/rating', null, ['product' => $product, 'size' => 'sm', 'class' => 'mt-2']); ?>
            <?php if ($blurb !== '') : ?>
                <p class="mt-3 text-[12.5px] leading-relaxed text-zinc-400"><?php echo esc_html($blurb); ?></p>
            <?php endif; ?>
            <?php if ($points !== []) : ?>
                <ul class="mt-3.5 space-y-1.5 text-[12px] font-semibold text-zinc-300">
                    <?php foreach ($points as $point) : ?>
                        <li class="flex gap-2"><?php echo optimum_lift_icon('check', 'w-3.5 h-3.5 shrink-0 mt-0.5 text-acid'); ?><?php echo esc_html($point); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php
            get_template_part('template-parts/product/price', null, ['product' => $product, 'size' => 'md', 'show_saving' => true, 'class' => 'mt-5 pt-1']);
            get_template_part('template-parts/product/trust-line', null, ['class' => 'mt-2 text-[11px] font-semibold text-zinc-500']);
            ?>
        <?php else : ?>
            <?php if ($category !== '') : ?>
                <div class="<?php echo esc_attr('flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[.2em] ' . ($variant === 'compact' && $is_bundle ? 'text-accent-light' : 'text-zinc-500')); ?>">
                    <?php get_template_part('template-parts/product/kind-icon', null, ['product' => $product, 'class' => 'w-4 h-4 shrink-0']); ?>
                    <?php echo esc_html($category); ?>
                </div>
            <?php endif; ?>
            <h3 class="mt-3 h-display text-xl text-white">
                <a href="<?php echo esc_url($url); ?>" class="transition hover:text-accent-light"><?php echo esc_html($name); ?></a>
            </h3>
            <?php if ($blurb !== '') : ?>
                <p class="<?php echo esc_attr('mt-2 text-[12.5px] leading-relaxed ' . ($variant === 'compact' && $is_bundle ? 'text-zinc-400' : 'text-zinc-500')); ?>"><?php echo esc_html($blurb); ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($variant === 'compact') : ?>
            <?php get_template_part('template-parts/product/price', null, ['product' => $product, 'size' => 'sm', 'class' => 'mt-4']); ?>
        <?php elseif ($variant === 'featured') : ?>
            <?php get_template_part('template-parts/product/price', null, ['product' => $product, 'size' => 'md', 'class' => 'mt-5']); ?>
            <?php if ($saving !== null) : ?>
                <div class="mt-1 text-[10px] font-bold uppercase tracking-wider text-acid"><?php
                    /* translators: %d: discount percentage. */
                    echo esc_html(sprintf(__('Save %d%%', 'optimum-lift'), $saving['percent']));
                ?></div>
            <?php endif; ?>
            <?php if ($points !== [] || $missing !== null) : ?>
                <ul class="mt-5 space-y-2 text-[12.5px] font-semibold text-zinc-300">
                    <?php foreach ($points as $point) : ?>
                        <li class="flex gap-2"><?php echo optimum_lift_icon('check', 'w-3.5 h-3.5 shrink-0 mt-0.5 text-acid'); ?><?php echo esc_html($point); ?></li>
                    <?php endforeach; ?>
                    <?php if ($missing !== null) : ?>
                        <li class="flex gap-2 text-zinc-600"><?php echo optimum_lift_icon('x', 'w-3.5 h-3.5 shrink-0 mt-0.5'); ?><?php echo esc_html($missing); ?></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>

        <div class="<?php echo esc_attr($actions_class); ?>">
            <?php if ($variant === 'shop') : ?>
                <?php $sold = optimum_lift_sold_count($product); ?>
                <?php if ($sold !== null) : ?>
                    <div class="mb-3 flex items-center gap-1.5 text-[11px] font-bold text-zinc-500">
                        <?php echo optimum_lift_icon('user', 'w-3.5 h-3.5 shrink-0 text-zinc-600'); ?>
                        <?php
                        /* translators: %s: number of copies sold. */
                        $sold_text = esc_html(_n('%s sold', '%s sold', $sold, 'optimum-lift'));
                        ?>
                        <span><?php printf($sold_text, '<span class="text-zinc-300">' . esc_html(optimum_lift_format_number($sold)) . '</span>'); ?></span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?php echo esc_url($url); ?>" data-cta="<?php echo esc_attr($prefix . '-view-' . $id); ?>" class="<?php echo esc_attr($view_class); ?>">
                <span><?php echo esc_html($view_label); ?><span class="screen-reader-text">: <?php echo esc_html($name); ?></span></span>
                <?php if ($variant !== 'compact') : ?>
                    <?php echo optimum_lift_icon('arrow-right', 'w-4 h-4 shrink-0'); ?>
                <?php endif; ?>
            </a>
            <?php
            get_template_part('template-parts/product/add-to-cart', null, [
                'product' => $product,
                'cta'     => $prefix . '-add-' . $id,
                'class'   => $add_class,
                'icon'    => $variant !== 'compact',
            ]);
            ?>
        </div>
    </div>
</article>
