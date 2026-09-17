<?php

/**
 * The storefront's ACF field groups: the Product sales fields, the bundle's
 * components and the section stack (ol_blocks) shared by Products and the
 * front page (ADR-0006).
 *
 * Keys are derived from names by optimum_lift_acf_keys(), never typed, so the
 * seed can write by key before these groups load: field_olt_<name without
 * ol_>, sub fields append _<name> to their parent's key, and a layout's sub
 * fields start from field_olt_<layout>. The Plans plugin owns group_ol_* and
 * field_ol_*; nothing here may use those prefixes.
 */

declare(strict_types=1);

add_action('acf/include_fields', 'optimum_lift_register_fields');

function optimum_lift_register_fields(): void
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    $product    = ['param' => 'post_type', 'operator' => '==', 'value' => 'product'];
    $bundle_cat = 'product_cat:' . optimum_lift_kind_slugs()['bundle'];

    acf_add_local_field_group([
        'key'        => 'group_olt_sales',
        'title'      => __('Sales page', 'optimum-lift'),
        'location'   => [[$product]],
        'menu_order' => 0,
        'fields'     => optimum_lift_acf_keys(optimum_lift_sales_fields(), 'field_olt', true),
    ]);

    acf_add_local_field_group([
        'key'        => 'group_olt_bundle',
        'title'      => __('Bundle', 'optimum-lift'),
        'location'   => [[
            $product,
            ['param' => 'post_taxonomy', 'operator' => '==', 'value' => $bundle_cat],
        ]],
        'menu_order' => 1,
        'fields'     => optimum_lift_acf_keys([
            optimum_lift_acf_field('relationship', 'ol_bundle_components', __('Components', 'optimum-lift'), [
                'instructions'  => __('The Products this bundle includes. Its comparison price is the sum of their current prices.', 'optimum-lift'),
                'post_type'     => ['product'],
                'post_status'   => ['publish'],
                'filters'       => ['search'],
                'return_format' => 'id',
            ]),
        ], 'field_olt', true),
    ]);

    acf_add_local_field_group([
        'key'        => 'group_olt_sections',
        'title'      => __('Page sections', 'optimum-lift'),
        'location'   => [
            [$product],
            [['param' => 'page_type', 'operator' => '==', 'value' => 'front_page']],
        ],
        'menu_order' => 2,
        'fields'     => optimum_lift_acf_keys([
            optimum_lift_acf_field('flexible_content', 'ol_blocks', __('Sections', 'optimum-lift'), [
                'button_label' => __('Add section', 'optimum-lift'),
                'layouts'      => optimum_lift_section_layouts(),
            ]),
        ], 'field_olt', true),
    ]);
}

/**
 * @return list<array<string, mixed>>
 */
