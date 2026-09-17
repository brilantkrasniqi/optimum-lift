<?php

/**
 * The Product gallery: the featured image, then the gallery images, with the
 * computed badge and the media label on the main image. The main image is the
 * page's largest paint, so it loads eagerly with high priority.
 *
 * Thumbnails (modules/gallery.js) swap the main image, srcset included, and
 * only show with JavaScript and more than one image. Without images the main
 * tile is the kind-icon placeholder and there are no thumbnails: a thumbnail
 * that switches nothing is a dead control.
 */

declare(strict_types=1);

/** @var array{product: WC_Product} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$image_ids = array_values(array_filter(
    array_unique(array_map('intval', array_merge([$product->get_image_id()], $product->get_gallery_image_ids()))),
    static fn (int $id): bool => $id > 0 && wp_get_attachment_image_url($id, 'woocommerce_single') !== false
));

$name  = optimum_lift_plain_text($product->get_name());
$sizes = '(min-width: 1280px) 560px, (min-width: 1024px) 45vw, calc(100vw - 2rem)';
$label = optimum_lift_field($product->get_id(), 'ol_media_label');
$label = is_string($label) ? trim($label) : '';

$alt = static function (int $id) use ($name): string {
    $text = trim(wp_strip_all_tags((string) get_post_meta($id, '_wp_attachment_image_alt', true)));

    return $text !== '' ? $text : $name;
};
?>
<div data-gallery>
    <div class="relative aspect-[4/3] overflow-hidden rounded-3xl border border-white/10">
        <?php if ($image_ids === []) : ?>
            <?php get_template_part('template-parts/product/thumb', null, ['product' => $product, 'eager' => true, 'icon_size' => 'w-20 h-20']); ?>
        <?php else : ?>
            <?php
            echo wp_get_attachment_image($image_ids[0], 'woocommerce_single', false, [
                'class'              => 'absolute inset-0 h-full w-full object-cover',
                'alt'                => $alt($image_ids[0]),
                'sizes'              => $sizes,
                'loading'            => 'eager',
                'fetchpriority'      => 'high',
                'data-gallery-image' => '',
            ]);
            ?>
        <?php endif; ?>

        <?php get_template_part('template-parts/product/badge', null, ['product' => $product, 'size' => 'md', 'class' => 'absolute top-4 left-4']); ?>

        <?php if ($label !== '') : ?>
            <span class="absolute bottom-4 left-4 rounded-full bg-black/70 px-3 py-1 text-[10px] font-extrabold uppercase tracking-widest text-zinc-300 backdrop-blur"><?php echo esc_html($label); ?></span>
        <?php endif; ?>
    </div>

    <?php if (count($image_ids) > 1) : ?>
        <ul class="mt-3 hidden grid-cols-4 gap-3 js:grid" aria-label="<?php esc_attr_e('Product images', 'optimum-lift'); ?>">
            <?php foreach ($image_ids as $index => $image_id) : ?>
                <li>
                    <button type="button" data-gallery-thumb data-src="<?php echo esc_url((string) wp_get_attachment_image_url($image_id, 'woocommerce_single')); ?>" data-srcset="<?php echo esc_attr((string) wp_get_attachment_image_srcset($image_id, 'woocommerce_single')); ?>" data-alt="<?php echo esc_attr($alt($image_id)); ?>" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>" class="relative block aspect-square w-full overflow-hidden rounded-xl border border-white/10 transition hover:border-white/30 aria-pressed:ring-2 aria-pressed:ring-accent aria-pressed:ring-offset-2 aria-pressed:ring-offset-paper">
                        <?php
                        echo wp_get_attachment_image($image_id, 'woocommerce_gallery_thumbnail', false, [
                            'class'   => 'absolute inset-0 h-full w-full object-cover',
                            'alt'     => '',
                            'sizes'   => '(min-width: 1024px) 140px, 25vw',
                            'loading' => 'lazy',
                        ]);
                        ?>
                        <span class="screen-reader-text"><?php
                            /* translators: 1: image number, 2: number of images. */
                            echo esc_html(sprintf(__('Show image %1$d of %2$d', 'optimum-lift'), $index + 1, count($image_ids)));
                        ?></span>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
