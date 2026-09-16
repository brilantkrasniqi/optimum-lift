<?php
/**
 * Search results. Products appear here too, unless the query is restricted to
 * a single post type.
 */

get_header();
?>

<main id="main" class="site-main">
    <header class="archive-header">
        <h1 class="page-title"><?php echo esc_html(optimum_lift_page_title()); ?></h1>
        <?php get_search_form(); ?>
    </header>

    <?php if (have_posts()) : ?>
        <div class="post-list">
            <?php
            while (have_posts()) {
                the_post();
                get_template_part('template-parts/content', 'search');
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