function optimum_lift_sales_fields(): array
{
    return [
        optimum_lift_acf_field('text', 'ol_title_accent', __('Title accent', 'optimum-lift'), [
            'instructions' => __('The end of the Product name to show in the accent colour, e.g. "12-Javor".', 'optimum-lift'),
        ]),
        optimum_lift_acf_field('text', 'ol_card_blurb', __('Card blurb', 'optimum-lift'), [
            'instructions' => __('One sentence for Product cards. When empty, the short description cut to 16 words.', 'optimum-lift'),
        ]),
        optimum_lift_acf_repeater('ol_points', __('Benefit bullets', 'optimum-lift'), [
            optimum_lift_acf_field('text', 'text', __('Text', 'optimum-lift')),
        ], [
            'instructions' => __('Cards show the first 3, the Product page up to 6.', 'optimum-lift'),
        ]),
        optimum_lift_acf_field('number', 'ol_duration_weeks', __('Duration (weeks)', 'optimum-lift'), [
            'instructions' => __('Shows the price per week.', 'optimum-lift'),
            'min'          => 1,
            'step'         => 1,
        ]),
        optimum_lift_acf_field('text', 'ol_media_label', __('Gallery label', 'optimum-lift'), [
            'instructions' => __('e.g. "Produkt digjital · PDF + video".', 'optimum-lift'),
        ]),
        optimum_lift_acf_field('text', 'ol_level_label', __('Level label', 'optimum-lift'), [
            'instructions' => __('The pill beside the category. When empty, the goal.', 'optimum-lift'),
        ]),
        optimum_lift_acf_repeater('ol_versions', __('Included versions', 'optimum-lift'), [
            optimum_lift_acf_field('text', 'label', __('Label', 'optimum-lift')),
            optimum_lift_acf_lines('options', __('Options', 'optimum-lift')),
        ], [
            'instructions' => __('Every version ships with the Product. Nothing here is a choice the buyer makes.', 'optimum-lift'),
        ]),
        optimum_lift_acf_field('text', 'ol_versions_note', __('Versions note', 'optimum-lift')),
        optimum_lift_acf_repeater('ol_stats', __('Trust strip', 'optimum-lift'), [
            optimum_lift_acf_field('text', 'value', __('Value', 'optimum-lift')),
            optimum_lift_acf_field('text', 'label', __('Label', 'optimum-lift')),
        ], [
            'instructions' => optimum_lift_acf_tokens_note(),
            'max'          => 4,
        ]),
        optimum_lift_acf_field('text', 'ol_bundle_hint', __('Bundle hint', 'optimum-lift'), [
            'instructions' => __('The sentence before the bundle name, e.g. "E do edhe ushqimin e zgjidhur?"', 'optimum-lift'),
        ]),
    ];
}

/**
 * Every section layout, keyed by layout key.
 *
 * @return array<string, array<string, mixed>>
 */
