<?php

/**
 * The icon that stands for a Product's kind: a barbell for a training
 * program, a bowl for a nutrition plan, a bolt for a bundle. `attrs` are
 * passed to optimum_lift_icon() (e.g. a thinner stroke at large sizes).
 */

declare(strict_types=1);

/** @var array{product: WC_Product, class?: string, attrs?: array<string, string>} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$icons = [
    'program' => 'barbell',
    'diet'    => 'bowl',
    'bundle'  => 'bolt',
];

echo optimum_lift_icon(
    $icons[optimum_lift_product_kind($product) ?? ''] ?? 'barbell',
    $args['class'] ?? 'w-4 h-4',
    $args['attrs'] ?? []
);
