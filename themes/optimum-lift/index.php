<?php
/**
 * Fallback template. Every request WordPress cannot match to a more specific
 * template lands here.
 */

get_header();
?>

<main id="main" class="site-main">
    <?php if (have_posts()) : ?>
        <?php if (!is_front_page()) : ?>
            <h1 class="page-title"><?php echo esc_html(optimum_lift_page_title()); ?></h1>
        <?php endif; ?>

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