function optimum_lift_section_layouts(): array
{
    $tokens = optimum_lift_acf_tokens_note();
    $text   = __('Text', 'optimum-lift');
    $title  = __('Title', 'optimum-lift');
    $label  = __('Label', 'optimum-lift');
    $value  = __('Value', 'optimum-lift');
    $name   = __('Name', 'optimum-lift');
    $body   = __('Body', 'optimum-lift');
    $note   = __('Note', 'optimum-lift');
    $items  = __('Items', 'optimum-lift');
    $image  = __('Image', 'optimum-lift');
    $cta    = __('Button label', 'optimum-lift');
    $anchor = __('Button anchor', 'optimum-lift');
    $chips  = __('Chips', 'optimum-lift');

    $layouts = [
        optimum_lift_acf_layout('qualification', __('Who it is for', 'optimum-lift'), [
            optimum_lift_acf_field('text', 'yes_title', __('"For you" title', 'optimum-lift')),
            optimum_lift_acf_repeater('yes_items', __('"For you" items', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'text', $text, [
                    'instructions' => __('Bold text and links are allowed.', 'optimum-lift'),
                ]),
            ]),
            optimum_lift_acf_field('text', 'no_title', __('"Not for you" title', 'optimum-lift')),
            optimum_lift_acf_repeater('no_items', __('"Not for you" items', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'text', $text),
            ]),
        ]),
        optimum_lift_acf_layout('phases', __('Phases', 'optimum-lift'), [
            optimum_lift_acf_select('style', __('Style', 'optimum-lift'), [
                'numbers' => __('Numbers', 'optimum-lift'),
                'icons'   => __('Icons', 'optimum-lift'),
            ]),
            optimum_lift_acf_repeater('cards', __('Cards', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'label', $label),
                optimum_lift_acf_field('text', 'title', $title),
                optimum_lift_acf_textarea('text', $text),
                optimum_lift_acf_lines('bullets', __('Bullets', 'optimum-lift')),
                optimum_lift_acf_icon(),
                optimum_lift_acf_bool('featured', __('Featured', 'optimum-lift')),
            ], ['layout' => 'block']),
            optimum_lift_acf_lines('checklist', __('Checklist', 'optimum-lift')),
        ]),
        optimum_lift_acf_layout('preview', __('Look inside', 'optimum-lift'), [
            optimum_lift_acf_field('text', 'workout_label', __('Workout label', 'optimum-lift')),
            optimum_lift_acf_field('text', 'workout_tag', __('Workout tag', 'optimum-lift')),
            optimum_lift_acf_repeater('workout_rows', __('Workout rows', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'name', __('Exercise', 'optimum-lift')),
                optimum_lift_acf_field('text', 'scheme', __('Sets × reps', 'optimum-lift')),
            ]),
            optimum_lift_acf_textarea('workout_note', __('Workout note', 'optimum-lift')),
            optimum_lift_acf_image('media_image', $image),
            optimum_lift_acf_field('url', 'media_video_url', __('Video URL', 'optimum-lift')),
            optimum_lift_acf_field('text', 'media_caption', __('Media caption', 'optimum-lift')),
            optimum_lift_acf_field('text', 'progress_title', __('Progress title', 'optimum-lift')),
            optimum_lift_acf_repeater('progress_rows', __('Progress rows', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'label', $label),
                optimum_lift_acf_field('text', 'value', $value),
                optimum_lift_acf_percent('percent', __('Bar (%)', 'optimum-lift')),
            ]),
            optimum_lift_acf_textarea('progress_note', __('Progress note', 'optimum-lift')),
        ]),
        optimum_lift_acf_layout('sample_day', __('Sample day', 'optimum-lift'), [
            optimum_lift_acf_field('text', 'day_label', __('Day label', 'optimum-lift')),
            optimum_lift_acf_field('text', 'kcal', __('Calories', 'optimum-lift')),
            optimum_lift_acf_field('text', 'protein', __('Protein', 'optimum-lift')),
            optimum_lift_acf_field('text', 'carbs', __('Carbohydrates', 'optimum-lift')),
            optimum_lift_acf_field('text', 'fat', __('Fat', 'optimum-lift')),
            optimum_lift_acf_repeater('meals', __('Meals', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'time', __('Time', 'optimum-lift')),
                optimum_lift_acf_field('text', 'name', $name),
                optimum_lift_acf_textarea('description', __('Description', 'optimum-lift')),
                optimum_lift_acf_field('text', 'kcal', __('Calories', 'optimum-lift')),
                optimum_lift_acf_field('text', 'macros', __('Macros', 'optimum-lift')),
            ], ['layout' => 'block']),
            optimum_lift_acf_textarea('note', $note, [
                'instructions' => __('Bold text is allowed.', 'optimum-lift'),
            ]),
            optimum_lift_acf_repeater('facts', __('Facts', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'value', $value),
                optimum_lift_acf_field('text', 'text', $text),
            ]),
        ]),
        optimum_lift_acf_layout('shopping_list', __('Shopping list', 'optimum-lift'), [
            optimum_lift_acf_wysiwyg('body', $body),
            optimum_lift_acf_field('text', 'cost_label', __('Cost label', 'optimum-lift')),
            optimum_lift_acf_field('text', 'cost_value', __('Cost', 'optimum-lift')),
            optimum_lift_acf_field('text', 'cost_suffix', __('Cost suffix', 'optimum-lift')),
            optimum_lift_acf_field('text', 'cost_note', __('Cost note', 'optimum-lift')),
            optimum_lift_acf_field('text', 'list_label', __('List label', 'optimum-lift')),
            optimum_lift_acf_lines('items', $items),
            optimum_lift_acf_field('text', 'list_note', __('List note', 'optimum-lift')),
        ]),
        optimum_lift_acf_layout('value_stack', __('Value stack', 'optimum-lift'), [
            optimum_lift_acf_select('layout', __('Layout', 'optimum-lift'), [
                'centered' => __('Centred', 'optimum-lift'),
                'split'    => __('Split, with image', 'optimum-lift'),
            ]),
            optimum_lift_acf_product('product', __('Product', 'optimum-lift'), [
                'instructions' => __('When empty, the page\'s Product (the bundle on the front page).', 'optimum-lift'),
            ]),
            optimum_lift_acf_repeater('items', $items, [
                optimum_lift_acf_field('text', 'text', $text),
                optimum_lift_acf_field('number', 'value', $value, ['min' => 0, 'step' => 'any']),
                optimum_lift_acf_bool('bonus', __('Bonus', 'optimum-lift')),
            ]),
            optimum_lift_acf_field('text', 'total_label', __('Total label', 'optimum-lift')),
            optimum_lift_acf_field('text', 'pay_label', __('"You pay" label', 'optimum-lift')),
            optimum_lift_acf_field('text', 'cta_label', $cta, ['instructions' => $tokens]),
            optimum_lift_acf_image('side_image', __('Side image', 'optimum-lift')),
        ]),
        optimum_lift_acf_layout('results', __('Results', 'optimum-lift'), [
            optimum_lift_acf_repeater('items', $items, [
                optimum_lift_acf_image('before_image', __('Before image', 'optimum-lift')),
                optimum_lift_acf_image('after_image', __('After image', 'optimum-lift')),
                optimum_lift_acf_field('text', 'name', $name),
                optimum_lift_acf_field('text', 'meta', __('Details', 'optimum-lift')),
                optimum_lift_acf_field('text', 'result', __('Result', 'optimum-lift')),
                optimum_lift_acf_textarea('quote', __('Quote', 'optimum-lift')),
            ], ['layout' => 'block']),
            optimum_lift_acf_textarea('disclaimer', __('Disclaimer', 'optimum-lift')),
            optimum_lift_acf_field('text', 'cta_label', $cta),
            optimum_lift_acf_field('text', 'cta_anchor', $anchor),
        ]),
        optimum_lift_acf_layout('reviews', __('Reviews', 'optimum-lift'), [
            optimum_lift_acf_select('source', __('Source', 'optimum-lift'), [
                'both'   => __('Product reviews, then testimonials', 'optimum-lift'),
                'native' => __('Product reviews', 'optimum-lift'),
                'manual' => __('Testimonials', 'optimum-lift'),
            ]),
            optimum_lift_acf_field('number', 'limit', __('Product reviews to show', 'optimum-lift'), [
                'default_value' => 3,
                'min'           => 1,
                'step'          => 1,
            ]),
            optimum_lift_acf_repeater('items', __('Testimonials', 'optimum-lift'), [
                optimum_lift_acf_textarea('quote', __('Quote', 'optimum-lift')),
                optimum_lift_acf_field('text', 'name', $name),
                optimum_lift_acf_field('text', 'meta', __('Details', 'optimum-lift')),
                optimum_lift_acf_field('number', 'rating', __('Rating', 'optimum-lift'), [
                    'default_value' => 5,
                    'min'           => 1,
                    'max'           => 5,
                    'step'          => 1,
                ]),
            ], ['layout' => 'block']),
            optimum_lift_acf_bool('show_summary', __('Show the rating summary', 'optimum-lift'), 1),
        ]),
        optimum_lift_acf_layout('comparison', __('Comparison', 'optimum-lift'), [
            optimum_lift_acf_repeater('columns', __('Columns', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'label', $label),
                optimum_lift_acf_product('product', __('Product', 'optimum-lift')),
                optimum_lift_acf_bool('highlight', __('Highlight', 'optimum-lift')),
            ]),
            optimum_lift_acf_repeater('rows', __('Rows', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'label', $label),
                optimum_lift_acf_select('type', __('Type', 'optimum-lift'), [
                    'text'  => $text,
                    'price' => __('Price of the column\'s Product', 'optimum-lift'),
                    'cta'   => __('Button to the column\'s Product', 'optimum-lift'),
                ]),
                optimum_lift_acf_repeater('cells', __('Cells', 'optimum-lift'), [
                    optimum_lift_acf_field('text', 'value', $value, [
                        'instructions' => __('Start with ✓ or ✕ for a tick or a cross.', 'optimum-lift'),
                    ]),
                ]),
            ], ['layout' => 'block']),
            optimum_lift_acf_textarea('note', $note),
        ]),
        optimum_lift_acf_layout('credibility', __('Coach', 'optimum-lift'), [
            optimum_lift_acf_image('image', $image),
            optimum_lift_acf_field('text', 'name', $name),
            optimum_lift_acf_field('text', 'role', __('Role', 'optimum-lift')),
            optimum_lift_acf_wysiwyg('body', $body),
            optimum_lift_acf_repeater('badges', __('Badges', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'title', $title),
                optimum_lift_acf_field('text', 'text', $text),
                optimum_lift_acf_icon(),
            ]),
            optimum_lift_acf_lines('chips', $chips),
        ]),
        optimum_lift_acf_layout('guarantee', __('Guarantee', 'optimum-lift'), [
            optimum_lift_acf_select('style', __('Style', 'optimum-lift'), [
                'band' => __('Band', 'optimum-lift'),
                'card' => __('Card', 'optimum-lift'),
            ]),
            optimum_lift_acf_textarea('body', $body, ['instructions' => $tokens]),
            optimum_lift_acf_lines('chips', $chips),
            optimum_lift_acf_field('text', 'cta_label', $cta),
            optimum_lift_acf_field('text', 'cta_anchor', $anchor),
        ]),
        optimum_lift_acf_layout('faq', __('FAQ', 'optimum-lift'), [
            optimum_lift_acf_repeater('items', __('Questions', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'question', __('Question', 'optimum-lift')),
                optimum_lift_acf_wysiwyg('answer', __('Answer', 'optimum-lift')),
            ], ['layout' => 'block']),
            optimum_lift_acf_wysiwyg('note', __('Medical disclaimer', 'optimum-lift')),
            optimum_lift_acf_bool('help_box', __('Show the WhatsApp help box', 'optimum-lift'), 0, [
                'instructions' => __('Shown only when a WhatsApp number is set in the Customizer.', 'optimum-lift'),
            ]),
        ]),
        optimum_lift_acf_layout('final_cta', __('Final call to action', 'optimum-lift'), [
            optimum_lift_acf_textarea('body', $body),
            optimum_lift_acf_bool('show_countdown', __('Show the offer countdown', 'optimum-lift'), 1),
            optimum_lift_acf_product('product', __('Product', 'optimum-lift'), [
                'instructions' => __('When empty, the page\'s Product (the bundle on the front page).', 'optimum-lift'),
            ]),
            optimum_lift_acf_field('text', 'primary_label', __('Primary button label', 'optimum-lift'), [
                'instructions' => $tokens,
            ]),
            optimum_lift_acf_select('primary_action', __('Primary button action', 'optimum-lift'), [
                'buy_now' => __('Buy now', 'optimum-lift'),
                'anchor'  => __('Scroll to a section', 'optimum-lift'),
            ]),
            optimum_lift_acf_field('text', 'primary_anchor', __('Primary button anchor', 'optimum-lift')),
            optimum_lift_acf_bool('secondary_add_to_cart', __('Show "Add to cart"', 'optimum-lift')),
            optimum_lift_acf_textarea('note', $note, ['instructions' => $tokens]),
        ]),
        optimum_lift_acf_layout('rich_text', __('Rich text', 'optimum-lift'), [
            optimum_lift_acf_wysiwyg('body', $body),
        ]),
        optimum_lift_acf_layout('home_hero', __('Homepage hero', 'optimum-lift'), [
            optimum_lift_acf_bool('show_proof', __('Show customers and rating', 'optimum-lift'), 1),
            optimum_lift_acf_wysiwyg('body', $body),
            optimum_lift_acf_field('text', 'primary_label', __('Primary button label', 'optimum-lift')),
            optimum_lift_acf_field('text', 'primary_anchor', __('Primary button anchor', 'optimum-lift')),
            optimum_lift_acf_field('text', 'secondary_label', __('Secondary button label', 'optimum-lift')),
            optimum_lift_acf_field('text', 'secondary_anchor', __('Secondary button anchor', 'optimum-lift')),
            optimum_lift_acf_lines('reassurance', __('Reassurance', 'optimum-lift')),
            optimum_lift_acf_image('before_image', __('Before image', 'optimum-lift')),
            optimum_lift_acf_image('after_image', __('After image', 'optimum-lift')),
            optimum_lift_acf_field('text', 'before_label', __('Before label', 'optimum-lift')),
            optimum_lift_acf_field('text', 'after_label', __('After label', 'optimum-lift')),
            optimum_lift_acf_field('text', 'progress_label', __('Progress label', 'optimum-lift')),
            optimum_lift_acf_percent('progress_percent', __('Progress (%)', 'optimum-lift')),
            optimum_lift_acf_repeater('progress_stats', __('Progress stats', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'value', $value),
                optimum_lift_acf_field('text', 'label', $label),
            ]),
            optimum_lift_acf_field('text', 'day_plan_label', __('Day plan label', 'optimum-lift')),
            optimum_lift_acf_repeater('day_plan', __('Day plan', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'meal', __('Meal', 'optimum-lift')),
                optimum_lift_acf_field('text', 'kcal', __('Calories', 'optimum-lift')),
            ]),
            optimum_lift_acf_repeater('stats', __('Stats', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'value', $value),
                optimum_lift_acf_field('text', 'label', $label),
            ], ['instructions' => $tokens]),
        ]),
        optimum_lift_acf_layout('marquee', __('Marquee', 'optimum-lift'), [
            optimum_lift_acf_lines('items', $items),
        ]),
        optimum_lift_acf_layout('problem', __('Problem', 'optimum-lift'), [
            optimum_lift_acf_repeater('cards', __('Cards', 'optimum-lift'), [
                optimum_lift_acf_icon(),
                optimum_lift_acf_field('text', 'title', $title),
                optimum_lift_acf_textarea('text', $text),
            ], ['layout' => 'block']),
            optimum_lift_acf_field('text', 'footer', __('Closing line', 'optimum-lift'), [
                'instructions' => optimum_lift_acf_accent_note(),
            ]),
        ]),
        optimum_lift_acf_layout('steps', __('How it works', 'optimum-lift'), [
            optimum_lift_acf_repeater('steps', __('Steps', 'optimum-lift'), [
                optimum_lift_acf_icon(),
                optimum_lift_acf_field('text', 'title', $title),
                optimum_lift_acf_textarea('text', $text),
            ], ['layout' => 'block']),
            optimum_lift_acf_field('text', 'cta_label', $cta),
            optimum_lift_acf_field('text', 'cta_anchor', $anchor),
            optimum_lift_acf_bool('show_recent', __('Show recent orders', 'optimum-lift'), 0, [
                'instructions' => __('Shown only when enough orders were paid in the last 24 hours.', 'optimum-lift'),
            ]),
        ]),
        optimum_lift_acf_layout('goal_tabs', __('Goal tabs', 'optimum-lift'), [
            optimum_lift_acf_repeater('tabs', __('Tabs', 'optimum-lift'), [
                optimum_lift_acf_field('text', 'label', __('Tab label', 'optimum-lift')),
                optimum_lift_acf_field('text', 'heading', __('Heading', 'optimum-lift')),
                optimum_lift_acf_textarea('text', $text),
                optimum_lift_acf_lines('bullets', __('Bullets', 'optimum-lift')),
                optimum_lift_acf_field('text', 'cta_label', $cta),
                optimum_lift_acf_product('product', __('Product', 'optimum-lift')),
                optimum_lift_acf_field('text', 'cta_anchor', $anchor, [
                    'instructions' => __('Used when no Product is set.', 'optimum-lift'),
                ]),
                optimum_lift_acf_field('text', 'stat', __('Stat', 'optimum-lift')),
                optimum_lift_acf_image('image', $image),
                optimum_lift_acf_field('text', 'image_label', __('Image label', 'optimum-lift')),
                optimum_lift_acf_field('text', 'image_value', __('Image value', 'optimum-lift')),
            ], ['layout' => 'block']),
        ]),
        optimum_lift_acf_layout('pricing', __('Pricing', 'optimum-lift'), [
            optimum_lift_acf_bool('show_countdown', __('Show the offer countdown', 'optimum-lift'), 1),
            optimum_lift_acf_product('bundle', __('Bundle', 'optimum-lift'), [
                'instructions' => __('When empty, the first bundle.', 'optimum-lift'),
                'taxonomy'     => ['product_cat:' . optimum_lift_kind_slugs()['bundle']],
            ]),
            optimum_lift_acf_field('number', 'featured_limit', __('Featured Products to show', 'optimum-lift'), [
                'default_value' => 3,
                'min'           => 0,
                'step'          => 1,
            ]),
            optimum_lift_acf_field('text', 'rest_heading', __('"More Products" heading', 'optimum-lift')),
            optimum_lift_acf_textarea('rest_text', __('"More Products" text', 'optimum-lift'), [
                'instructions' => $tokens,
            ]),
            optimum_lift_acf_bool('show_trust', __('Show payment and guarantee row', 'optimum-lift'), 1),
        ]),
    ];

    return array_column($layouts, null, 'key');
}

