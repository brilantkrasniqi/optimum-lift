<?php

/**
 * The link from a Product to the bundle that contains it, priced: the
 * Product's own lead-in (`ol_bundle_hint`), then what the bundle holds and
 * what it costs. "Every program and meal plan" is said only while it is true;
 * otherwise the sentence counts the other Products inside. Not shown on a
 * bundle, nor for a Product that no bundle contains.
 */

declare(strict_types=1);

/** @var array{product: WC_Product} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product || optimum_lift_is_bundle($product)) {
    return;
}

$bundle = optimum_lift_find_bundle($product);
if ($bundle === null) {
    return;
}

$included = array_map(static fn (WC_Product $p): int => $p->get_id(), optimum_lift_bundle_components($bundle));
$others   = count($included) - 1;
if ($others < 1) {
    return;
}

$left_out = array_filter(
    optimum_lift_query_products(['visibility' => 'catalog']),
    static fn (WC_Product $p): bool => !optimum_lift_is_bundle($p) && !in_array($p->get_id(), $included, true)
);

$lead  = optimum_lift_field($product->get_id(), 'ol_bundle_hint');
$lead  = is_string($lead) ? trim($lead) : '';
$name  = '<strong class="text-white">' . esc_html($bundle->get_name()) . '</strong>';
$price = '<strong class="text-acid">' . wp_kses_post(wc_price(optimum_lift_current_price($bundle))) . '</strong>';
?>
<a href="<?php echo esc_url($bundle->get_permalink()); ?>" data-cta="pdp-bundle-hint" class="mt-4 flex items-center gap-3 rounded-2xl border border-acid/25 bg-acid/[.07] p-4 transition hover:bg-acid/[.11]">
    <?php echo optimum_lift_icon('bolt', 'w-5 h-5 shrink-0 text-acid'); ?>
    <span class="text-[12.5px] leading-relaxed text-zinc-300">
        <?php if ($lead !== '') : ?>
            <?php echo esc_html($lead); ?>
        <?php endif; ?>
        <?php
        if ($left_out === []) {
            $sentence = optimum_lift_product_kind($product) === 'diet'
                /* translators: 1: bundle name, 2: bundle price. */
                ? __('%1$s includes every meal plan and training program for %2$s.', 'optimum-lift')
                /* translators: 1: bundle name, 2: bundle price. */
                : __('%1$s includes every training program and meal plan for %2$s.', 'optimum-lift');
            printf(esc_html($sentence), $name, $price);
        } else {
            /* translators: 1: bundle name, 2: number of other Products in the bundle, 3: bundle price. */
            $sentence = _n('%1$s includes this and %2$s more product for %3$s.', '%1$s includes this and %2$s more products for %3$s.', $others, 'optimum-lift');
            printf(esc_html($sentence), $name, esc_html(optimum_lift_format_number($others)), $price);
        }
        ?>
    </span>
    <?php echo optimum_lift_icon('arrow-right', 'w-4 h-4 shrink-0 text-zinc-500'); ?>
</a>
