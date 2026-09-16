<?php
get_header();
?>

<main id="main" class="site-main">
    <h1 class="page-title"><?php echo esc_html(optimum_lift_page_title()); ?></h1>

    <p><?php esc_html_e('That page does not exist. Try a search, or head back to the shop.', 'optimum-lift'); ?></p>

    <?php get_search_form(); ?>

    <?php if (function_exists('wc_get_page_id')) : ?>
        <p><a class="button" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">
            <?php esc_html_e('Browse plans', 'optimum-lift'); ?>
        </a></p>
    <?php endif; ?>
</main>

<?php
get_footer();
