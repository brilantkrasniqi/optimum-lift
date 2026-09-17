<?php

/**
 * The closing call to action: heading, body, an optional offer countdown, the
 * primary button and a reassurance note.
 *
 * Its Product is the block's `product`, else the page's (the bundle on the
 * front page); the label and note tokens use it. The primary button is Buy Now
 * for that Product or a link to a section. With `secondary_add_to_cart` it sits
 * beside an add-to-cart button (produkt-dieta.html); alone it is the large
 * white button (produkt.html, index.html).
 *
 * The countdown shows only while there is a real offer (ADR-0008): the
 * Product's own on a Product page, which a bundle priced without a sale never
 * has, and the site offer on the front page.
 *
 * The Buy Now link is written here rather than with product/buy-buttons.php,
 * whose fixed button styles cannot make this design's buttons; it carries the
 * same attributes (spec, markup contracts).
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block   = $args['block'] ?? [];
$post_id = (int) ($args['post_id'] ?? 0);
$index   = (int) ($args['index'] ?? 0);

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

$product = is_numeric($block['product'] ?? null) ? wc_get_product((int) $block['product']) : null;
$product = $product instanceof WC_Product && $product->get_status() === 'publish' ? $product : ($args['product'] ?? null);
$product = $product instanceof WC_Product ? $product : null;
$buyable = $product !== null && $product->is_purchasable() && $product->is_in_stock();

$front   = $post_id > 0 && $post_id === optimum_lift_front_page_id();
$eyebrow = $text($block['eyebrow'] ?? null);
$heading = $text($block['heading'] ?? null);
$body    = $text($block['body'] ?? null);
$label   = $text($block['primary_label'] ?? null);
$label   = $label !== '' ? $label : __('Buy now', 'optimum-lift');

$href = '';
if (($block['primary_action'] ?? '') === 'anchor') {
    $anchor = sanitize_title(ltrim($text($block['primary_anchor'] ?? null), '#'));
    $href   = $anchor !== '' ? '#' . $anchor : '';
} elseif ($buyable && $product !== null) {
    $href = optimum_lift_buy_now_url($product);
}

if ($heading === '' && $body === '' && $href === '') {
    return;
}

$paired = $buyable && !empty($block['secondary_add_to_cart']);

$offer = null;
if (!empty($block['show_countdown'])) {
    $offer = $front ? optimum_lift_offer() : ($product !== null ? optimum_lift_offer($product) : null);
}

// The anchor price in the note is struck through, as in the design. The token
// prints wc_price() of the anchor price, so the same markup is wrapped here.
$note = optimum_lift_replace_tokens($text($block['note'] ?? null), $product);
if ($note !== '' && $product !== null && optimum_lift_saving($product) !== null) {
    $anchor_price = wc_price(optimum_lift_anchor_price($product));
    $note         = str_replace($anchor_price, '<del class="line-through">' . $anchor_price . '</del>', $note);
}

$tone     = optimum_lift_block_tone_class($block);
$previous = $index > 0 ? (optimum_lift_blocks($post_id)[$index - 1] ?? null) : null;
// A line between this and a section of the same background, where the glow begins.
$divided = $tone === '' && is_array($previous) && optimum_lift_block_tone_class($previous) === '';
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'final-cta')); ?>" class="<?php echo esc_attr('relative overflow-hidden py-20 md:py-28 ' . ($divided ? 'border-t border-white/[.07]' : $tone)); ?>">
    <?php if ($paired) : ?>
        <div class="pointer-events-none absolute inset-0 grain opacity-30" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-40 left-1/2 h-[46rem] w-[46rem] -translate-x-1/2 rounded-full bg-accent/15 blur-[140px]" aria-hidden="true"></div>
    <?php else : ?>
        <div class="pointer-events-none absolute inset-0 bg-linear-to-b from-accent/20 to-transparent" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-40 left-1/2 h-[44rem] w-[44rem] -translate-x-1/2 rounded-full bg-accent/20 blur-[140px]" aria-hidden="true"></div>
    <?php endif; ?>

    <div class="reveal relative mx-auto max-w-3xl px-4 text-center">
        <?php if ($eyebrow !== '') : ?>
            <span class="eyebrow mb-5"><?php echo esc_html($eyebrow); ?></span>
        <?php endif; ?>
        <?php if ($heading !== '') : ?>
            <h2 class="<?php echo esc_attr($front ? 'h-display text-4xl text-white sm:text-6xl' : 'h-display text-4xl text-white sm:text-5xl'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
        <?php endif; ?>
        <?php if ($body !== '') : ?>
            <?php
            $body_class = match (true) {
                $paired => 'mx-auto mt-5 max-w-xl text-[15px] leading-relaxed text-zinc-400 first:mt-0',
                $front  => 'mx-auto mt-6 max-w-xl text-[15px] leading-relaxed text-zinc-300 first:mt-0 sm:text-lg',
                default => 'mx-auto mt-6 max-w-lg text-[15px] leading-relaxed text-zinc-300 first:mt-0',
            };
            ?>
            <p class="<?php echo esc_attr($body_class); ?>"><?php echo esc_html($body); ?></p>
        <?php endif; ?>

        <?php if ($offer !== null) : ?>
            <div class="mt-9" data-countdown-scope>
                <?php
                get_template_part('template-parts/components/countdown', null, [
                    'ends_at' => $offer['ends_at'],
                    'variant' => 'boxes',
                    'class'   => 'mx-auto',
                ]);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($paired && $href !== '' && $product !== null) : ?>
            <div class="mt-9 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                <a href="<?php echo esc_url($href); ?>"<?php echo str_starts_with($href, '#') ? ' data-cta="final-cta"' : ' rel="nofollow" data-buy-now="' . esc_attr((string) $product->get_id()) . '" data-cta="final-buy-now"'; ?> class="btn btn-primary w-full gap-2.5 rounded-xl px-8 py-4 text-base sm:w-auto">
                    <span><?php echo optimum_lift_replace_tokens($label, $product); ?></span>
                    <?php echo optimum_lift_icon('arrow-right', 'w-5 h-5 shrink-0'); ?>
                </a>
                <?php
                get_template_part('template-parts/product/add-to-cart', null, [
                    'product' => $product,
                    'cta'     => 'final-add',
                    'class'   => 'btn btn-ghost w-full rounded-xl px-8 py-4 text-[15px] sm:w-auto',
                ]);
                ?>
            </div>
            <?php if ($note !== '') : ?>
                <p class="mt-5 text-[12px] font-bold uppercase tracking-wider text-zinc-500"><?php echo wp_kses_post($note); ?></p>
            <?php endif; ?>
        <?php else : ?>
            <?php if ($href !== '') : ?>
                <a href="<?php echo esc_url($href); ?>"<?php echo str_starts_with($href, '#') || $product === null ? ' data-cta="final-cta"' : ' rel="nofollow" data-buy-now="' . esc_attr((string) $product->get_id()) . '" data-cta="final-buy-now"'; ?> class="btn btn-light pulse mt-8 gap-2.5 rounded-2xl px-8 py-5 text-lg shadow-glow">
                    <span><?php echo optimum_lift_replace_tokens($label, $product); ?></span>
                    <?php echo optimum_lift_icon('arrow-right', 'w-5 h-5 shrink-0'); ?>
                </a>
            <?php endif; ?>
            <?php if ($note !== '') : ?>
                <p class="mt-4 text-[12px] font-bold text-zinc-400"><?php echo wp_kses_post($note); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
