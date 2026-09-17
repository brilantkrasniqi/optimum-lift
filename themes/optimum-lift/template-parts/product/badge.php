<?php

/**
 * The computed badge pill ("Më i shituri", "I ri", "Vlera më e mirë"). Nothing
 * renders when the Product has not earned one (ADR-0006, ADR-0008).
 *
 * `size` is `sm` (cards) or `md` (gallery); `class` positions it.
 */

declare(strict_types=1);

/** @var array{product: WC_Product, size?: string, class?: string} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$badge = optimum_lift_badge($product);
if ($badge === null) {
    return;
}

$classes = [
    'rounded-full font-extrabold uppercase tracking-widest',
    $badge['tone'] === 'acid' ? 'bg-acid text-paper' : 'bg-accent text-white',
    ($args['size'] ?? 'sm') === 'md' ? 'px-3 py-1 text-[10px]' : 'px-2.5 py-1 text-[9.5px]',
    $args['class'] ?? '',
];
?>
<span class="<?php echo esc_attr(trim(implode(' ', $classes))); ?>"><?php echo esc_html($badge['label']); ?></span>
