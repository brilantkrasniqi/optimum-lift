<?php

/**
 * The running offer behind the urgency bar and countdowns. There is an offer
 * only while a real end date is in the future (ADR-0008): a Product's
 * scheduled sale end, else the site offer end set in the Customizer.
 */

declare(strict_types=1);

/**
 * The offer for a Product, or the site-wide one without a Product.
 *
 * - scope 'product': $p is on sale with a sale end date in the future.
 * - scope 'site': the site offer end is in the future and, when $p is given,
 *   $p is on sale.
 *
 * Without a Product, there is a site offer only while at least one published
 * Product is on sale. A Product that is not on sale itself has no offer, even while the site
 * offer runs. That includes a bundle priced without a sale: its price does not
 * rise when the offer ends (its saving against the components grows), so a
 * countdown next to it would be false urgency.
 *
 * ends_at is a UTC timestamp. percent is $p's saving, or without $p the
 * highest saving in the catalogue (see optimum_lift_best_sale_percent()).
 * label is display-ready plain text, the Customizer offer label followed by
 * the percent ("Launch offer · up to −50%"): print it escaped and do not add
 * the percent again.
 *
 * @return array{ends_at: int, label: string, percent: ?int, scope: string}|null
 */
function optimum_lift_offer(?WC_Product $p = null): ?array
{
    $now     = time();
    $ends_at = optimum_lift_site_offer_end();

    if ($p === null) {
        // With no Product on sale the site date ends nothing: no price rises
        // when it passes (a bundle's saving against its components is
        // permanent), so there is no offer to count down to.
        return $ends_at !== null && $ends_at > $now && optimum_lift_sale_running()
            ? optimum_lift_offer_shape($ends_at, 'site', optimum_lift_best_sale_percent(), true)
            : null;
    }

    if (!$p->is_on_sale()) {
        return null;
    }

    $percent  = optimum_lift_saving($p)['percent'] ?? null;
    $sale_end = $p->get_date_on_sale_to();
    if ($sale_end !== null && $sale_end->getTimestamp() > $now) {
        return optimum_lift_offer_shape($sale_end->getTimestamp(), 'product', $percent, false);
    }

    return $ends_at !== null && $ends_at > $now
        ? optimum_lift_offer_shape($ends_at, 'site', $percent, false)
        : null;
}

/**
 * @return array{ends_at: int, label: string, percent: ?int, scope: string}
 */
function optimum_lift_offer_shape(int $ends_at, string $scope, ?int $percent, bool $up_to): array
{
    $label = trim((string) optimum_lift_shop_setting('offer_label'));

    if ($percent !== null && $percent > 0) {
        $amount = $up_to
            /* translators: %s: the highest discount, a number. */
            ? sprintf(__('up to −%s%%', 'optimum-lift'), optimum_lift_format_number($percent))
            /* translators: %s: the discount, a number. */
            : sprintf(__('−%s%%', 'optimum-lift'), optimum_lift_format_number($percent));
        $label = $label !== '' ? $label . ' · ' . $amount : $amount;
    }

    return [
        'ends_at' => $ends_at,
        'label'   => $label,
        'percent' => $percent,
        'scope'   => $scope,
    ];
}

/**
 * The Customizer offer end (datetime-local, site timezone) as a UTC timestamp.
 * Only the Customizer's own format is accepted: a relative value such as
 * "+2 days" would move with every request and never end.
 */
function optimum_lift_site_offer_end(): ?int
{
    $value = optimum_lift_shop_setting('offer_ends_at');
    if (!is_string($value) || trim($value) === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', substr(trim($value), 0, 16), wp_timezone());

    return $date instanceof DateTimeImmutable ? $date->getTimestamp() : null;
}

/**
 * Whether any published Product is on sale right now.
 */
function optimum_lift_sale_running(): bool
{
    $cache_key = 'sale_running:' . wp_cache_get_last_changed('posts');
    $cached    = wp_cache_get($cache_key, 'optimum_lift', false, $found);
    if ($found) {
        return (bool) $cached;
    }

    $running = false;
    foreach (optimum_lift_query_products() as $product) {
        if ($product->is_on_sale()) {
            $running = true;
            break;
        }
    }

    wp_cache_set($cache_key, $running, 'optimum_lift', 5 * MINUTE_IN_SECONDS);

    return $running;
}

/**
 * The highest saving percent among published Products: sale prices, and the
 * saving of a bundle against its components, which is what the bundle banner
 * next to the offer bar shows.
 */
function optimum_lift_best_sale_percent(): ?int
{
    // Sales start and end with the clock as well as with edits, so the cache is short.
    $cache_key = 'best_sale_percent:' . wp_cache_get_last_changed('posts');
    $cached    = wp_cache_get($cache_key, 'optimum_lift', false, $found);
    if ($found) {
        return is_int($cached) ? $cached : null;
    }

    $best = null;
    foreach (optimum_lift_query_products() as $product) {
        $saving = optimum_lift_saving($product);
        if ($saving !== null && ($best === null || $saving['percent'] > $best)) {
            $best = $saving['percent'];
        }
    }

    wp_cache_set($cache_key, $best, 'optimum_lift', 5 * MINUTE_IN_SECONDS);

    return $best;
}
