<?php

/**
 * Product data for the storefront templates: kinds, bundles, sales fields and
 * text helpers.
 *
 * WooCommerce owns everything it already tracks (name, prices, categories,
 * reviews, sales, featured, order); ACF only holds the sales copy (ADR-0006).
 * Every ACF read goes through optimum_lift_field(), so the storefront still
 * renders when ACF is inactive.
 */

declare(strict_types=1);

/**
 * An ACF field value, or null when ACF is inactive.
 */
function optimum_lift_field(int $post_id, string $name): mixed
{
    if ($post_id <= 0 || !function_exists('get_field')) {
        return null;
    }

    return get_field($name, $post_id);
}

/**
 * A theme setting with the spec's defaults, for when the Customizer module is
 * not loaded.
 */
function optimum_lift_shop_setting(string $key): mixed
{
    if (function_exists('optimum_lift_setting')) {
        return optimum_lift_setting($key);
    }

    $defaults = [
        'guarantee_days'     => 30,
        'offer_label'        => __('Launch offer', 'optimum-lift'),
        'offer_ends_at'      => '',
        'customers_baseline' => 600,
    ];

    return $defaults[$key] ?? null;
}

/**
 * The product_cat slug behind each kind of Product.
 *
 * @return array<string, string> kind => product_cat slug
 */
function optimum_lift_kind_slugs(): array
{
    return (array) apply_filters('optimum_lift_kind_slugs', [
        'bundle'  => 'paketa',
        'program' => 'programe-stervitjeje',
        'diet'    => 'dieta',
    ]);
}

/**
 * 'program', 'diet' or 'bundle', from the Product's categories. A Product in
 * the bundle category is a bundle whatever else it is filed under.
 */
function optimum_lift_product_kind(WC_Product $p): ?string
{
    $terms = get_the_terms($p->get_id(), 'product_cat');
    if (!is_array($terms)) {
        return null;
    }

    $slugs = wp_list_pluck($terms, 'slug');
    $kinds = optimum_lift_kind_slugs();
    if (isset($kinds['bundle']) && in_array($kinds['bundle'], $slugs, true)) {
        return 'bundle';
    }

    foreach ($kinds as $kind => $slug) {
        if (in_array($slug, $slugs, true)) {
            return $kind;
        }
    }

    return null;
}

function optimum_lift_is_bundle(WC_Product $p): bool
{
    return optimum_lift_product_kind($p) === 'bundle';
}

/**
 * The bundle's published, purchasable components, in the order the editor set.
 *
 * @return list<WC_Product>
 */
function optimum_lift_bundle_components(WC_Product $bundle): array
{
    $ids = optimum_lift_field($bundle->get_id(), 'ol_bundle_components');
    if (!is_array($ids)) {
        return [];
    }

    $components = [];
    foreach ($ids as $id) {
        $product = is_numeric($id) ? wc_get_product((int) $id) : null;
        if (
            $product instanceof WC_Product
            && $product->get_id() !== $bundle->get_id()
            && $product->get_status() === 'publish'
            && $product->is_purchasable()
            && !optimum_lift_is_bundle($product)
        ) {
            $components[] = $product;
        }
    }

    return $components;
}

/**
 * Published, purchasable Products matching the query, in menu order.
 *
 * @param array<string, mixed> $args Extra wc_get_products() arguments.
 * @return list<WC_Product>
 */
function optimum_lift_query_products(array $args = []): array
{
    $products = wc_get_products(array_merge([
        'status'  => 'publish',
        'limit'   => -1,
        'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
    ], $args));

    if (!is_array($products)) {
        return [];
    }

    return array_values(array_filter(
        $products,
        static fn ($product): bool => $product instanceof WC_Product && $product->is_purchasable()
    ));
}

/**
 * Every published, purchasable bundle, in menu order. Cached until a post or
 * term changes.
 *
 * @return list<WC_Product>
 */
