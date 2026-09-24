<?php

/**
 * The homepage: the front page's section stack (ol_blocks) with the bundle as
 * the context Product, then the mobile sticky call to action and the
 * exit-intent offer, which both lead to the first pricing section. A front
 * page without sections shows its content.
 *
 * WordPress also picks this file when the front page lists posts; that view
 * stays the blog index.
 */

declare(strict_types=1);

if (get_option('show_on_front') !== 'page') {
    get_template_part('index');
    return;
}

$post_id = (int) get_queried_object_id();
$blocks  = function_exists('optimum_lift_blocks') ? optimum_lift_blocks($post_id) : [];
$bundle  = $blocks !== [] ? optimum_lift_find_bundle() : null;

$pricing_anchor = '';
foreach ($blocks as $block) {
    if ($block['acf_fc_layout'] === 'pricing') {
        $pricing_anchor = optimum_lift_block_id($block, 'pricing');
        break;
    }
}

// The sticky bar shows the bundle's price; the footer keeps room for it on
// mobile only when it is there.
if ($bundle === null) {
    add_filter('optimum_lift_has_sticky_bar', '__return_false');
}

get_header();
?>

<main id="main" tabindex="-1">
    <?php if (function_exists('wc_notice_count') && wc_notice_count() > 0) : ?>
        <?php // A no-JS add to cart from a homepage card lands here with its notice. ?>
        <div class="mx-auto max-w-7xl px-4 pt-6"><?php woocommerce_output_all_notices(); ?></div>
    <?php endif; ?>
    <?php if ($blocks !== []) : ?>
        <?php optimum_lift_render_blocks($post_id, $bundle); ?>
    <?php else : ?>
        <?php while (have_posts()) : ?>
            <?php the_post(); ?>
            <article class="mx-auto max-w-3xl px-4 py-16 md:py-24">
                <h1 class="h-display text-4xl text-white sm:text-5xl"><?php the_title(); ?></h1>
                <div class="prose-ol mt-8">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    <?php endif; ?>
</main>

<?php
if ($bundle !== null) {
    get_template_part('template-parts/home/sticky-cta', null, ['bundle' => $bundle, 'anchor' => $pricing_anchor]);
}

if ($blocks !== []) {
    get_template_part('template-parts/home/exit-modal', null, ['anchor' => $pricing_anchor]);
}

get_footer();
