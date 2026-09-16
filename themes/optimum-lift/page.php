<?php
/**
 * A static page. The WooCommerce cart, checkout and account pages are pages
 * too, but they render through woocommerce.php rather than this file.
 */

get_header();

while (have_posts()) :
    the_post();
    ?>
    <main id="main" class="site-main">
        <article <?php post_class('entry entry--page'); ?>>
            <h1 class="entry__title"><?php the_title(); ?></h1>

            <div class="entry__content prose">
                <?php
                the_content();
                wp_link_pages(['before' => '<nav class="page-links">', 'after' => '</nav>']);
                ?>
            </div>
        </article>
    </main>
    <?php
endwhile;

get_footer();