/**
 * A layout with the common section fields first.
 *
 * @param list<array<string, mixed>> $sub_fields
 * @return array<string, mixed>
 */
function optimum_lift_acf_layout(string $name, string $label, array $sub_fields): array
{
    $common = [
        optimum_lift_acf_field('text', 'anchor', __('Anchor', 'optimum-lift'), [
            'instructions' => __('The section id for links, e.g. "faq".', 'optimum-lift'),
            'wrapper'      => ['width' => '25'],
        ]),
        optimum_lift_acf_field('text', 'nav_label', __('Navigation label', 'optimum-lift'), [
            'instructions' => __('Adds a link to this section in the header.', 'optimum-lift'),
            'wrapper'      => ['width' => '25'],
        ]),
        optimum_lift_acf_field('text', 'eyebrow', __('Eyebrow', 'optimum-lift'), [
            'wrapper' => ['width' => '25'],
        ]),
        optimum_lift_acf_select('tone', __('Background', 'optimum-lift'), [
            'default' => __('Page', 'optimum-lift'),
            'alt'     => __('Band', 'optimum-lift'),
        ], ['wrapper' => ['width' => '25']]),
        optimum_lift_acf_textarea('heading', __('Heading', 'optimum-lift'), [
            'instructions' => optimum_lift_acf_accent_note()
                . ' ' . __('A new line breaks the heading.', 'optimum-lift'),
            'rows'         => 2,
            'wrapper'      => ['width' => '50'],
        ]),
        optimum_lift_acf_textarea('intro', __('Intro', 'optimum-lift'), [
            'wrapper' => ['width' => '50'],
        ]),
    ];

    return [
        'key'        => 'layout_olt_' . $name,
        'name'       => $name,
        'label'      => $label,
        'display'    => 'block',
        'sub_fields' => optimum_lift_acf_keys(array_merge($common, $sub_fields), 'field_olt_' . $name),
    ];
}

