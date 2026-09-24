<?php

/**
 * Social proof from real data only (ADR-0008). Each helper returns null while
 * the real number is too weak to help; the thresholds are filterable.
 *
 * Store-wide numbers are cached together in one transient that is dropped
 * whenever an order, a Product or a Product review changes.
 */

declare(strict_types=1);

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * A cached proof value. Entries expire on their own TTL; the whole store is
 * flushed by optimum_lift_flush_proof().
 *
 * @template T
 * @param callable(): T $compute
 * @return T
 */
function optimum_lift_proof_cached(string $name, int $ttl, callable $compute): mixed
{
    $store = get_transient('optimum_lift_proof_v1');
    $store = is_array($store) ? $store : [];
    $entry = $store[$name] ?? null;

    if (is_array($entry) && array_key_exists('value', $entry) && ($entry['expires'] ?? 0) > time()) {
        return $entry['value'];
    }

    $value        = $compute();
    $store[$name] = ['value' => $value, 'expires' => time() + $ttl];
    set_transient('optimum_lift_proof_v1', $store, DAY_IN_SECONDS);

    return $value;
}

function optimum_lift_flush_proof(): void
{
    delete_transient('optimum_lift_proof_v1');
}

add_action('woocommerce_order_status_changed', 'optimum_lift_flush_proof');
add_action('save_post_product', 'optimum_lift_flush_proof');
// A Product saved in code with only meta changes (sales, rating counts) skips save_post_product.
add_action('woocommerce_new_product', 'optimum_lift_flush_proof');
add_action('woocommerce_update_product', 'optimum_lift_flush_proof');
add_action('woocommerce_trash_product', 'optimum_lift_flush_proof');
add_action('woocommerce_delete_product', 'optimum_lift_flush_proof');

add_action('comment_post', static function (int $comment_id): void {
    $comment = get_comment($comment_id);
    if ($comment instanceof WP_Comment && get_post_type((int) $comment->comment_post_ID) === 'product') {
        optimum_lift_flush_proof();
    }
});

add_action('wp_update_comment_count', static function (int $post_id): void {
    if (get_post_type($post_id) === 'product') {
        optimum_lift_flush_proof();
    }
});

/**
 * The Product's native rating, from at least 3 reviews.
 *
 * @return array{average: float, count: int}|null
 */
function optimum_lift_rating(WC_Product $p): ?array
{
    $count = $p->get_review_count();
    if ($count < (int) apply_filters('optimum_lift_min_reviews', 3)) {
        return null;
    }

    return ['average' => (float) $p->get_average_rating(), 'count' => $count];
}

/**
 * The review-weighted rating across every published Product.
 *
 * @return array{average: float, count: int}|null
 */
function optimum_lift_store_rating(): ?array
{
    $totals = optimum_lift_proof_cached('store_rating', 12 * HOUR_IN_SECONDS, static function (): array {
        $sum   = 0.0;
        $count = 0;
        foreach (optimum_lift_query_products() as $product) {
            $sum   += (float) $product->get_average_rating() * $product->get_review_count();
            $count += $product->get_review_count();
        }

        return ['sum' => $sum, 'count' => $count];
    });

    if ($totals['count'] < (int) apply_filters('optimum_lift_min_reviews', 3)) {
        return null;
    }

    return ['average' => round($totals['sum'] / $totals['count'], 2), 'count' => $totals['count']];
}

/**
 * "N të shitura", from total_sales, once there are at least 25.
 */
function optimum_lift_sold_count(WC_Product $p): ?int
{
    $sold = $p->get_total_sales();

    return $sold >= (int) apply_filters('optimum_lift_min_sold', 25) ? $sold : null;
}

/**
 * The Customizer baseline (the physical plans sold before the shop) plus paid
 * online orders.
 */
function optimum_lift_customer_count(): int
{
    return max(0, (int) optimum_lift_setting('customers_baseline')) + optimum_lift_paid_orders_count();
}

/**
 * Paid orders, optionally only those created after a timestamp.
 */
function optimum_lift_paid_orders_count(?int $since = null): int
{
    $count = static function () use ($since): int {
        $args = [
            'type'     => 'shop_order',
            'status'   => wc_get_is_paid_statuses(),
            'limit'    => 1,
            'paginate' => true,
            'return'   => 'ids',
        ];
        if ($since !== null) {
            $args['date_created'] = '>' . $since;
        }

        $result = wc_get_orders($args);

        return $result instanceof stdClass ? (int) $result->total : 0;
    };

    return $since === null ? optimum_lift_proof_cached('paid_orders', 12 * HOUR_IN_SECONDS, $count) : $count();
}

/**
 * A number as plain text with the store's price separators ("12.400", "4,8"),
 * so counts and ratings read like the prices beside them. number_format_i18n()
 * would follow the WordPress locale instead, which for sq is an HTML entity.
 */