function optimum_lift_bundles(): array
{
    $slugs = optimum_lift_kind_slugs();
    if (empty($slugs['bundle'])) {
        return [];
    }

    $cache_key = 'bundles:' . wp_cache_get_last_changed('posts') . ':' . wp_cache_get_last_changed('terms');
    $ids       = wp_cache_get($cache_key, 'optimum_lift');

    if (!is_array($ids)) {
        $ids = array_map(
            static fn (WC_Product $product): int => $product->get_id(),
            optimum_lift_query_products(['category' => [$slugs['bundle']]])
        );
        wp_cache_set($cache_key, $ids, 'optimum_lift');
    }

    $bundles = [];
    foreach ($ids as $id) {
        $product = wc_get_product((int) $id);
        if ($product instanceof WC_Product) {
            $bundles[] = $product;
        }
    }

    return $bundles;
}

/**
 * The first bundle, or with $containing, the first bundle that includes it.
 */
function optimum_lift_find_bundle(?WC_Product $containing = null): ?WC_Product
{
    foreach (optimum_lift_bundles() as $bundle) {
        if ($containing === null) {
            return $bundle;
        }

        foreach (optimum_lift_bundle_components($bundle) as $component) {
            if ($component->get_id() === $containing->get_id()) {
                return $bundle;
            }
        }
    }

    return null;
}

/**
 * The kind that completes this one: a program's complement is a diet and back.
 */
function optimum_lift_complement_kind(WC_Product $p): ?string
{
    return match (optimum_lift_product_kind($p)) {
        'program' => 'diet',
        'diet'    => 'program',
        default   => null,
    };
}

/**
 * The Product's goal: its first pa_objektivi term, as plain text.
 */
function optimum_lift_goal(WC_Product $p): ?string
{
    $names = wc_get_product_terms($p->get_id(), 'pa_objektivi', ['fields' => 'names']);
    $first = reset($names);

    return is_string($first) && $first !== '' ? optimum_lift_plain_text($first) : null;
}

/**
 * Decodes the entities WordPress stores in term names and excerpts ("Masë
 * &amp; forcë"), so helpers documented as plain text stay plain text in
 * non-HTML output such as tracking payloads. Escape the result at output.
 */
