<?php

/**
 * Page not found: a search, the shop and home, then the featured Products, so
 * a dead link from an ad or an old post still leads somewhere worth buying.
 */

declare(strict_types=1);

$featured = function_exists('optimum_lift_featured_products') ? optimum_lift_featured_products(3) : [];

get_header();
?>

<main id="main" tabindex="-1">
    <section class="relative overflow-hidden border-b border-white/[.07]">
        <div class="pointer-events-none absolute -top-40 left-1/2 h-[40rem] w-[40rem] -translate-x-1/2 rounded-full bg-accent/15 blur-[130px]" aria-hidden="true"></div>
        <div class="grain pointer-events-none absolute inset-0 opacity-40" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-3xl px-4 py-16 text-center md:py-24">
            <p class="h-display text-[7rem] leading-none text-white/[.06] sm:text-[10rem]" aria-hidden="true">404</p>
            <h1 class="-mt-10 h-display text-4xl text-white sm:-mt-14 sm:text-5xl"><?php echo esc_html(optimum_lift_page_title()); ?></h1>
            <p class="mx-auto mt-4 max-w-md text-[15px] leading-relaxed text-zinc-400"><?php esc_html_e('That page does not exist or has moved. Try a search, or head back to the shop.', 'optimum-lift'); ?></p>

            <div class="mx-auto mt-8 max-w-md text-left">
                <?php get_search_form(); ?>
            </div>

            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <?php if (function_exists('wc_get_page_permalink')) : ?>
                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" data-cta="404-shop" class="btn btn-primary btn-md min-h-12"><?php esc_html_e('Browse the shop', 'optimum-lift'); ?></a>
                <?php endif; ?>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-ghost btn-md min-h-12"><?php esc_html_e('Go to the homepage', 'optimum-lift'); ?></a>
            </div>
        </div>
    </section>

    <?php if ($featured !== []) : ?>
        <section class="mx-auto max-w-7xl px-4 py-14 md:py-20">
            <h2 class="text-center h-display text-2xl text-white sm:text-3xl"><?php esc_html_e('Start with a plan', 'optimum-lift'); ?></h2>
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($featured as $product) : ?>
                    <?php get_template_part('template-parts/product/card', null, ['product' => $product, 'variant' => 'compact', 'cta_prefix' => '404']); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
get_footer();
