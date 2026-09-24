<?php

/**
 * The drawer's one upsell (spec "Cart (09)", ADR-0007). The decision is made
 * here, on the server, from the real cart lines, so the drawer never offers
 * something the cart already covers and never computes a price in JavaScript.
 */

declare(strict_types=1);

/**
 * The best-selling published Product of a kind, ties going to the first in
 * menu order; null when the kind has no Products.
 */
function optimum_lift_bestseller_of_kind(string $kind): ?WC_Product
{
    $slug = optimum_lift_kind_slugs()[$kind] ?? null;
    if (!is_string($slug) || $slug === '') {
        return null;
    }

    $id = optimum_lift_proof_cached('bestseller_' . $kind, 12 * HOUR_IN_SECONDS, static function () use ($slug): int {
        $best = ['id' => 0, 'sales' => -1];
        foreach (optimum_lift_query_products(['category' => [$slug], 'visibility' => 'catalog']) as $product) {
            if (!optimum_lift_is_bundle($product) && $product->get_total_sales() > $best['sales']) {
                $best = ['id' => $product->get_id(), 'sales' => $product->get_total_sales()];
            }
        }

        return $best['id'];
    });

    $product = $id > 0 ? wc_get_product($id) : null;

    return $product instanceof WC_Product ? $product : null;
}

/**
 * What the drawer offers under the cart lines, walking the ladder in order:
 *
 * 1. a bundle is in the cart: nothing;
 * 2. the bundle's components in the cart cost at least the bundle: `swap`,
 *    amount = the saving;
 * 3. the cart holds one kind but not its complement: `upgrade` when the
 *    cart plus the best complement would cost at least the bundle (amount =
 *    what the bundle adds to the covered lines), else `complement` with it;
 * 4. a bundle exists and is not in the cart: `upgrade`.
 *
 * @return array{type: 'swap'|'upgrade'|'complement', product: WC_Product, amount: float}|null
 */
function optimum_lift_cart_upsell(): ?array
{
    $cart = WC()->cart;
    if ($cart === null || $cart->is_empty()) {
        return null;
    }

    /** @var list<WC_Product> $lines */
    $lines = [];
    foreach ($cart->get_cart() as $item) {
        $product = $item['data'] ?? null;
        if ($product instanceof WC_Product) {
            $lines[] = $product;
        }
    }

    // 1. The cart already has everything a bundle offers.
    foreach ($lines as $line) {
        if (optimum_lift_is_bundle($line)) {
            return null;
        }
    }

    $bundle = null;
    foreach ($lines as $line) {
        $bundle = optimum_lift_find_bundle($line);
        if ($bundle !== null) {
            break;
        }
    }
    $bundle ??= optimum_lift_find_bundle();

    $decimals = wc_get_price_decimals();
    $line_ids = array_map(static fn (WC_Product $p): int => $p->get_id(), $lines);
    $covered  = 0.0;
    $price    = 0.0;

    if ($bundle !== null) {
        $price         = optimum_lift_current_price($bundle);
        $component_ids = array_map(static fn (WC_Product $c): int => $c->get_id(), optimum_lift_bundle_components($bundle));
        foreach ($lines as $line) {
            if (in_array($line->get_id(), $component_ids, true)) {
                $covered += optimum_lift_current_price($line);
            }
        }
        $covered = round($covered, $decimals);

        // 2. The parts already cost at least the whole.
        if ($covered > 0 && $covered >= $price) {
            return ['type' => 'swap', 'product' => $bundle, 'amount' => round($covered - $price, $decimals)];
        }
    }

    // 3. One kind without its complement.
    $kinds           = [];
    $complement_kind = null;
    foreach ($lines as $line) {
        $kind = optimum_lift_product_kind($line);
        if ($kind !== null) {
            $kinds[$kind]    = true;
            $complement_kind = optimum_lift_complement_kind($line);
        }
    }

    if (count($kinds) === 1 && $complement_kind !== null) {
        $complement = optimum_lift_best_complement($lines, $complement_kind, $line_ids);

        if ($complement !== null) {
            $subtotal         = round(array_sum(array_map('optimum_lift_current_price', $lines)), $decimals);
            $complement_price = optimum_lift_current_price($complement);

            if ($bundle !== null && $subtotal + $complement_price >= $price) {
                return ['type' => 'upgrade', 'product' => $bundle, 'amount' => round($price - $covered, $decimals)];
            }

            return ['type' => 'complement', 'product' => $complement, 'amount' => $complement_price];
        }
    }

    // 4. Everything else still has the bundle to gain.
    if ($bundle !== null) {
        return ['type' => 'upgrade', 'product' => $bundle, 'amount' => round(max(0.0, $price - $covered), $decimals)];
    }

    return null;
}

/**
 * The complement to offer: the first Product of that kind in the lines'
 * cross-sells that can be bought and is not in the cart, else the kind's
 * best-seller.
 *
 * @param list<WC_Product> $lines
 * @param list<int>        $line_ids
 */
function optimum_lift_best_complement(array $lines, string $kind, array $line_ids): ?WC_Product
{
    foreach ($lines as $line) {
        foreach ($line->get_cross_sell_ids() as $id) {
            $candidate = wc_get_product((int) $id);
            if (
                $candidate instanceof WC_Product
                && !in_array($candidate->get_id(), $line_ids, true)
                && $candidate->get_status() === 'publish'
                && $candidate->is_purchasable()
                && optimum_lift_product_kind($candidate) === $kind
            ) {
                return $candidate;
            }
        }
    }

    $bestseller = optimum_lift_bestseller_of_kind($kind);

    return $bestseller !== null && !in_array($bestseller->get_id(), $line_ids, true) ? $bestseller : null;
}
