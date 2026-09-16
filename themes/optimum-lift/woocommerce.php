<?php
/**
 * The single template WooCommerce renders every shop view through — the Product
 * archive, a single Product, cart, checkout, and account.
 *
 * Because this file exists, WooCommerce does not load its own archive-product.php
 * or single-product.php wrappers: woocommerce_content() renders the inner loop
 * and this file owns everything around it. That keeps the page chrome in one
 * place instead of spread across copied plugin templates.
 */

declare(strict_types=1);

get_header();
?>

<main id="main" class="site-main site-main--shop">
    <?php
    if (function_exists('woocommerce_breadcrumb')) {
        woocommerce_breadcrumb();
    }

    woocommerce_content();
    ?>
</main>

<?php
get_footer();