function optimum_lift_format_number(float $n, int $decimals = 0): string
{
    return number_format($n, max(0, $decimals), wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
}

/**
 * "600+": rounded down to a round number, never up. Below 100 to the ten, below
 * 1,000 to the fifty, above that to the hundred.
 */
function optimum_lift_format_count_plus(int $n): string
{
    $n    = max(0, $n);
    $step = match (true) {
        $n < 100  => 10,
        $n < 1000 => 50,
        default   => 100,
    };
    $floored = intdiv($n, $step) * $step;

    return optimum_lift_format_number($floored > 0 ? $floored : $n) . '+';
}

/**
 * Paid orders in the last N hours, once there are at least 10.
 */
function optimum_lift_recent_orders_count(int $hours = 24): ?int
{
    $hours = max(1, $hours);
    $count = optimum_lift_proof_cached(
        'recent_orders_' . $hours,
        15 * MINUTE_IN_SECONDS,
        static fn (): int => optimum_lift_paid_orders_count(time() - $hours * HOUR_IN_SECONDS)
    );

    return $count >= (int) apply_filters('optimum_lift_min_recent_orders', 10) ? $count : null;
}

/**
 * The computed badge. A bundle cheaper than its parts is "Best value"; the
 * best-selling non-bundle Product (at least 10 sales) is "Best seller";
 * a Product published in the last 30 days is "New".
 *
 * @return array{label: string, tone: string}|null
 */
function optimum_lift_badge(WC_Product $p): ?array
{
    if (optimum_lift_is_bundle($p) && optimum_lift_saving($p) !== null) {
        return ['label' => __('Best value', 'optimum-lift'), 'tone' => 'accent'];
    }

    if ($p->get_id() === optimum_lift_bestseller_id()) {
        return ['label' => __('Best seller', 'optimum-lift'), 'tone' => 'accent'];
    }

    $created  = $p->get_date_created();
    $new_days = (int) apply_filters('optimum_lift_new_days', 30);
    if ($created !== null && $created->getTimestamp() > time() - $new_days * DAY_IN_SECONDS) {
        return ['label' => __('New', 'optimum-lift'), 'tone' => 'acid'];
    }

    return null;
}

/**
 * The ID of the non-bundle Product with the most sales, or 0 below the
 * threshold. Ties go to the first in menu order.
 */
function optimum_lift_bestseller_id(): int
{
    $best = optimum_lift_proof_cached('bestseller', 12 * HOUR_IN_SECONDS, static function (): array {
        $best = ['id' => 0, 'sales' => 0];
        foreach (optimum_lift_query_products() as $product) {
            if (!optimum_lift_is_bundle($product) && $product->get_total_sales() > $best['sales']) {
                $best = ['id' => $product->get_id(), 'sales' => $product->get_total_sales()];
            }
        }

        return $best;
    });

    return $best['sales'] >= (int) apply_filters('optimum_lift_min_bestseller_sales', 10) ? $best['id'] : 0;
}

/**
 * "X nga 10 klientë e zgjedhin": the share of paid orders that contain the
 * bundle, in tenths rounded down, once there are at least 20 paid orders and
 * the share is at least 5 in 10.
 */
function optimum_lift_bundle_share(WC_Product $bundle): ?int
{
    $orders = optimum_lift_paid_orders_count();
    if ($orders <= 0 || $orders < (int) apply_filters('optimum_lift_min_bundle_orders', 20)) {
        return null;
    }

    $with_bundle = optimum_lift_proof_cached(
        'bundle_orders_' . $bundle->get_id(),
        12 * HOUR_IN_SECONDS,
        static fn (): int => optimum_lift_paid_orders_with_product($bundle->get_id())
    );
    $share = intdiv(min($with_bundle, $orders) * 10, $orders);

    return $share >= (int) apply_filters('optimum_lift_min_bundle_share', 5) ? $share : null;
}

/**
 * Paid orders with a line for the Product. Order line items live in the same
 * tables under both order storages; only the orders table differs.
 */
function optimum_lift_paid_orders_with_product(int $product_id): int
{
    global $wpdb;

    if (OrderUtil::custom_orders_table_usage_is_enabled()) {
        $orders = OrderUtil::get_table_for_orders();
        $id     = 'id';
        $status = 'status';
        $type   = 'type';
    } else {
        $orders = $wpdb->posts;
        $id     = 'ID';
        $status = 'post_status';
        $type   = 'post_type';
    }

    $statuses     = array_map(static fn (string $s): string => 'wc-' . $s, wc_get_is_paid_statuses());
    $placeholders = implode(', ', array_fill(0, count($statuses), '%s'));

    $sql = "SELECT COUNT(DISTINCT items.order_id)
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta
            ON meta.order_item_id = items.order_item_id AND meta.meta_key = '_product_id'
        INNER JOIN {$orders} AS orders ON orders.{$id} = items.order_id
        WHERE items.order_item_type = 'line_item' AND meta.meta_value = %d
            AND orders.{$type} = 'shop_order' AND orders.{$status} IN ({$placeholders})";

    return (int) $wpdb->get_var($wpdb->prepare($sql, $product_id, ...$statuses));
}

function optimum_lift_format_rating(float $avg): string
{
    return optimum_lift_format_number($avg, 1);
}
