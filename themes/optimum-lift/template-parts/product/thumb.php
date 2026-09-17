<?php

/**
 * A Product's featured image, or the placeholder tile with its kind icon while
 * the Product has no photo.
 *
 * Both fill their positioned parent by default; pass `class` to size them
 * differently. Images lazy-load unless `eager` is set (above the fold).
 */

declare(strict_types=1);

/**
 * @var array{product: WC_Product, size?: string, sizes?: string, class?: string, icon_size?: string, eager?: bool} $args
 */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$class    = $args['class'] ?? 'absolute inset-0 h-full w-full';
$image_id = (int) $product->get_image_id();
$image    = $image_id > 0
    ? wp_get_attachment_image($image_id, $args['size'] ?? 'woocommerce_single', false, [
        'class'   => 'object-cover ' . $class,
        'sizes'   => $args['sizes'] ?? '(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw',
        'loading' => empty($args['eager']) ? 'lazy' : 'eager',
    ])
    : '';

if ($image !== '') {
    echo $image;
    return;
}

$tile = optimum_lift_product_kind($product) === 'diet' ? 'ph-photo ph-photo--acid' : 'ph-photo';
?>
<span class="<?php echo esc_attr($tile . ' grid place-items-center text-zinc-700 ' . $class); ?>" aria-hidden="true">
    <?php
    get_template_part('template-parts/product/kind-icon', null, [
        'product' => $product,
        'class'   => $args['icon_size'] ?? 'w-14 h-14',
        'attrs'   => ['stroke-width' => '1.4'],
    ]);
    ?>
</span>
