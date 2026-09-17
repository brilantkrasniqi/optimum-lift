<?php
/**
 * The single template WooCommerce renders its own views through: a single
 * Product and the Product archives (shop, categories, tags). Cart, checkout
 * and account are pages and render through page.php.
 *
 * Because this file exists, WooCommerce does not load its archive-product.php
 * or single-product.php wrappers. Each view is a theme template part; while a
 * part is missing, woocommerce_content() renders WooCommerce's default loop.
 */

declare(strict_types=1);

get_header();

if (is_product()) {
    $part = 'template-parts/single-product/layout';
} elseif (is_shop() || is_product_taxonomy()) {
    $part = 'template-parts/shop/archive';
} else {
    $part = '';
}
?>

<main id="main">
    <?php if ($part !== '' && locate_template($part . '.php') !== '') : ?>
        <?php
        if (is_product()) {
            // The Product loop runs here, as in WooCommerce's single-product.php,
            // so the part gets the global $post and $product set up.
            while (have_posts()) {
                the_post();

                $product = wc_get_product(get_the_ID());
                if ($product instanceof WC_Product) {
                    get_template_part($part, null, ['product' => $product]);
                }
            }
        } else {
            get_template_part($part);
        }
        ?>
    <?php else : ?>
        <div class="mx-auto max-w-7xl px-4 py-12">
            <?php
            woocommerce_breadcrumb();
            woocommerce_content();
            ?>
        </div>
    <?php endif; ?>
</main>

<?php
get_footer();