/**
 * Assigns every field its key from its name, recursing into sub fields.
 * Top-level names drop their "ol_" prefix.
 *
 * @param list<array<string, mixed>> $fields
 * @return list<array<string, mixed>>
 */
function optimum_lift_acf_keys(array $fields, string $prefix, bool $top_level = false): array
{
    foreach ($fields as $i => $field) {
        $name = (string) $field['name'];
        if ($top_level && str_starts_with($name, 'ol_')) {
            $name = substr($name, 3);
        }

        $fields[$i]['key'] = $prefix . '_' . $name;
        if (isset($field['sub_fields']) && is_array($field['sub_fields'])) {
            $fields[$i]['sub_fields'] = optimum_lift_acf_keys(array_values($field['sub_fields']), $fields[$i]['key']);
        }
    }

    return $fields;
}

/**
 * @param array<string, mixed> $extra
 * @return array<string, mixed>
 */
function optimum_lift_acf_field(string $type, string $name, string $label, array $extra = []): array
{
    return array_merge(['type' => $type, 'name' => $name, 'label' => $label], $extra);
}

/**
 * @param list<array<string, mixed>> $sub_fields
 * @param array<string, mixed> $extra
 * @return array<string, mixed>
 */
function optimum_lift_acf_repeater(string $name, string $label, array $sub_fields, array $extra = []): array
{
    return optimum_lift_acf_field('repeater', $name, $label, array_merge([
        'layout'     => 'table',
        'sub_fields' => $sub_fields,
    ], $extra));
}

