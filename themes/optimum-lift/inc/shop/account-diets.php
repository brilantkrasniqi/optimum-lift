<?php

/**
 * My Account › Diets: every diet the Customer can download, with its Size and
 * a Download PDF button, next to the Plans plugin's Plans tab. The dashboard
 * lists them under the Plans too, so a Customer never has to look for a diet
 * in Downloads.
 *
 * A diet is a download of a Product in the diet category, or of a bundle. A
 * bundle's file is named after the diet it is (matched by the file name's
 * plan prefix, as in the Size PDFs box), else WooCommerce's download name.
 * The markup is the Plans Portal's list (.ol-portal, .ol-card), so it looks
 * the same.
 */

declare(strict_types=1);

const OPTIMUM_LIFT_DIETS_ENDPOINT = 'diets';

/**
 * The Customer's diets, one per file, in WooCommerce's order (by order).
 *
 * @return list<array{title: string, size: string, bundle: string, url: string}>
 */
function optimum_lift_customer_diets(int $user_id): array
{
    $diets = [];
    foreach (wc_get_customer_available_downloads($user_id) as $download) {
        $product = wc_get_product((int) ($download['product_id'] ?? 0));
        if (!$product instanceof WC_Product || !in_array(optimum_lift_product_kind($product), ['diet', 'bundle'], true)) {
            continue;
        }

        // The same file bought twice is listed once.
        $key = $product->get_id() . '|' . (string) ($download['download_id'] ?? '');
        if (isset($diets[$key])) {
            continue;
        }

        $base        = optimum_lift_base_product($product);
        $bundle      = optimum_lift_is_bundle($base);
        $diets[$key] = [
            'title'  => $bundle ? optimum_lift_bundle_diet_name($product, $download) : $base->get_name(),
            'size'   => optimum_lift_size_label($product),
            'bundle' => $bundle ? $base->get_name() : '',
            'url'    => (string) ($download['download_url'] ?? ''),
        ];
    }

    return array_values($diets);
}

/**
 * The name of the diet a bundle's file is: the component whose file for the
 * same Size has the same plan prefix. Else WooCommerce's download name.
 *
 * @param array<string, mixed> $download One of wc_get_customer_available_downloads().
 */
function optimum_lift_bundle_diet_name(WC_Product $product, array $download): string
{
    $fallback = (string) ($download['download_name'] ?? $product->get_name());
    $file     = is_array($download['file'] ?? null) ? (string) ($download['file']['file'] ?? '') : '';
    if (!$product instanceof WC_Product_Variation || $file === '') {
        return $fallback;
    }

    $prefix = optimum_lift_size_file_prefix($file, $product);
    $stem   = optimum_lift_size_file_stem((string) wp_parse_url($file, PHP_URL_PATH));

    foreach (optimum_lift_bundle_components($product) as $component) {
        $sized = $component instanceof WC_Product_Variable ? optimum_lift_matching_variation($component, $product) : null;
        foreach ($sized?->get_downloads() ?? [] as $own) {
            // The same PDF, or a component sold in fewer attributes (Pesha
            // alone) whose file the bundle's suffix does not end with.
            $own_stem = optimum_lift_size_file_stem((string) wp_parse_url($own->get_file(), PHP_URL_PATH));
            if ($own_stem === $stem || ($prefix !== null && optimum_lift_size_file_prefix($own->get_file(), $sized) === $prefix)) {
                return $component->get_name();
            }
        }
    }

    return $fallback;
}

/**
 * The list, on the Diets tab and on the dashboard.
 *
 * @param list<array{title: string, size: string, bundle: string, url: string}> $diets
 */
