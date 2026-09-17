<?php

/**
 * Fractional stars, the average, the labelled review count and, optionally,
 * the sold count.
 *
 * Pass `product` for its rating, or `rating` (e.g. the store rating). Each
 * number shows only above its proof threshold (ADR-0008), so this renders
 * nothing for a new Product. `size` is `sm` (cards), `md` (bundle banner) or
 * `lg` (Product hero). `link` turns the whole line into a link: a section
 * anchor with or without "#" (optimum_lift_block_id() returns it without), or
 * a URL.
 */

declare(strict_types=1);

/**
 * @var array{product?: WC_Product, rating?: array{average: float, count: int}|null, size?: string, link?: string, show_sold?: bool, class?: string} $args
 */

$product = $args['product'] ?? null;
$product = $product instanceof WC_Product ? $product : null;
$rating  = $args['rating'] ?? ($product !== null ? optimum_lift_rating($product) : null);
$sold    = $product !== null && !empty($args['show_sold']) ? optimum_lift_sold_count($product) : null;

if ($rating === null && $sold === null) {
    return;
}

$styles = [
    'sm' => [
        'line'  => 'flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-bold text-zinc-500',
        'group' => 'inline-flex items-center gap-2',
        'stars' => 'text-[11px]',
        'value' => 'text-zinc-300',
        'sold'  => 'text-zinc-500',
        'count' => 'text-zinc-300',
    ],
    'md' => [
        'line'  => 'flex flex-wrap items-center gap-x-3 gap-y-1 text-[11.5px] font-bold text-zinc-400',
        'group' => 'inline-flex items-center gap-1.5',
        'stars' => 'text-[11.5px]',
        'value' => 'text-zinc-200',
        'sold'  => 'text-zinc-400',
        'count' => 'text-zinc-200',
    ],
    'lg' => [
        'line'  => 'inline-flex flex-wrap items-center gap-2 text-[12px] font-bold text-zinc-300',
        'group' => 'inline-flex items-center gap-2',
        'stars' => 'text-[15px]',
        'value' => 'text-white',
        'sold'  => 'text-zinc-400',
        'count' => 'text-zinc-400',
    ],
];
$style = $styles[$args['size'] ?? 'sm'] ?? $styles['sm'];
$link  = $args['link'] ?? '';
$tag   = $link !== '' ? 'a' : 'div';

// A bare anchor would otherwise become a URL to a host named after it.
if ($link !== '' && preg_match('~^(#|/|[a-z][a-z0-9+.-]*:)~i', $link) !== 1) {
    $link = '#' . $link;
}

$line_class = $style['line'] . ($link !== '' ? ' hover:text-white transition' : '') . ' ' . ($args['class'] ?? '');
?>
<<?php echo $tag; ?> class="<?php echo esc_attr(trim($line_class)); ?>"<?php echo $link !== '' ? ' href="' . esc_url($link) . '"' : ''; ?>>
    <?php if ($rating !== null) : ?>
        <?php
        $average = optimum_lift_format_rating($rating['average']);
        // A plain-dot percentage: the CSS custom property must not follow the locale.
        $percent = rtrim(rtrim(sprintf('%.1F', max(0, min(100, $rating['average'] / 5 * 100))), '0'), '.');
        ?>
        <span class="<?php echo esc_attr($style['group']); ?>">
            <span class="olstars <?php echo esc_attr($style['stars']); ?>" style="--pct:<?php echo esc_attr($percent); ?>%" role="img" aria-label="<?php
                /* translators: %s: average rating, e.g. 4,7. */
                echo esc_attr(sprintf(__('%s out of 5 stars', 'optimum-lift'), $average));
            ?>">★★★★★</span>
            <span class="<?php echo esc_attr($style['value']); ?>" aria-hidden="true"><?php echo esc_html($average); ?></span>
            <span class="text-zinc-500">(<?php
                /* translators: %s: number of reviews. */
                echo esc_html(sprintf(_n('%s review', '%s reviews', $rating['count'], 'optimum-lift'), optimum_lift_format_number($rating['count'])));
            ?>)</span>
        </span>
    <?php endif; ?>

    <?php if ($rating !== null && $sold !== null) : ?>
        <span class="text-zinc-600" aria-hidden="true">·</span>
    <?php endif; ?>

    <?php if ($sold !== null) : ?>
        <?php
        /* translators: %s: number of copies sold. */
        $sold_text = esc_html(_n('%s sold', '%s sold', $sold, 'optimum-lift'));
        $sold_html = '<span class="' . esc_attr($style['count']) . '">' . esc_html(optimum_lift_format_number($sold)) . '</span>';
        ?>
        <span class="<?php echo esc_attr($style['sold']); ?>"><?php printf($sold_text, $sold_html); ?></span>
    <?php endif; ?>
</<?php echo $tag; ?>>
