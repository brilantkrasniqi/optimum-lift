<?php

/**
 * A static page.
 *
 * Content pages (the legal pages, any page an editor writes) get the intro
 * band and the dark prose column, then the way back to the shop. The
 * WooCommerce cart, checkout and My Account pages are pages too, but their
 * shortcodes print forms and tables with their own styles: those get a wide
 * container, a plain heading and no prose.
 */

declare(strict_types=1);

$store_page = function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page());

get_header();

while (have_posts()) :
    the_post();

    if ($store_page) :
        $account   = is_account_page();
        $signed_in = is_user_logged_in();
        ?>
        <main id="main" class="mx-auto max-w-7xl px-4 pt-8 pb-16 md:pt-12 md:pb-24">
            <?php if (!is_order_received_page()) : ?>
                <?php // The thank-you template's success hero is that page's heading. ?>
                <header class="mb-6 md:mb-8">
                    <?php if ($account && $signed_in && is_wc_endpoint_url()) : ?>
                        <p class="mb-3"><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="eyebrow transition hover:text-white"><?php echo esc_html(get_post_field('post_title', get_the_ID())); ?></a></p>
                    <?php endif; ?>
                    <h1 class="h-display text-3xl break-words text-white sm:text-4xl"><?php echo esc_html(get_the_title()); ?></h1>
                </header>
            <?php endif; ?>

            <?php if ($account && !$signed_in && !is_wc_endpoint_url('lost-password')) : ?>
                <p class="mb-6 flex max-w-2xl gap-3 rounded-2xl border border-acid/25 bg-acid/[.06] p-4 text-[13.5px] leading-relaxed text-zinc-300">
                    <?php echo optimum_lift_icon('lock', 'mt-0.5 w-4 h-4 shrink-0 text-acid'); ?>
                    <span><?php esc_html_e('Bought a plan? Log in with the email you used at checkout. On your first order we emailed you a link to set your password; if you cannot find it, use "Lost your password?".', 'optimum-lift'); ?></span>
                </p>
            <?php endif; ?>

            <?php the_content(); ?>
        </main>
        <?php
    else :
        $legal_pages = array_filter([
            function_exists('wc_terms_and_conditions_page_id') ? wc_terms_and_conditions_page_id() : 0,
            (int) get_option('wp_page_for_privacy_policy'),
            (int) get_option('woocommerce_refund_returns_page_id'),
        ]);
        ?>
        <main id="main">
            <?php
            get_template_part('template-parts/content-intro', null, [
                'title' => get_the_title(),
                'width' => 'narrow',
                /* translators: %s: date the page was last changed. */
                'meta'  => in_array(get_the_ID(), $legal_pages, true) ? sprintf(__('Last updated %s', 'optimum-lift'), get_the_modified_date()) : '',
            ]);
            ?>

            <article <?php post_class('mx-auto max-w-3xl px-4 py-12 md:py-16'); ?>>
                <div class="prose-ol text-[15px] sm:text-base">
                    <?php the_content(); ?>
                </div>

                <?php
                wp_link_pages([
                    'before' => '<nav class="ol-page-links" aria-label="' . esc_attr__('Page', 'optimum-lift') . '">',
                    'after'  => '</nav>',
                ]);

                if (comments_open() || get_comments_number() > 0) {
                    comments_template();
                }
                ?>
            </article>

            <?php get_template_part('template-parts/content-shop-cta'); ?>
        </main>
        <?php
    endif;
endwhile;

get_footer();
