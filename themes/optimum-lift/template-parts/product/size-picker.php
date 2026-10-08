<?php

/**
 * The Size picker (ADR-0013) in the hero's price box, for a Product that
 * needs a choice: one group of radio pills per variation attribute, nothing
 * chosen unless the URL names a Size (`?attribute_pa_pesha=60-70`).
 *
 * It is a plain GET form to the Product's own page, so it works without
 * JavaScript: the buy buttons, here and in the sticky bar, submit it through
 * their `form` attribute, the browser refuses until every group has a choice,
 * and Buy Now (buy-now.php) or WooCommerce's add-to-cart handler (with
 * sizes.php) find the variation from the `attribute_*` values.
 * modules/size-picker.js disables the combinations that are not sold and
 * fills in `variation_id`; modules/cart.js sends the add through the drawer.
 */

declare(strict_types=1);

/** @var array{product: WC_Product, class?: string} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product_Variable || !optimum_lift_needs_choice($product)) {
    return;
}

// Each group's options in the attribute's own order: the term order for a
// global attribute, as typed for a custom one.
$groups = [];
foreach ($product->get_variation_attributes() as $name => $values) {
    $name    = (string) $name;
    $key     = 'attribute_' . sanitize_title($name);
    $options = [];

    if (taxonomy_exists($name)) {
        foreach (wc_get_product_terms($product->get_id(), $name, ['fields' => 'all']) as $term) {
            if ($term instanceof WP_Term && in_array($term->slug, $values, true)) {
                $options[$term->slug] = optimum_lift_plain_text($term->name);
            }
        }
    } else {
        foreach ($values as $value) {
            $options[(string) $value] = optimum_lift_plain_text((string) $value);
        }
    }

    if ($options === []) {
        continue;
    }

    // A valid value in the URL is chosen; anything else is ignored.
    $requested = isset($_GET[$key]) && is_string($_GET[$key]) ? wp_unslash($_GET[$key]) : '';
    $requested = taxonomy_exists($name) ? sanitize_title($requested) : html_entity_decode(wc_clean($requested), ENT_QUOTES, get_bloginfo('charset'));

    $groups[] = [
        'key'     => $key,
        'legend'  => optimum_lift_plain_text(wc_attribute_label($name, $product)),
        'options' => $options,
        'checked' => isset($options[$requested]) ? $requested : '',
    ];
}

if ($groups === []) {
    return;
}

// What the script needs to know about each published Size.
$variations = [];
foreach ($product->get_children() as $child_id) {
    $variation = wc_get_product($child_id);
    if ($variation instanceof WC_Product_Variation && $variation->get_status() === 'publish') {
        $variations[] = [
            'id'         => $variation->get_id(),
            'attributes' => $variation->get_variation_attributes(),
            'available'  => $variation->is_purchasable() && $variation->is_in_stock(),
        ];
    }
}

?>
<form id="ol-size-form" action="<?php echo esc_url($product->get_permalink()); ?>" method="get" data-size-picker class="<?php echo esc_attr(trim('ol-size-picker ' . ($args['class'] ?? ''))); ?>">
    <?php
    // A plain permalink (`?product=…`) keeps its query: a GET form drops the action's own.
    wp_parse_str((string) wp_parse_url($product->get_permalink(), PHP_URL_QUERY), $query);
    foreach ($query as $arg => $value) :
        ?>
        <input type="hidden" name="<?php echo esc_attr((string) $arg); ?>" value="<?php echo esc_attr(is_string($value) ? $value : ''); ?>">
    <?php endforeach; ?>
    <?php foreach ($groups as $group) : ?>
        <fieldset class="ol-size-group">
            <legend class="ol-size-legend"><?php echo esc_html($group['legend']); ?></legend>
            <div class="ol-size-options">
                <?php foreach ($group['options'] as $value => $label) : ?>
                    <label class="ol-size-pill">
                        <input type="radio" name="<?php echo esc_attr($group['key']); ?>" value="<?php echo esc_attr((string) $value); ?>" required<?php checked($group['checked'], (string) $value); ?>>
                        <span><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php endforeach; ?>
    <input type="hidden" name="variation_id" value="">
    <div class="ol-size-notice" data-size-notice aria-live="polite"></div>
    <script type="application/json" data-size-variations><?php echo wp_json_encode($variations, JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
</form>
