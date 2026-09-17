<?php

/**
 * Post archives: categories, tags, authors, dates. Product archives never
 * reach this file; WooCommerce routes those through woocommerce.php.
 */

declare(strict_types=1);

get_header();
?>

<main id="main">
    <?php
    get_template_part('template-parts/content-intro', null, [
        'title'     => optimum_lift_page_title(),
        'eyebrow'   => __('Blog', 'optimum-lift'),
        'lead_html' => get_the_archive_description(),
    ]);
    get_template_part('template-parts/content-loop');
    get_template_part('template-parts/content-shop-cta');
    ?>
</main>

<?php
get_footer();
