<?php

/**
 * Sizes (ADR-0011): a variable Product sells one variation per Size, such as
 * a diet in "Mashkull · 80–90 kg". The rest of the storefront asks these
 * helpers instead of WooCommerce's variation API, and a variation stands for
 * its parent wherever the theme reads kinds, bundles, ACF fields or
 * cross-sells (product-data.php).
 *
 * Every Size is a whole choice: a variation with an "Any …" attribute is never
 * resolved, so a buyer always gets exactly the file their Size names.
 */

declare(strict_types=1);

/**
 * Whether a Size must be chosen before the Product can be bought.
 */
function optimum_lift_needs_choice(WC_Product $p): bool
{
    return $p instanceof WC_Product_Variable;
}

/**
 * The parent of a variation, else the Product itself.
 */
function optimum_lift_base_product(WC_Product $p): WC_Product
{
    if (!$p instanceof WC_Product_Variation) {
        return $p;
    }

    $parent = wc_get_product($p->get_parent_id());

    return $parent instanceof WC_Product ? $parent : $p;
}

/**
 * The ID of the parent of a variation, else of the Product itself.
 */
function optimum_lift_base_id(WC_Product $p): int
{
    return $p instanceof WC_Product_Variation ? $p->get_parent_id() : $p->get_id();
}

/**
 * A variation's Size as plain text, its values' names in the parent's
 * attribute order ("Mashkull · 80–90 kg"); '' for anything else.
 */
function optimum_lift_size_label(WC_Product $p): string
{
    if (!$p instanceof WC_Product_Variation) {
        return '';
    }

    $parent = optimum_lift_base_product($p);
    $values = $p->get_variation_attributes(false);
    $names  = [];
    foreach ($parent->get_attributes() as $attribute) {
        if (!$attribute instanceof WC_Product_Attribute || !$attribute->get_variation()) {
            continue;
        }

        $value = $values[sanitize_title($attribute->get_name())] ?? '';
        if (!is_string($value) || $value === '') {
            continue;
        }

        $term    = $attribute->is_taxonomy() ? get_term_by('slug', $value, $attribute->get_name()) : null;
        $names[] = optimum_lift_plain_text($term instanceof WP_Term ? $term->name : $value);
    }

    return implode(' · ', $names);
}

/**
 * The `attribute_<name>` keys of the parent's variation attributes, mapped to
 * whether each is a taxonomy attribute.
 *
 * @return array<string, bool>
 */
function optimum_lift_size_keys(WC_Product $parent): array
{
    $keys = [];
    foreach ($parent->get_attributes() as $attribute) {
        if ($attribute instanceof WC_Product_Attribute && $attribute->get_variation()) {
            $keys['attribute_' . sanitize_title($attribute->get_name())] = $attribute->is_taxonomy();
        }
    }

    return $keys;
}

/**
 * The Size values a request chose, limited to the parent's variation
 * attributes and sanitised as WC_Cart::add_to_cart() does. Values left empty
 * are left out.
 *
 * @param array<mixed> $request Unslashed request values.
 * @return array<string, string>
 */
function optimum_lift_requested_size(WC_Product $parent, array $request): array
{
    $chosen = [];
    foreach (optimum_lift_size_keys($parent) as $key => $is_taxonomy) {
        $value = $request[$key] ?? '';
        if (!is_string($value)) {
            continue;
        }

        $value = $is_taxonomy
            ? sanitize_title($value)
            : html_entity_decode(wc_clean($value), ENT_QUOTES, get_bloginfo('charset'));
        if ($value !== '') {
            $chosen[$key] = $value;
        }
    }

    return $chosen;
}

/**
 * Whether the request chose a value for every Size attribute of the parent.
 *
 * @param array<mixed> $request Unslashed request values.
 */
function optimum_lift_size_complete(WC_Product $parent, array $request): bool
{
    $keys = optimum_lift_size_keys($parent);

    return $keys !== [] && count(optimum_lift_requested_size($parent, $request)) === count($keys);
}

