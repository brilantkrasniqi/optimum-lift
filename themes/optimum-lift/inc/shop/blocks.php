<?php

/**
 * The section stack (ol_blocks) of a Product or the front page: one template
 * part per layout in template-parts/blocks/ (ADR-0006).
 */

declare(strict_types=1);

/**
 * The post's section rows, each with its layout name in acf_fc_layout.
 *
 * @return list<array<string, mixed>>
 */
function optimum_lift_blocks(int $post_id): array
{
    $rows = optimum_lift_field($post_id, 'ol_blocks');
    if (!is_array($rows)) {
        return [];
    }

    return array_values(array_filter(
        $rows,
        static fn ($row): bool => is_array($row)
            && is_string($row['acf_fc_layout'] ?? null)
            && $row['acf_fc_layout'] !== ''
    ));
}

/**
 * Renders every section through template-parts/blocks/<layout-with-dashes>.php.
 * A layout without a template part is skipped; with WP_DEBUG on, an HTML
 * comment names the missing part.
 */
function optimum_lift_render_blocks(int $post_id, ?WC_Product $context): void
{
    foreach (optimum_lift_blocks($post_id) as $index => $block) {
        $slug = 'template-parts/blocks/' . str_replace('_', '-', sanitize_key((string) $block['acf_fc_layout']));

        if (locate_template($slug . '.php') === '') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                printf("<!-- optimum-lift: missing template part %s.php -->\n", esc_html($slug));
            }
            continue;
        }

        get_template_part($slug, null, [
            'block'   => $block,
            'product' => $context,
            'post_id' => $post_id,
            'index'   => $index,
        ]);
    }
}

/**
 * Header navigation links from the sections that set a nav label and an
 * anchor. The anchor is the section id, without "#".
 *
 * @return list<array{label: string, anchor: string}>
 */
function optimum_lift_block_nav(int $post_id): array
{
    $links = [];
    foreach (optimum_lift_blocks($post_id) as $block) {
        $label  = is_string($block['nav_label'] ?? null) ? trim($block['nav_label']) : '';
        $anchor = optimum_lift_block_id($block);

        if ($label !== '' && $anchor !== '') {
            $links[] = ['label' => $label, 'anchor' => $anchor];
        }
    }

    return $links;
}

/**
 * The section's id attribute: its anchor field, else the fallback, as a slug.
 *
 * @param array<string, mixed> $block
 */
function optimum_lift_block_id(array $block, string $fallback = ''): string
{
    $anchor = is_string($block['anchor'] ?? null) ? trim($block['anchor']) : '';

    return sanitize_title($anchor !== '' ? $anchor : $fallback);
}

/**
 * The classes for the section's tone: '' by default, the band for 'alt'.
 *
 * @param array<string, mixed> $block
 */
function optimum_lift_block_tone_class(array $block): string
{
    return ($block['tone'] ?? '') === 'alt' ? 'border-y border-white/[.07] bg-surface/50' : '';
}
