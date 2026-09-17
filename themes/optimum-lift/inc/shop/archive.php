<?php

/**
 * The shop and Product category archives (template-parts/shop/): the main
 * product query, the sort options and the category filter.
 *
 * Everything is server-rendered from WooCommerce's main query. The filter is a
 * set of category links and the sort is WooCommerce's own `orderby` parameter,
 * so every view has a URL and works without JavaScript.
 */

declare(strict_types=1);

/**
 * The sorts the archive offers: WooCommerce `orderby` value => label.
 *
 * @return array<string, string>
 */
function optimum_lift_shop_orderby_options(): array
{
    return [
        'menu_order' => __('Our picks', 'optimum-lift'),
        'popularity' => __('Best sellers', 'optimum-lift'),
        'price'      => __('Price: low to high', 'optimum-lift'),
        'price-desc' => __('Price: high to low', 'optimum-lift'),
    ];
}

/**
 * The sort in the URL when the archive offers it, else ''.
 */
function optimum_lift_shop_requested_orderby(): string
{
    $orderby = $_GET['orderby'] ?? '';

    return is_string($orderby) && array_key_exists($orderby, optimum_lift_shop_orderby_options()) ? $orderby : '';
}

/**
 * The sort the grid is in: the requested one, else the editor's order.
 */
function optimum_lift_shop_orderby(): string
{
    $orderby = optimum_lift_shop_requested_orderby();

    return $orderby !== '' ? $orderby : 'menu_order';
}

/**
 * The category filter: all Products (the shop page), then each kind's category
 * archive. Each link keeps the current sort.
 *
 * @return list<array{label: string, url: string, current: bool}>
 */
function optimum_lift_shop_filters(): array
{
    $orderby = optimum_lift_shop_requested_orderby();
    $keep    = static fn (string $url): string => $orderby !== '' && $orderby !== 'menu_order'
        ? add_query_arg('orderby', $orderby, $url)
        : $url;

    $filters = [[
        'label'   => __('All', 'optimum-lift'),
        'url'     => $keep(wc_get_page_permalink('shop')),
        'current' => is_shop() && !is_search(),
    ]];

    // The pills name the kinds, not the categories: "Plane ushqimore" is the
    // diet archive's title, "Dieta" its short label.
    $labels = [
        'program' => __('Training programs', 'optimum-lift'),
        'diet'    => __('Diets', 'optimum-lift'),
        'bundle'  => __('Bundles', 'optimum-lift'),
    ];
    $slugs = optimum_lift_kind_slugs();

    foreach ($labels as $kind => $label) {
        $term = empty($slugs[$kind]) ? false : get_term_by('slug', $slugs[$kind], 'product_cat');
        $url  = $term instanceof WP_Term ? get_term_link($term) : null;

        if ($term instanceof WP_Term && is_string($url)) {
            $filters[] = [
                'label'   => $label,
                'url'     => $keep($url),
                'current' => is_product_category($term->slug),
            ];
        }
    }

    return $filters;
}

/**
 * The bundle to show as the banner above the grid, or null: only on the shop
 * page and the bundle category, first page only.
 */
function optimum_lift_shop_banner_bundle(): ?WC_Product
{
    $slugs = optimum_lift_kind_slugs();
    $shows = (is_shop() && !is_search()) || (!empty($slugs['bundle']) && is_product_category($slugs['bundle']));

    return $shows && !is_paged() ? optimum_lift_find_bundle() : null;
}

/**
 * The trail above the heading: Home / Shop, then the category and its parents.
 * The last crumb has no URL.
 *
 * @return list<array{label: string, url: string}>
 */
function optimum_lift_shop_breadcrumb(): array
{
    $crumbs = [
        ['label' => _x('Home', 'breadcrumb', 'optimum-lift'), 'url' => home_url('/')],
        ['label' => __('Shop', 'optimum-lift'), 'url' => wc_get_page_permalink('shop')],
    ];

    $term = is_product_taxonomy() ? get_queried_object() : null;
    if ($term instanceof WP_Term) {
        foreach (array_reverse(get_ancestors($term->term_id, $term->taxonomy, 'taxonomy')) as $ancestor_id) {
            $ancestor = get_term($ancestor_id, $term->taxonomy);
            $url      = $ancestor instanceof WP_Term ? get_term_link($ancestor) : null;

            if ($ancestor instanceof WP_Term && is_string($url)) {
                $crumbs[] = ['label' => optimum_lift_plain_text($ancestor->name), 'url' => $url];
            }
        }

        $crumbs[] = ['label' => optimum_lift_plain_text($term->name), 'url' => ''];
    } else {
        $crumbs[1]['url'] = '';
    }

    return $crumbs;
}

/**
 * The archive heading: "All products" on the shop page, the term name on a
 * category, the search title on a Product search. Plain text.
 */
function optimum_lift_shop_title(): string
{
    if (is_search()) {
        return optimum_lift_page_title();
    }

    $term = is_product_taxonomy() ? get_queried_object() : null;

    return $term instanceof WP_Term ? optimum_lift_plain_text($term->name) : __('All products', 'optimum-lift');
}

// The select offers the editor's order as the default sort, whatever the
// WooCommerce setting says, so the page and the select agree.
add_filter('woocommerce_default_catalog_orderby', static fn (): string => 'menu_order');

// The catalogue is 3–8 Products: one page holds all of it, so a filter or a
// sort never splits it across pages.
add_filter('loop_shop_per_page', static fn (): int => 48);

add_action('woocommerce_product_query', static function (WP_Query $query, WC_Query $wc_query): void {
    // A sort the select does not offer (an old ?orderby=rating link) falls
    // back to the default, so the select always names the grid's order.
    if (isset($_GET['orderby']) && optimum_lift_shop_requested_orderby() === '') {
        $wc_query->remove_ordering_args();
        $query->set('orderby', 'menu_order title');
        $query->set('order', 'ASC');
        $query->set('meta_key', '');
    }

    // The banner shows the bundle on the shop page, and a program or diet
    // archive is about single Products; only the bundle archive lists bundles.
    $slugs = optimum_lift_kind_slugs();
    if (empty($slugs['bundle']) || $query->is_search()) {
        return;
    }

    $kind_archives = array_values(array_diff($slugs, [$slugs['bundle']]));
    if (!$query->is_post_type_archive('product') && ($kind_archives === [] || !$query->is_tax('product_cat', $kind_archives))) {
        return;
    }

    $tax_query   = (array) $query->get('tax_query');
    $tax_query[] = [
        'taxonomy' => 'product_cat',
        'field'    => 'slug',
        'terms'    => [$slugs['bundle']],
        'operator' => 'NOT IN',
    ];
    $query->set('tax_query', $tax_query);
}, 10, 2);
