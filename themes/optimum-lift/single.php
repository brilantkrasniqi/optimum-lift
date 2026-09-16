<?php
/**
 * A single blog post.
 */

get_header();

while (have_posts()) :
    the_post();
    ?>
    <main id="main" class="site-main">
        <article <?php post_class('entry entry--single'); ?>>
            <header class="entry__header">
                <h1 class="entry__title"><?php the_title(); ?></h1>
                <?php optimum_lift_entry_meta(); ?>
            </header>

            <?php if (has_post_thumbnail()) : ?>
                <figure class="entry__media"><?php the_post_thumbnail('large'); ?></figure>
            <?php endif; ?>

            <div class="entry__content">
                <?php
                the_content();
                wp_link_pages(['before' => '<nav class="page-links">', 'after' => '</nav>']);
                ?>
            </div>
        </article>

        <?php
        the_post_navigation(['class' => 'post-nav']);

        if (comments_open() || get_comments_number()) {
            comments_template();
        }
        ?>
    </main>
    <?php
endwhile;

get_footer();