/**
 * @param array<string, mixed> $extra
 * @return array<string, mixed>
 */
function optimum_lift_acf_textarea(string $name, string $label, array $extra = []): array
{
    return optimum_lift_acf_field('textarea', $name, $label, array_merge(['rows' => 3, 'new_lines' => ''], $extra));
}

/**
 * A textarea read as a list, one item per line.
 *
 * @return array<string, mixed>
 */
function optimum_lift_acf_lines(string $name, string $label): array
{
    return optimum_lift_acf_textarea($name, $label, [
        'instructions' => __('One per line.', 'optimum-lift'),
        'rows'         => 4,
    ]);
}

/**
 * @return array<string, mixed>
 */
function optimum_lift_acf_wysiwyg(string $name, string $label): array
{
    return optimum_lift_acf_field('wysiwyg', $name, $label, [
        'tabs'         => 'all',
        'toolbar'      => 'basic',
        'media_upload' => 0,
        'delay'        => 1,
    ]);
}

/**
 * @param array<string, string> $choices The first choice is the default.
 * @param array<string, mixed> $extra
 * @return array<string, mixed>
 */
function optimum_lift_acf_select(string $name, string $label, array $choices, array $extra = []): array
{
    return optimum_lift_acf_field('select', $name, $label, array_merge([
        'choices'       => $choices,
        'default_value' => array_key_first($choices),
        'return_format' => 'value',
    ], $extra));
}

