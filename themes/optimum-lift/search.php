<?php

/**
 * Search results, across every public post type, Products included.
 */

declare(strict_types=1);

global $wp_query;

$found = (int) $wp_query->found_posts;

get_header();
?>

<main id="main" tabindex="-1">
    <?php
    get_template_part('template-parts/content-intro', null, [
        'title'  => optimum_lift_page_title(),
        'width'  => 'narrow',
        /* translators: %s: number of search results. */
        'lead'   => $found > 0 ? sprintf(_n('%s result', '%s results', $found, 'optimum-lift'), number_format_i18n($found)) : '',
        'search' => true,
    ]);
    get_template_part('template-parts/content-loop', null, ['variant' => 'search']);
    get_template_part('template-parts/content-shop-cta');
    ?>
</main>

<?php
get_footer();
