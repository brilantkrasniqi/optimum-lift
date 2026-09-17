<?php

/**
 * The bundle as the price anchor: a wide accent card that makes the single
 * Products next to it read as partial, and carries the highest order value.
 *
 * `variant` `shop` (above the catalogue grid) or `home` (pricing section: adds
 * the level label, the per-week price and the larger call to action). A point
 * that opens with an all-caps label and a colon ("BONUS: …") shows the label
 * in acid.
 */

declare(strict_types=1);

/** @var array{product: WC_Product, variant?: string, class?: string} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$home   = ($args['variant'] ?? 'shop') === 'home';
$share  = optimum_lift_bundle_share($product);
$badge  = optimum_lift_badge($product);
$level  = $home ? optimum_lift_level_label($product) : null;
$blurb  = optimum_lift_card_blurb($product);
$points = array_slice(optimum_lift_points($product), 0, 6);

$label = $share !== null
    /* translators: %d: how many of every 10 customers choose the bundle. */
    ? sprintf(__('Most chosen · %d in 10 customers', 'optimum-lift'), $share)
    : ($badge['label'] ?? '');

$category = optimum_lift_category_label($product);
if ($level !== null && $level !== '') {
    $category .= ' · ' . $level;
}

$heading = $home ? 'h3' : 'h2';
$classes = [
    'relative rounded-[1.75rem] border-2 border-accent bg-linear-to-b from-accent/[.18] via-surface to-surface p-7 shadow-glow sm:p-9 lg:grid lg:items-center lg:gap-10',
    $home ? 'lg:grid-cols-[1.3fr_1fr]' : 'lg:grid-cols-[1.35fr_1fr]',
    $args['class'] ?? '',
];
?>
<div class="<?php echo esc_attr(trim(implode(' ', $classes))); ?>">
    <?php if ($label !== '') : ?>
        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-accent px-4 py-1.5 text-[10px] font-extrabold uppercase tracking-[.16em] text-white"><?php echo esc_html($label); ?></div>
    <?php endif; ?>

    <div>
        <div class="flex items-center gap-2 pt-2 text-[10px] font-extrabold uppercase tracking-[.2em] text-accent-light">
            <?php echo optimum_lift_icon('bolt', 'w-4 h-4 shrink-0'); ?>
            <?php echo esc_html($category); ?>
        </div>
        <<?php echo $heading; ?> class="mt-3 h-display text-3xl text-white">
            <a href="<?php echo esc_url($product->get_permalink()); ?>" class="transition hover:text-accent-light"><?php echo esc_html($product->get_name()); ?></a>
        </<?php echo $heading; ?>>
        <?php
        get_template_part('template-parts/product/rating', null, [
            'product'   => $product,
            'size'      => 'md',
            'show_sold' => true,
            'class'     => 'mt-2.5',
        ]);
        ?>
        <?php if ($blurb !== '') : ?>
            <p class="<?php echo esc_attr($home ? 'mt-2.5 max-w-md text-[13px] leading-relaxed text-zinc-400' : 'mt-2.5 max-w-md text-[13.5px] leading-relaxed text-zinc-400'); ?>"><?php echo esc_html($blurb); ?></p>
        <?php endif; ?>
        <?php if ($points !== []) : ?>
            <ul class="<?php echo esc_attr($home ? 'mt-6 grid gap-x-4 gap-y-2.5 text-[13.5px] font-semibold text-white sm:grid-cols-2' : 'mt-5 grid gap-x-4 gap-y-2 text-[13px] font-semibold text-white sm:grid-cols-2'); ?>">
                <?php foreach ($points as $point) : ?>
                    <?php $prefixed = preg_match('/^(\p{Lu}+:)\s+(.+)$/u', $point, $parts) === 1; ?>
                    <li class="flex gap-2.5">
                        <?php echo optimum_lift_icon('check', 'w-4 h-4 shrink-0 mt-0.5 text-acid'); ?>
                        <?php if ($prefixed) : ?>
                            <span><span class="text-acid"><?php echo esc_html($parts[1]); ?></span> <?php echo esc_html($parts[2]); ?></span>
                        <?php else : ?>
                            <span><?php echo esc_html($point); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="mt-7 lg:mt-0 lg:border-l lg:border-white/10 lg:pl-10">
        <?php
        get_template_part('template-parts/product/price', null, [
            'product'       => $product,
            'size'          => 'lg',
            'show_saving'   => true,
            'show_percent'  => !$home,
            'show_per_week' => $home,
        ]);

        get_template_part('template-parts/product/buy-buttons', null, [
            'product'    => $product,
            'size'       => 'md',
            'cta_prefix' => $home ? 'home-bundle' : 'shop-bundle',
            'buy_label'  => $home ? __('Start my transformation now', 'optimum-lift') : null,
            'pulse'      => $home,
            'class'      => 'mt-6',
        ]);

        get_template_part('template-parts/product/trust-line', null, [
            'items' => ['instant', 'guarantee'],
            'class' => 'mt-3 text-center text-[11px] font-bold text-zinc-400',
        ]);
        ?>
    </div>
</div>