function optimum_lift_plain_text(string $text): string
{
    return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * The singular category line on cards and the hero ("Program stërvitjeje").
 * Kinds have fixed labels because the category names are plural archive
 * titles; any other Product shows its first category.
 */
function optimum_lift_category_label(WC_Product $p): string
{
    $label = match (optimum_lift_product_kind($p)) {
        'program' => __('Training program', 'optimum-lift'),
        'diet'    => __('Meal plan', 'optimum-lift'),
        'bundle'  => __('Complete bundle', 'optimum-lift'),
        default   => '',
    };

    if ($label !== '') {
        return $label;
    }

    $terms   = get_the_terms($p->get_id(), 'product_cat');
    $default = (int) get_option('default_product_cat');
    foreach (is_array($terms) ? $terms : [] as $term) {
        if ($term->term_id !== $default) {
            return optimum_lift_plain_text($term->name);
        }
    }

    return '';
}

/**
 * The pill beside the category ("Fillestar → i avancuar", "Çdo program + çdo
 * dietë"): the level label field, else the goal. Plain text.
 */
function optimum_lift_level_label(WC_Product $p): ?string
{
    $label = optimum_lift_field($p->get_id(), 'ol_level_label');
    if (is_string($label) && trim($label) !== '') {
        return trim($label);
    }

    return optimum_lift_goal($p);
}

/**
 * The card blurb: the sales field, else the short description cut to 16 words.
 * Plain text.
 */
function optimum_lift_card_blurb(WC_Product $p): string
{
    $blurb = optimum_lift_field($p->get_id(), 'ol_card_blurb');
    if (is_string($blurb) && trim($blurb) !== '') {
        return trim($blurb);
    }

    return optimum_lift_plain_text(wp_trim_words($p->get_short_description(), 16, '…'));
}

/**
 * Benefit bullets, plain text. Cards show the first 3, the hero up to 6.
 *
 * @return list<string>
 */
function optimum_lift_points(WC_Product $p): array
{
    $points = [];
    foreach (optimum_lift_rows($p->get_id(), 'ol_points') as $row) {
        $text = is_string($row['text'] ?? null) ? trim($row['text']) : '';
        if ($text !== '') {
            $points[] = $text;
        }
    }

    return $points;
}

/**
 * The trust strip under the hero, at most 4 items.
 *
 * Values and labels are HTML-safe with tokens replaced (see
 * optimum_lift_replace_tokens()): print them without escaping again. An item
 * whose token has no value yet (a rating below its threshold) is dropped, so
 * weak proof is hidden rather than shown empty.
 *
 * @return list<array{value: string, label: string}>
 */
function optimum_lift_stats(WC_Product $p): array
{
    $stats = [];
    foreach (optimum_lift_rows($p->get_id(), 'ol_stats') as $row) {
        $value = is_string($row['value'] ?? null) ? trim($row['value']) : '';
        $label = is_string($row['label'] ?? null) ? trim($row['label']) : '';

        if ($value === '' || optimum_lift_has_empty_token($value . ' ' . $label, $p)) {
            continue;
        }

        $stats[] = [
            'value' => optimum_lift_replace_tokens($value, $p),
            'label' => optimum_lift_replace_tokens($label, $p),
        ];
    }

    return array_slice($stats, 0, 4);
}

/**
 * The included versions ("Niveli: Fillestar, Mesatar, I avancuar"), plain text.
 *
 * @return list<array{label: string, options: list<string>}>
 */
function optimum_lift_versions(WC_Product $p): array
{
    $versions = [];
    foreach (optimum_lift_rows($p->get_id(), 'ol_versions') as $row) {
        $label   = is_string($row['label'] ?? null) ? trim($row['label']) : '';
        $options = optimum_lift_lines(is_string($row['options'] ?? null) ? $row['options'] : null);

        if ($label !== '' || $options !== []) {
            $versions[] = ['label' => $label, 'options' => $options];
        }
    }

    return $versions;
}

/**
 * The rows of a repeater field, skipping anything that is not a row.
 *
 * @return list<array<string, mixed>>
 */
function optimum_lift_rows(int $post_id, string $name): array
{
    $rows = optimum_lift_field($post_id, $name);
    if (!is_array($rows)) {
        return [];
    }

    return array_values(array_filter($rows, 'is_array'));
}

/**
 * The Product name, escaped, with the ol_title_accent suffix wrapped in
 * <span class="text-accent-light">. A suffix the name does not end with is
 * ignored rather than appended.
 */
function optimum_lift_title_html(WC_Product $p): string
{
    $name   = $p->get_name();
    $accent = optimum_lift_field($p->get_id(), 'ol_title_accent');
    $accent = is_string($accent) ? trim($accent) : '';

    if ($accent === '' || $accent === $name || !str_ends_with($name, $accent)) {
        return esc_html($name);
    }

    return esc_html(rtrim(substr($name, 0, -strlen($accent))))
        . ' <span class="text-accent-light">' . esc_html($accent) . '</span>';
}

/**
 * Featured Products for the homepage, bundles excluded, in menu order.
 *
 * @return list<WC_Product>
 */
function optimum_lift_featured_products(int $limit = 3): array
{
    $products = array_filter(
        optimum_lift_query_products(['featured' => true, 'visibility' => 'catalog']),
        static fn (WC_Product $product): bool => !optimum_lift_is_bundle($product)
    );

    return array_slice(array_values($products), 0, max(0, $limit));
}

/**
 * Every published Product shown in the catalogue, bundles included.
 */
function optimum_lift_catalog_count(): int
{
    $ids = wc_get_products([
        'status'     => 'publish',
        'visibility' => 'catalog',
        'limit'      => -1,
        'return'     => 'ids',
    ]);

    return is_array($ids) ? count($ids) : 0;
}

/**
 * "Bought together" Products: the native Cross-sells, else Products of the
 * complementary kind, with the bundle that contains $p (or the first bundle)
 * taking the last slot. A bundle never cross-sells itself.
 *
 * @return list<WC_Product>
 */
function optimum_lift_cross_sells(WC_Product $p, int $limit = 3): array
{
    if ($limit <= 0) {
        return [];
    }

    $bundle     = null;
    $candidates = [];
    foreach ($p->get_cross_sell_ids() as $id) {
        $product = wc_get_product((int) $id);
        if (
            !$product instanceof WC_Product
            || $product->get_id() === $p->get_id()
            || $product->get_status() !== 'publish'
            || !$product->is_purchasable()
        ) {
            continue;
        }

        if (optimum_lift_is_bundle($product)) {
            $bundle ??= $product;
        } else {
            $candidates[] = $product;
        }
    }

    $complement = optimum_lift_complement_kind($p);
    $slugs      = optimum_lift_kind_slugs();
    if ($candidates === [] && $complement !== null && !empty($slugs[$complement])) {
        $candidates = optimum_lift_query_products([
            'category'   => [$slugs[$complement]],
            'visibility' => 'catalog',
            'exclude'    => [$p->get_id()],
        ]);
    }

    if (!optimum_lift_is_bundle($p)) {
        $bundle ??= optimum_lift_find_bundle($p) ?? optimum_lift_find_bundle();
    } else {
        $bundle = null;
    }

    if ($bundle === null) {
        return array_slice($candidates, 0, $limit);
    }

    $products   = array_slice($candidates, 0, $limit - 1);
    $products[] = $bundle;

    return $products;
}

/**
 * The Buy Now link (ADR-0007). Unescaped: pass it through esc_url() at output.
 */
function optimum_lift_buy_now_url(WC_Product $p): string
{
    return home_url('/?ol_buy_now=' . $p->get_id());
}

/**
 * A section heading as HTML: escaped, *word* in the accent colour, each new
 * line a <br>.
 */
function optimum_lift_heading_html(string $text): string
{
    $html = esc_html(trim($text));
    $html = (string) preg_replace('/\*([^*\r\n]+)\*/', '<span class="text-accent-light">$1</span>', $html);

    return (string) preg_replace('/\r\n|\r|\n/', '<br>', $html);
}

/**
 * The non-empty, trimmed lines of a textarea.
 *
 * @return list<string>
 */
function optimum_lift_lines(?string $textarea): array
{
    if ($textarea === null) {
        return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', $textarea);

    return array_values(array_filter(
        array_map('trim', is_array($lines) ? $lines : []),
        static fn (string $line): bool => $line !== ''
    ));
}

/**
 * Replaces {price}, {regular_price}, {saving}, {bundle_price},
 * {guarantee_days}, {customers}, {rating}, {reviews}, {store_rating},
 * {store_reviews} and {rest} in editor text.
 *
 * Returns HTML: the text is escaped first, then the values are inserted (prices
 * as wc_price() markup). Callers must not escape the result again.
 *
 * A token without a value (see optimum_lift_token_value()) becomes ''. In text
 * made of phrases separated by " · ", the phrase holding it is dropped instead,
 * so "Bli — {price} · Në vend të {regular_price}" keeps only its first phrase
 * while there is no saving. Unknown tokens are left as typed.
 */
function optimum_lift_replace_tokens(string $text, ?WC_Product $context): string
{
    if (!str_contains($text, '{')) {
        return esc_html($text);
    }

    $phrases = explode(' · ', $text);
    $kept    = [];

    foreach ($phrases as $phrase) {
        $empty = false;
        $html  = (string) preg_replace_callback(
            '/\{([a-z_]+)\}/',
            static function (array $match) use ($context, &$empty): string {
                $value = optimum_lift_token_value($match[1], $context);
                $empty = $empty || $value === '';

                return $value ?? $match[0];
            },
            esc_html($phrase)
        );

        if (!$empty || count($phrases) === 1) {
            $kept[] = $html;
        }
    }

    return implode(' · ', $kept);
}

/**
 * Whether the text uses a token that currently has no value. Use it to hide a
 * whole element (a stat, a note) rather than show it with a gap.
 */
function optimum_lift_has_empty_token(string $text, ?WC_Product $context): bool
{
    if (!preg_match_all('/\{([a-z_]+)\}/', $text, $matches)) {
        return false;
    }

    foreach ($matches[1] as $token) {
        if (optimum_lift_token_value($token, $context) === '') {
            return true;
        }
    }

    return false;
}

/**
 * One token's HTML-safe value: '' when it has no value, null when the token is
 * unknown.
 *
 * - {price}, {regular_price}, {saving}: the context Product's. {regular_price}
 *   is the struck anchor, so like {saving} it is empty without a saving
 *   (ADR-0008: no "was" price that equals the price).
 * - {bundle_price}: the context bundle, the bundle containing the context
 *   Product, else the first bundle.
 * - {rating}, {reviews}: the context Product's own, empty below the review
 *   threshold; the whole store's without a context. {store_rating} and
 *   {store_reviews} are always the whole store's (the homepage, whose context
 *   is the bundle, uses these).
 * - {guarantee_days}: empty when the Customizer guarantee is 0.
 * - {rest}: optimum_lift_catalog_rest() with the default three featured cards.
 */
function optimum_lift_token_value(string $token, ?WC_Product $context): ?string
{
    $price = static fn (float $amount): string => wc_price($amount);

    switch ($token) {
        case 'price':
            return $context !== null ? $price(optimum_lift_current_price($context)) : '';

        case 'regular_price':
        case 'saving':
            $saving = $context !== null ? optimum_lift_saving($context) : null;
            if ($context === null || $saving === null) {
                return '';
            }

            return $price($token === 'saving' ? $saving['amount'] : optimum_lift_anchor_price($context));

        case 'bundle_price':
            $bundle = $context !== null && optimum_lift_is_bundle($context)
                ? $context
                : optimum_lift_find_bundle($context) ?? optimum_lift_find_bundle();

            return $bundle !== null ? $price(optimum_lift_current_price($bundle)) : '';

        case 'guarantee_days':
            $days = (int) optimum_lift_shop_setting('guarantee_days');

            return $days > 0 ? esc_html(optimum_lift_format_number($days)) : '';

        case 'customers':
            return esc_html(optimum_lift_format_count_plus(optimum_lift_customer_count()));

        case 'rating':
        case 'reviews':
        case 'store_rating':
        case 'store_reviews':
            $rating = $context !== null && !str_starts_with($token, 'store_')
                ? optimum_lift_rating($context)
                : optimum_lift_store_rating();
            if ($rating === null) {
                return '';
            }

            return esc_html(str_ends_with($token, 'rating')
                ? optimum_lift_format_rating($rating['average'])
                : optimum_lift_format_number($rating['count']));

        case 'rest':
            return esc_html(optimum_lift_format_number(optimum_lift_catalog_rest()));
    }

    return null;
}

/**
 * The catalogue Products the homepage pricing section leaves out: the
 * catalogue count minus the featured cards it shows (up to $featured_limit)
 * and the bundle. A pricing block with another limit puts this number in
 * place of {rest} before replacing the other tokens.
 */
function optimum_lift_catalog_rest(int $featured_limit = 3): int
{
    $shown = count(optimum_lift_featured_products($featured_limit)) + (optimum_lift_find_bundle() !== null ? 1 : 0);

    return max(0, optimum_lift_catalog_count() - $shown);
}
