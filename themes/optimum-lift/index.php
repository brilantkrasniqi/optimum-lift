<?php

/**
 * The blog (the posts page) and the fallback for any request WordPress cannot
 * match to a more specific template.
 */

declare(strict_types=1);

get_header();
?>

<main id="main">
    <?php
    get_template_part('template-parts/content-intro', null, [
        'title'   => is_home() && !is_front_page() ? optimum_lift_page_title() : get_bloginfo('name'),
        'eyebrow' => __('Blog', 'optimum-lift'),
    ]);
    get_template_part('template-parts/content-loop');
    get_template_part('template-parts/content-shop-cta');
    ?>
</main>

<?php
get_footer();
