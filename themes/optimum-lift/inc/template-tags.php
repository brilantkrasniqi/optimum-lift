<?php
/**
 * Small helpers used by templates. Keep presentation logic here rather than
 * inline in the template files.
 *
 * Storefront data helpers (offers, section blocks, bundles) live in inc/shop/
 * and are called behind function_exists(), so the chrome still renders when
 * a storefront module is missing.
 */

declare(strict_types=1);

/**
 * The heading for the current archive / search / 404 view.
 */
function optimum_lift_page_title(): string
{
    if (is_search()) {
        /* translators: %s: search query. */
        return sprintf(__('Results for "%s"', 'optimum-lift'), get_search_query());
    }

    if (is_404()) {
        return __('Page not found', 'optimum-lift');
    }

    if (is_home() && !is_front_page()) {
        // Without a posts page, get_the_title(0) would return the first post's title.
        $posts_page = (int) get_option('page_for_posts');

        return $posts_page > 0 ? (string) get_the_title($posts_page) : __('Blog', 'optimum-lift');
    }

    return wp_strip_all_tags(get_the_archive_title());
}

/**
 * Post meta line for the blog. Products do not use this.
 */
function optimum_lift_entry_meta(): void
{
    printf(
        '<p class="entry__meta"><time datetime="%1$s">%2$s</time></p>',
        esc_attr(get_the_date(DATE_W3C)),
        esc_html(get_the_date())
    );
}

/**
 * The Product a single Product page shows; null on every other page.
 */
function optimum_lift_current_product(): ?WC_Product
{
    if (!function_exists('is_product') || !is_product()) {
        return null;
    }

    $product = wc_get_product(get_queried_object_id());

    return $product instanceof WC_Product ? $product : null;
}

/**
 * Checkout gets a distraction-free header and footer. The order-received page
 * is past the payment, so it gets the full site chrome again.
 */
function optimum_lift_is_checkout_chrome(): bool
{
    return function_exists('is_checkout') && is_checkout() && !is_order_received_page();
}

/**
 * The static front page's ID, or 0 when the front page lists posts.
 */
function optimum_lift_front_page_id(): int
{
    return get_option('show_on_front') === 'page' ? (int) get_option('page_on_front') : 0;
}

/**
 * The post whose section blocks make up the current page (the front page or a
 * Product), or 0.
 */
function optimum_lift_sections_post_id(): int
{
    if (optimum_lift_current_product() !== null) {
        return get_queried_object_id();
    }

    return is_front_page() ? optimum_lift_front_page_id() : 0;
}

/**
 * The anchor of the first section of a layout on a post, or '' when the post
 * has no such section (or the sections module is not loaded).
 */
function optimum_lift_section_anchor(int $post_id, string $layout): string
{
    if ($post_id <= 0 || !function_exists('optimum_lift_blocks') || !function_exists('optimum_lift_block_id')) {
        return '';
    }

    foreach (optimum_lift_blocks($post_id) as $block) {
        if (($block['acf_fc_layout'] ?? '') === $layout) {
            return optimum_lift_block_id($block);
        }
    }

    return '';
}

/**
 * Header and mobile menu links. On the front page and Products: the shop,
 * then the page's own sections. Elsewhere: the `primary` menu, or when none is
 * assigned, the shop plus the front page's sections.
 *
 * @return list<array{label: string, url: string, current: bool, shop: bool}>
 */
function optimum_lift_nav_links(): array
{
    $post_id = optimum_lift_sections_post_id();

    if ($post_id === 0 && has_nav_menu('primary')) {
        return optimum_lift_menu_links('primary');
    }

    $links = [];

    if (function_exists('wc_get_page_permalink')) {
        $links[] = [
            'label'   => __('Shop', 'optimum-lift'),
            'url'     => wc_get_page_permalink('shop'),
            'current' => is_shop() || is_product_taxonomy(),
            'shop'    => true,
        ];
    }

    $sections_post = $post_id !== 0 ? $post_id : optimum_lift_front_page_id();
    $base          = $post_id !== 0 ? '' : home_url('/');

    if ($sections_post > 0 && function_exists('optimum_lift_block_nav')) {
        foreach (optimum_lift_block_nav($sections_post) as $item) {
            $links[] = [
                'label'   => $item['label'],
                'url'     => $base . '#' . $item['anchor'],
                'current' => false,
                'shop'    => false,
            ];
        }
    }

    return $links;
}

/**
 * The top-level items of the menu assigned to a location.
 *
 * @return list<array{label: string, url: string, current: bool, shop: bool}>
 */