function optimum_lift_render_diets(array $diets, string $heading = ''): void
{
    ?>
    <div class="ol-portal ol-diets">
        <?php if ($heading !== '') : ?>
            <h2 class="ol-portal__heading"><?php echo esc_html($heading); ?></h2>
        <?php endif; ?>

        <?php if ($diets === []) : ?>
            <p class="ol-empty">
                <?php esc_html_e('You do not have any diets yet.', 'optimum-lift'); ?>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Browse the shop', 'optimum-lift'); ?></a>
            </p>
        <?php endif; ?>

        <ul class="ol-plan-list">
            <?php foreach ($diets as $diet) : ?>
                <li class="ol-card">
                    <div class="ol-card__body">
                        <h2 class="ol-card__title"><?php echo esc_html($diet['title']); ?></h2>
                        <?php if ($diet['size'] !== '' || $diet['bundle'] !== '') : ?>
                            <p class="ol-muted">
                                <?php
                                $parts = array_filter([
                                    $diet['size'],
                                    /* translators: %s: the bundle's name. */
                                    $diet['bundle'] !== '' ? sprintf(__('Part of %s', 'optimum-lift'), $diet['bundle']) : '',
                                ]);
                                echo esc_html(implode(' · ', $parts));
                                ?>
                            </p>
                        <?php endif; ?>
                        <p class="ol-actions">
                            <a class="ol-button" href="<?php echo esc_url($diet['url']); ?>"><?php esc_html_e('Download PDF', 'optimum-lift'); ?></a>
                        </p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

add_filter('woocommerce_get_query_vars', static function (array $vars): array {
    $vars[OPTIMUM_LIFT_DIETS_ENDPOINT] = OPTIMUM_LIFT_DIETS_ENDPOINT;

    return $vars;
});

// The endpoint's rewrite rule, once (WooCommerce adds it on init).
add_action('wp_loaded', static function (): void {
    if (get_option('optimum_lift_rewrite') !== OPTIMUM_LIFT_DIETS_ENDPOINT) {
        flush_rewrite_rules(false);
        update_option('optimum_lift_rewrite', OPTIMUM_LIFT_DIETS_ENDPOINT);
    }
});

// After the Plans tab, else after Dashboard. Runs after the Plans plugin's filter.
add_filter('woocommerce_account_menu_items', static function (array $items): array {
    $keys     = array_keys($items);
    $position = array_search('plans', $keys, true);
    $position = $position === false ? array_search('dashboard', $keys, true) : $position;
    $offset   = $position === false ? 0 : (int) $position + 1;

    return array_slice($items, 0, $offset, true)
        + [OPTIMUM_LIFT_DIETS_ENDPOINT => _x('Diets', 'My Account tab', 'optimum-lift')]
        + array_slice($items, $offset, null, true);
}, 20);

add_filter('woocommerce_endpoint_' . OPTIMUM_LIFT_DIETS_ENDPOINT . '_title', static fn (): string => _x('Diets', 'My Account tab', 'optimum-lift'));

add_action('woocommerce_account_' . OPTIMUM_LIFT_DIETS_ENDPOINT . '_endpoint', static function (): void {
    optimum_lift_render_diets(optimum_lift_customer_diets(get_current_user_id()));
});

// On the dashboard, under the Plans, only when there are diets.
add_action('woocommerce_account_dashboard', static function (): void {
    $diets = optimum_lift_customer_diets(get_current_user_id());
    if ($diets !== []) {
        optimum_lift_render_diets($diets, __('Your diets', 'optimum-lift'));
    }
}, 20);

// The Plans Portal's stylesheet, which the plugin loads only on its own pages.
add_action('wp_enqueue_scripts', static function (): void {
    if (is_account_page() && is_wc_endpoint_url(OPTIMUM_LIFT_DIETS_ENDPOINT) && defined('OptimumLift\Plans\FILE') && defined('OptimumLift\Plans\VERSION')) {
        wp_enqueue_style('ol-portal', plugins_url('assets/portal.css', (string) constant('OptimumLift\Plans\FILE')), [], (string) constant('OptimumLift\Plans\VERSION'));
    }
});