/**
 * The variation a request chose: a `variation_id` that is a child of $parent,
 * else the `attribute_<name>` values. Null when the choice is incomplete or
 * matches nothing, when the variation cannot be bought (not published,
 * purchasable and in stock), or when it has an "Any …" attribute.
 *
 * @param array<mixed> $request Unslashed request values, such as wp_unslash($_GET).
 */
function optimum_lift_resolve_variation(WC_Product $parent, array $request): ?WC_Product_Variation
{
    if (!$parent instanceof WC_Product_Variable) {
        return null;
    }

    $id = is_numeric($request['variation_id'] ?? null) ? absint($request['variation_id']) : 0;
    if ($id === 0) {
        if (!optimum_lift_size_complete($parent, $request)) {
            return null;
        }

        // Every value must match: unlike WooCommerce's own matching, an
        // "Any …" variation never matches a chosen value.
        $chosen = optimum_lift_requested_size($parent, $request);
        foreach ($parent->get_children() as $child) {
            $values = get_post_type($child) === 'product_variation' ? wc_get_product_variation_attributes($child) : [];
            $values = array_intersect_key($values, $chosen);
            ksort($values);
            ksort($chosen);
            if ($values === $chosen) {
                $id = $child;
                break;
            }
        }
    }

    $variation = $id > 0 ? wc_get_product($id) : null;
    if (
        !$variation instanceof WC_Product_Variation
        || $variation->get_parent_id() !== $parent->get_id()
        || $variation->get_status() !== 'publish'
        || !$variation->is_purchasable()
        || !$variation->is_in_stock()
    ) {
        return null;
    }

    $values = $variation->get_variation_attributes();
    foreach (array_keys(optimum_lift_size_keys($parent)) as $key) {
        if (($values[$key] ?? '') === '') {
            return null;
        }
    }

    return $variation;
}

/**
 * Why a request resolved to no variation: "Choose your size first." while the
 * choice is incomplete, else "This size is not available.".
 *
 * @param array<mixed> $request Unslashed request values.
 */
function optimum_lift_size_error(WC_Product $parent, array $request): string
{
    $id = is_numeric($request['variation_id'] ?? null) ? absint($request['variation_id']) : 0;

    return $id === 0 && !optimum_lift_size_complete($parent, $request)
        ? __('Choose your size first.', 'optimum-lift')
        : __('This size is not available.', 'optimum-lift');
}

/**
 * The variation that has the same Size as another one, for a bundle sold in
 * the Sizes of its diets: the values the two share by attribute name.
 */
function optimum_lift_matching_variation(WC_Product $parent, WC_Product_Variation $like): ?WC_Product_Variation
{
    return optimum_lift_resolve_variation($parent, $like->get_variation_attributes());
}

/*
 * WooCommerce's own add path (`?add-to-cart=ID` with `attribute_*` values, the
 * picker's no-JavaScript form) adds a variable Product only with a
 * `variation_id` (WC_Form_Handler::add_to_cart_handler_variable()). This runs
 * just before it, on the same hook: it fills in the variation the values
 * name, or stops the add with the theme's notice.
 */
add_action('wp_loaded', static function (): void {
    $id = $_REQUEST['add-to-cart'] ?? null;
    if (!is_numeric($id) || !empty($_REQUEST['variation_id'])) {
        return;
    }

    $product = wc_get_product(absint($id));
    if (!$product instanceof WC_Product || !optimum_lift_needs_choice($product)) {
        return;
    }

    $request   = wp_unslash($_REQUEST);
    $request   = is_array($request) ? $request : [];
    $variation = optimum_lift_resolve_variation($product, $request);
    if ($variation !== null) {
        $_REQUEST['variation_id'] = (string) $variation->get_id();

        return;
    }

    wc_add_notice(optimum_lift_size_error($product, $request), 'notice');
    unset($_REQUEST['add-to-cart'], $_GET['add-to-cart'], $_POST['add-to-cart']);
}, 19);
