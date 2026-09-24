<?php

/**
 * The shop and Product category archives (dyqani.html), from WooCommerce's
 * main query: intro, the bundle banner, category filter and sort, result
 * count, the grid of cards, the empty state and the guarantee band.
 *
 * The banner stands outside the filter and the sort: next to the bundle the
 * single Products read as partial. Where it shows, the grid leaves the bundle
 * out and the count includes it, so "N products" matches what is on screen.
 */

declare(strict_types=1);

/** @var WP_Query $wp_query */
global $wp_query;

$bundle   = optimum_lift_shop_banner_bundle();
$products = [];
$in_query = false;

// Not $post: load_template() makes that name the global post.
foreach ($wp_query->posts as $item) {
    $product = wc_get_product($item);

    if (!$product instanceof WC_Product) {
        continue;
    }

    if ($bundle !== null && $product->get_id() === $bundle->get_id()) {
        $in_query = true;
        continue;
    }

    $products[] = $product;
}

$count = (int) $wp_query->found_posts - ($in_query ? 1 : 0) + ($bundle !== null ? 1 : 0);

get_template_part('template-parts/shop/intro');
?>

<?php if (wc_notice_count() > 0) : ?>
    <div class="mx-auto max-w-7xl px-4 pt-8">
        <?php woocommerce_output_all_notices(); ?>
    </div>
<?php endif; ?>

<?php if ($bundle !== null) : ?>
    <div class="mx-auto max-w-7xl px-4 pt-10">
        <?php get_template_part('template-parts/product/bundle-banner', null, ['product' => $bundle, 'variant' => 'shop']); ?>
    </div>
<?php endif; ?>

<?php get_template_part('template-parts/shop/toolbar', null, ['count' => $count]); ?>

<?php // The banner can be the only Product (the bundle archive): no empty grid under it. ?>
<?php if ($products !== [] || $count === 0 || $wp_query->max_num_pages > 1) : ?>
<section class="mx-auto max-w-7xl px-4 pt-6 pb-20">
    <?php if ($products !== []) : ?>
        <h2 class="screen-reader-text"><?php esc_html_e('Products', 'optimum-lift'); ?></h2>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($products as $product) : ?>
                <?php get_template_part('template-parts/product/card', null, ['product' => $product, 'variant' => 'shop']); ?>
            <?php endforeach; ?>
        </div>
    <?php elseif ($count === 0) : ?>
        <?php get_template_part('template-parts/shop/empty'); ?>
    <?php endif; ?>

    <?php get_template_part('template-parts/shop/pagination'); ?>
</section>
<?php else : ?>
<div class="pb-10" aria-hidden="true"></div>
<?php endif; ?>

<?php
get_template_part('template-parts/shop/guarantee');
