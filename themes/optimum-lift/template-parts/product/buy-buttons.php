<?php

/**
 * "Bli tani" (straight to checkout with only this Product, ADR-0007) above or
 * beside "Shto në shportë" (opens the drawer). Two links with two jobs, never
 * one handler with a flag.
 *
 * `size`: `lg` (Product price box), `md` (bundle banner: large Buy Now, compact
 * add to cart), `sm` (bars). `layout`: `stack` or `row`. `data-cta` ids are
 * `{cta_prefix}-buy-now` and `{cta_prefix}-add`.
 *
 * A Product that needs a Size (sizes.php): with `form` (the Size picker's
 * form id, on the Product page) both are submit buttons for that form; without
 * it, one "Choose your size" link to the picker (`{cta_prefix}-choose-size`).
 */

declare(strict_types=1);

/**
 * @var array{product: WC_Product, layout?: string, size?: string, cta_prefix?: string, buy_label?: string|null, add_label?: string|null, pulse?: bool, class?: string, form?: string} $args
 */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product || !$product->is_purchasable() || !$product->is_in_stock()) {
    return;
}

$size   = $args['size'] ?? 'lg';
$row    = ($args['layout'] ?? 'stack') === 'row';
$prefix = $args['cta_prefix'] ?? 'product';

$buy_class = match ($size) {
    'sm'    => 'btn btn-primary btn-md',
    default => 'btn btn-primary btn-lg gap-2.5 rounded-xl px-5',
};
$add_class = match ($size) {
    'sm'    => 'btn btn-ghost btn-md',
    'md'    => 'btn btn-outline btn-sm min-h-11 px-5',
    default => 'btn btn-ghost btn-md py-3.5 text-[14px]',
};
$width = $row ? ' flex-1' : ' btn-block';

$wrap = [
    $row ? 'flex flex-wrap gap-2' : 'grid',
    $size === 'lg' ? 'gap-2.5' : 'gap-2',
    $args['class'] ?? '',
];

$choose = optimum_lift_needs_choice($product);
$form   = $choose ? ($args['form'] ?? '') : '';
$pulse  = !empty($args['pulse']) ? ' pulse' : '';
$icon   = $size === 'sm' ? 'w-4 h-4 shrink-0' : 'w-5 h-5 shrink-0';
?>
<div class="<?php echo esc_attr(trim(implode(' ', $wrap))); ?>">
<?php if ($choose && $form === '') : ?>
    <a href="<?php echo esc_url($product->get_permalink() . '#blej'); ?>" data-cta="<?php echo esc_attr($prefix . '-choose-size'); ?>" class="<?php echo esc_attr($buy_class . $width); ?>">
        <?php esc_html_e('Choose your size', 'optimum-lift'); ?>
        <?php echo optimum_lift_icon('arrow-right', $icon); ?>
    </a>
<?php elseif ($choose) : ?>
    <button type="submit" form="<?php echo esc_attr($form); ?>" name="ol_buy_now" value="<?php echo esc_attr((string) $product->get_id()); ?>" data-buy-now-submit data-cta="<?php echo esc_attr($prefix . '-buy-now'); ?>" class="<?php echo esc_attr($buy_class . $width . $pulse); ?>">
        <?php echo esc_html($args['buy_label'] ?? __('Buy now', 'optimum-lift')); ?>
        <?php echo optimum_lift_icon('arrow-right', $icon); ?>
    </button>
    <?php
    get_template_part('template-parts/product/add-to-cart', null, [
        'product' => $product,
        'cta'     => $prefix . '-add',
        'class'   => $add_class . $width,
        'label'   => $args['add_label'] ?? null,
        'icon'    => $size !== 'sm',
        'form'    => $form,
    ]);
    ?>
<?php else : ?>
    <a href="<?php echo esc_url(optimum_lift_buy_now_url($product)); ?>" rel="nofollow" data-buy-now="<?php echo esc_attr((string) $product->get_id()); ?>" data-cta="<?php echo esc_attr($prefix . '-buy-now'); ?>" class="<?php echo esc_attr($buy_class . $width . (!empty($args['pulse']) ? ' pulse' : '')); ?>">
        <?php echo esc_html($args['buy_label'] ?? __('Buy now', 'optimum-lift')); ?>
        <?php echo optimum_lift_icon('arrow-right', $size === 'sm' ? 'w-4 h-4 shrink-0' : 'w-5 h-5 shrink-0'); ?>
    </a>
    <?php
    get_template_part('template-parts/product/add-to-cart', null, [
        'product' => $product,
        'cta'     => $prefix . '-add',
        'class'   => $add_class . $width,
        'label'   => $args['add_label'] ?? null,
        'icon'    => $size !== 'sm',
    ]);
    ?>
<?php endif; ?>
</div>
