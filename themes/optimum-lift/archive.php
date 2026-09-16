<?php
/**
 * Post-type and taxonomy archives. Product archives never reach this file --
 * WooCommerce routes those through woocommerce.php.
 */

get_header();
?>

<main id="main" class="site-main">
    <?php if (have_posts()) : ?>
        <header class="archive-header">
            <h1 class="page-title"><?php echo esc_html(optimum_lift_page_title()); ?></h1>
            <?php the_archive_description('<div class="archive-header__description">', '</div>'); ?>
        </header>

        <div class="post-list">
            <?php
            while (have_posts()) {
                the_post();
                get_template_part('template-parts/content', get_post_type());
            }
            ?>
        </div>

        <?php the_posts_pagination(); ?>
    <?php else : ?>
        <?php get_template_part('template-parts/content', 'none'); ?>
    <?php endif; ?>
</main>

<?php
get_footer();