/**
 * @param array<string, mixed> $extra
 * @return array<string, mixed>
 */
function optimum_lift_acf_bool(string $name, string $label, int $default = 0, array $extra = []): array
{
    return optimum_lift_acf_field('true_false', $name, $label, array_merge([
        'ui'            => 1,
        'default_value' => $default,
    ], $extra));
}

/**
 * @return array<string, mixed>
 */
function optimum_lift_acf_image(string $name, string $label): array
{
    return optimum_lift_acf_field('image', $name, $label, [
        'return_format' => 'id',
        'preview_size'  => 'thumbnail',
        'library'       => 'all',
    ]);
}

/**
 * @param array<string, mixed> $extra
 * @return array<string, mixed>
 */
function optimum_lift_acf_product(string $name, string $label, array $extra = []): array
{
    return optimum_lift_acf_field('post_object', $name, $label, array_merge([
        'post_type'     => ['product'],
        'post_status'   => ['publish'],
        'return_format' => 'id',
        'allow_null'    => 1,
        'multiple'      => 0,
        'ui'            => 1,
    ], $extra));
}

/**
 * @return array<string, mixed>
 */
function optimum_lift_acf_percent(string $name, string $label): array
{
    return optimum_lift_acf_field('number', $name, $label, ['min' => 0, 'max' => 100, 'step' => 1]);
}

/**
 * An icon picker offering the SVGs in assets/icons/.
 *
 * @return array<string, mixed>
 */
function optimum_lift_acf_icon(): array
{
    $files = glob(OPTIMUM_LIFT_DIR . '/assets/icons/*.svg');
    $names = array_map(static fn (string $file): string => basename($file, '.svg'), is_array($files) ? $files : []);
    sort($names);

    return optimum_lift_acf_field('select', 'icon', __('Icon', 'optimum-lift'), [
        'choices'       => array_combine($names, $names),
        'allow_null'    => 1,
        'return_format' => 'value',
    ]);
}

function optimum_lift_acf_tokens_note(): string
{
    return __('Tokens: {price}, {regular_price}, {saving}, {bundle_price}, {guarantee_days}, {customers}, {rating}, {reviews}, {store_rating}, {store_reviews}, {rest}. A phrase between " · " whose token has no value is left out.', 'optimum-lift');
}

function optimum_lift_acf_accent_note(): string
{
    return __('Put *words* between asterisks to show them in the accent colour.', 'optimum-lift');
}
