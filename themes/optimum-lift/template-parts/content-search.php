<?php

/**
 * One search result, of any post type. A Product shows its thumbnail and
 * price, since that is what a shopper searching the store is looking for.
 */

declare(strict_types=1);

$product = get_post_type() === 'product' && function_exists('wc_get_product') ? wc_get_product(get_the_ID()) : null;
$product = $product instanceof WC_Product ? $product : null;
$type    = get_post_type_object((string) get_post_type());
$label   = $product !== null ? optimum_lift_category_label($product) : ($type !== null ? $type->labels->singular_name : '');
$excerpt = $product !== null ? optimum_lift_card_blurb($product) : wp_trim_words(get_the_excerpt(), 26);
?>
<article <?php post_class('group relative flex items-start gap-4 rounded-2xl border border-white/[.08] bg-surface p-4 transition hover:border-white/20 sm:gap-5 sm:p-5'); ?>>
    <?php if ($product !== null) : ?>
        <div class="relative aspect-square w-20 shrink-0 overflow-hidden rounded-xl sm:w-24">
            <?php get_template_part('template-parts/product/thumb', null, ['product' => $product, 'size' => 'woocommerce_thumbnail', 'sizes' => '96px', 'icon_size' => 'w-8 h-8']); ?>
        </div>
    <?php endif; ?>

    <div class="min-w-0 flex-1">
        <?php if ($label !== '') : ?>
            <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-500"><?php echo esc_html($label); ?></p>
        <?php endif; ?>

        <h2 class="mt-1.5 text-base leading-snug font-extrabold text-white sm:text-lg">
            <a href="<?php echo esc_url(get_permalink()); ?>" class="transition after:absolute after:inset-0 group-hover:text-accent-light"><?php echo esc_html(get_the_title()); ?></a>
        </h2>

        <?php if ($excerpt !== '') : ?>
            <p class="mt-1.5 text-[13.5px] leading-relaxed text-zinc-400"><?php echo esc_html($excerpt); ?></p>
        <?php endif; ?>

        <?php if ($product !== null) : ?>
            <?php get_template_part('template-parts/product/price', null, ['product' => $product, 'size' => 'sm', 'show_saving' => true, 'class' => 'mt-3']); ?>
        <?php endif; ?>
    </div>

    <?php echo optimum_lift_icon('arrow-right', 'mt-1 hidden w-4 h-4 shrink-0 text-zinc-500 transition group-hover:translate-x-0.5 group-hover:text-white sm:block'); ?>
</article>
