<?php

/**
 * Prices as the storefront shows them. Every amount is a display price
 * (wc_get_price_to_display(), so tax display settings apply), and a saving is
 * always computed, never stored (ADR-0006, ADR-0008).
 */

declare(strict_types=1);

function optimum_lift_current_price(WC_Product $p): float
{
    return (float) wc_get_price_to_display($p);
}

/**
 * The struck-through comparison price. For a bundle, the sum of its
 * components' current prices, so "cheaper together" stays true when a
 * component's price changes; a bundle without components compares with
 * itself and shows no saving. For anything else, the regular price.
 */
function optimum_lift_anchor_price(WC_Product $p): float
{
    if (optimum_lift_is_bundle($p)) {
        $components = optimum_lift_bundle_components($p);
        if ($components === []) {
            return optimum_lift_current_price($p);
        }

        return round(array_sum(array_map('optimum_lift_current_price', $components)), wc_get_price_decimals());
    }

    $regular = $p->get_regular_price();
    if ($regular === '') {
        return optimum_lift_current_price($p);
    }

    return (float) wc_get_price_to_display($p, ['price' => (float) $regular]);
}

/**
 * The saving against the anchor price, or null when there is none.
 *
 * @return array{amount: float, percent: int}|null
 */
function optimum_lift_saving(WC_Product $p): ?array
{
    $anchor = optimum_lift_anchor_price($p);
    $amount = round($anchor - optimum_lift_current_price($p), wc_get_price_decimals());

    if ($amount <= 0 || $anchor <= 0) {
        return null;
    }

    return [
        'amount'  => $amount,
        'percent' => (int) round($amount / $anchor * 100),
    ];
}

/**
 * The current price spread over the Product's duration ("≈ X / javë"), or null
 * without a duration. A bundle without its own duration takes its longest
 * component's.
 */
function optimum_lift_price_per_week(WC_Product $p): ?float
{
    $weeks = (int) optimum_lift_field($p->get_id(), 'ol_duration_weeks');

    if ($weeks <= 0 && optimum_lift_is_bundle($p)) {
        foreach (optimum_lift_bundle_components($p) as $component) {
            $weeks = max($weeks, (int) optimum_lift_field($component->get_id(), 'ol_duration_weeks'));
        }
    }

    $price = optimum_lift_current_price($p);
    if ($weeks <= 0 || $price <= 0) {
        return null;
    }

    return $price / $weeks;
}