function optimum_lift_menu_links(string $location): array
{
    $locations = get_nav_menu_locations();
    $items     = isset($locations[$location]) ? wp_get_nav_menu_items($locations[$location]) : false;

    if (!is_array($items)) {
        return [];
    }

    global $wp;
    $current  = untrailingslashit(home_url('/' . (string) $wp->request));
    $shop_url = function_exists('wc_get_page_permalink') ? untrailingslashit(wc_get_page_permalink('shop')) : '';
    $links    = [];

    foreach ($items as $item) {
        if ((int) $item->menu_item_parent !== 0) {
            continue;
        }

        $url     = (string) $item->url;
        $links[] = [
            'label'   => (string) $item->title,
            'url'     => $url,
            'current' => untrailingslashit($url) === $current,
            'shop'    => $shop_url !== '' && untrailingslashit($url) === $shop_url,
        ];
    }

    return $links;
}

/**
 * The header's call to action: to the price box on a Product, to the pricing
 * section on the front page, none elsewhere.
 *
 * @return array{label: string, url: string, arrow: bool}|null
 */
function optimum_lift_header_cta(): ?array
{
    if (optimum_lift_current_product() !== null) {
        return ['label' => __('Buy now', 'optimum-lift'), 'url' => '#blej', 'arrow' => false];
    }

    if (!is_front_page()) {
        return null;
    }

    $anchor = optimum_lift_section_anchor(optimum_lift_front_page_id(), 'pricing');

    if ($anchor === '' && !function_exists('wc_get_page_permalink')) {
        return null;
    }

    return [
        'label' => __('Start now', 'optimum-lift'),
        'url'   => $anchor !== '' ? '#' . $anchor : wc_get_page_permalink('shop'),
        'arrow' => true,
    ];
}

/**
 * "30-day guarantee", from the Customizer.
 */
function optimum_lift_guarantee_label(): string
{
    $days = (int) optimum_lift_setting('guarantee_days');

    /* translators: %d: number of days of the money-back guarantee. */
    return sprintf(_n('%d-day guarantee', '%d-day guarantee', $days, 'optimum-lift'), $days);
}

/**
 * A wa.me link to the Customizer's WhatsApp number, or '' when none is set.
 */
function optimum_lift_whatsapp_url(string $message = ''): string
{
    $number = (string) optimum_lift_setting('whatsapp');

    if ($number === '') {
        return '';
    }

    $url = 'https://wa.me/' . $number;

    return $message === '' ? $url : add_query_arg('text', rawurlencode($message), $url);
}

/**
 * Items in the cart, for the header badge.
 */
function optimum_lift_cart_count(): int
{
    return function_exists('WC') ? (int) WC()->cart?->get_cart_contents_count() : 0;
}

/**
 * The FAQ section: on this page when it has one, else on the front page, else ''.
 */
function optimum_lift_faq_url(): string
{
    $post_id = optimum_lift_sections_post_id();
    $anchor  = optimum_lift_section_anchor($post_id, 'faq');

    if ($anchor !== '') {
        return '#' . $anchor;
    }

    $front_page = optimum_lift_front_page_id();
    $anchor     = $front_page !== $post_id ? optimum_lift_section_anchor($front_page, 'faq') : '';

    return $anchor !== '' ? home_url('/#' . $anchor) : '';
}

/**
 * Every Product in the catalogue, in the editor's order, for the footer.
 *
 * @return list<WC_Product>
 */
function optimum_lift_catalogue_products(): array
{
    if (!function_exists('wc_get_products')) {
        return [];
    }

    $products = wc_get_products([
        'status'     => 'publish',
        'visibility' => 'catalog',
        'orderby'    => 'menu_order',
        'order'      => 'ASC',
        'limit'      => -1,
    ]);

    return is_array($products) ? array_values(array_filter($products, static fn (mixed $product): bool => $product instanceof WC_Product)) : [];
}

/**
 * Published legal pages: terms, privacy policy, refund policy.
 *
 * @return list<array{label: string, url: string}>
 */
function optimum_lift_legal_links(): array
{
    $pages = [
        [__('Terms of service', 'optimum-lift'), function_exists('wc_terms_and_conditions_page_id') ? wc_terms_and_conditions_page_id() : 0],
        [__('Privacy policy', 'optimum-lift'), (int) get_option('wp_page_for_privacy_policy')],
        [__('Refund policy', 'optimum-lift'), (int) get_option('woocommerce_refund_returns_page_id')],
    ];

    $links = [];

    foreach ($pages as [$label, $page_id]) {
        if ($page_id > 0 && get_post_status($page_id) === 'publish') {
            $links[] = ['label' => $label, 'url' => (string) get_permalink($page_id)];
        }
    }

    return $links;
}

/**
 * Whether a fixed bar covers the bottom of the screen on mobile (the Product
 * buy bar, the homepage CTA), so the footer needs room to scroll past it. On
 * a Product, only when its buy bar actually renders.
 */
function optimum_lift_has_sticky_bar(): bool
{
    $product = optimum_lift_current_product();

    // The same conditions under which buy-bar.php renders, and layout.php
    // reaches it (not behind a password form).
    $buy_bar = $product !== null
        && $product->is_purchasable()
        && $product->is_in_stock()
        && !post_password_required($product->get_id());

    return (bool) apply_filters('optimum_lift_has_sticky_bar', $buy_bar || is_front_page());
}
