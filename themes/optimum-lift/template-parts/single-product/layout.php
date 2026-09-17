<?php

/**
 * The single Product page: the hero with the price box, the Product's section
 * blocks (or, without blocks, its description and a guarantee band), the
 * cross-sells and the sticky buy bar.
 *
 * woocommerce.php runs the loop and passes the Product. WooCommerce's
 * single-product hooks do not run on this page, so this part does what they
 * would: the password form, the notices (in the hero) and the Product's
 * structured data, which WooCommerce prints in the footer.
 */

declare(strict_types=1);

/** @var array{product: WC_Product} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

if (post_password_required()) {
    echo '<div class="prose-ol mx-auto max-w-xl px-4 py-16">' . get_the_password_form() . '</div>';
    return;
}

WC()->structured_data->generate_product_data($product);

$blocks         = optimum_lift_blocks($product->get_id());
$reviews_anchor = '';
foreach ($blocks as $block) {
    if ($block['acf_fc_layout'] === 'reviews') {
        // The same fallback the reviews block gives its section id.
        $reviews_anchor = optimum_lift_block_id($block, 'reviews');
        break;
    }
}

get_template_part('template-parts/single-product/hero', null, [
    'product'        => $product,
    'reviews_anchor' => $reviews_anchor,
]);

if ($blocks !== []) {
    optimum_lift_render_blocks($product->get_id(), $product);
} else {
    get_template_part('template-parts/single-product/fallback', null, ['product' => $product]);
}

get_template_part('template-parts/single-product/cross-sells', null, ['product' => $product]);
get_template_part('template-parts/single-product/buy-bar', null, ['product' => $product]);

$view_item = [
    'id'       => $product->get_id(),
    'name'     => optimum_lift_plain_text($product->get_name()),
    'price'    => optimum_lift_current_price($product),
    'currency' => get_woocommerce_currency(),
];
?>
<script type="application/json" data-ol-track="view_item"><?php echo wp_json_encode($view_item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
