<?php

/**
 * The value stack: everything included, each with its value, the total, and
 * what the buyer pays today, then Buy Now for that Product.
 *
 * The total is the sum of the rows and "you pay" is the Product's current
 * price, so neither can drift from the numbers beside them; the struck total
 * shows only while it is above the price. `product` defaults to the page's
 * context Product (the bundle on the front page). No `cta_label`, no button.
 *
 * `split` puts the copy beside `side_image`, or without one, beside a mockup
 * of the plan drawn from real sections of this page, the Product and its
 * bundle components (a sample Workout, a sample day's macros, the shopping
 * list). With neither, it falls back to the centred layout.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block = $args['block'] ?? [];

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

$items = [];
foreach (is_array($block['items'] ?? null) ? $block['items'] : [] as $row) {
    $item = is_array($row) ? $text($row['text'] ?? null) : '';
    if ($item !== '') {
        $items[] = [
            'text'  => $item,
            'value' => is_numeric($row['value'] ?? null) ? max(0.0, (float) $row['value']) : 0.0,
            'bonus' => !empty($row['bonus']),
        ];
    }
}

if ($items === []) {
    return;
}

$product = is_numeric($block['product'] ?? null) ? wc_get_product((int) $block['product']) : null;
if (!$product instanceof WC_Product) {
    $product = $args['product'] ?? null;
}
if (!$product instanceof WC_Product || $product->get_price() === '') {
    $product = null;
}

$total      = round(array_sum(array_column($items, 'value')), wc_get_price_decimals());
$price      = $product !== null ? optimum_lift_current_price($product) : null;
$show_total = $total > 0 && ($price === null || $total > $price);

$eyebrow     = $text($block['eyebrow'] ?? null);
$heading     = $text($block['heading'] ?? null);
$intro       = $text($block['intro'] ?? null);
$total_label = $text($block['total_label'] ?? null);
$pay_label   = $text($block['pay_label'] ?? null);
$cta_label   = $text($block['cta_label'] ?? null);
$cta_product = $product !== null && $cta_label !== '' && $product->is_purchasable() && $product->is_in_stock() ? $product : null;

$side_image = '';
$mockup     = null;

if (($block['layout'] ?? '') === 'split') {
    $image_id   = is_numeric($block['side_image'] ?? null) ? (int) $block['side_image'] : 0;
    $side_image = $image_id > 0
        ? wp_get_attachment_image($image_id, 'large', false, [
            'class'   => 'mx-auto h-auto w-full max-w-md rounded-[2rem] border border-white/10 shadow-card',
            'sizes'   => '(min-width: 1024px) 448px, 100vw',
            'loading' => 'lazy',
        ])
        : '';

    if ($side_image === '') {
        $sources = [(int) ($args['post_id'] ?? 0)];
        if ($product !== null) {
            $sources[] = $product->get_id();
            foreach (optimum_lift_bundle_components($product) as $component) {
                $sources[] = $component->get_id();
            }
        }

        $mockup = ['workout_label' => '', 'workout_tag' => '', 'workout' => [], 'kcal' => '', 'macros' => [], 'shopping' => []];

        foreach (array_unique($sources) as $source) {
            foreach (optimum_lift_blocks($source) as $section) {
                $layout = $section['acf_fc_layout'];

                if ($layout === 'preview' && $mockup['workout'] === []) {
                    foreach (is_array($section['workout_rows'] ?? null) ? $section['workout_rows'] : [] as $row) {
                        $name = is_array($row) ? $text($row['name'] ?? null) : '';
                        if ($name !== '' && count($mockup['workout']) < 3) {
                            $mockup['workout'][] = ['name' => $name, 'scheme' => $text($row['scheme'] ?? null)];
                        }
                    }
                    $mockup['workout_label'] = $text($section['workout_label'] ?? null);
                    $mockup['workout_tag']   = $text($section['workout_tag'] ?? null);
                } elseif ($layout === 'sample_day' && $mockup['kcal'] === '') {
                    $mockup['kcal']   = $text($section['kcal'] ?? null);
                    $mockup['macros'] = array_filter([
                        __('Protein', 'optimum-lift')                    => $text($section['protein'] ?? null),
                        __('Carbs', 'optimum-lift')                      => $text($section['carbs'] ?? null),
                        _x('Fat', 'macronutrient total', 'optimum-lift') => $text($section['fat'] ?? null),
                    ], static fn (string $value): bool => $value !== '');
                } elseif ($layout === 'shopping_list' && $mockup['shopping'] === []) {
                    $mockup['shopping'] = array_slice(optimum_lift_lines(is_string($section['items'] ?? null) ? $section['items'] : null), 0, 3);
                }
            }
        }

        if ($mockup['workout'] === [] && $mockup['kcal'] === '') {
            $mockup = null;
        }
    }
}

$split = $side_image !== '' || $mockup !== null;
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'value-stack')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="<?php echo esc_attr($split ? 'mx-auto grid max-w-7xl items-center gap-12 px-4 lg:grid-cols-2 lg:gap-16' : 'mx-auto max-w-3xl px-4'); ?>">
        <div class="<?php echo esc_attr($split ? 'reveal' : 'contents'); ?>">
            <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
                <div class="<?php echo esc_attr($split ? 'max-w-2xl' : 'reveal mx-auto max-w-2xl text-center'); ?>">
                    <?php if ($eyebrow !== '') : ?>
                        <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                    <?php endif; ?>
                    <?php if ($heading !== '') : ?>
                        <h2 class="<?php echo esc_attr($split ? 'mt-5 h-display text-3xl text-white first:mt-0 sm:text-5xl' : 'mt-5 h-display text-3xl text-white first:mt-0 sm:text-4xl'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
                    <?php endif; ?>
                    <?php if ($intro !== '') : ?>
                        <p class="mt-5 text-[15px] leading-relaxed text-zinc-400 first:mt-0"><?php echo esc_html($intro); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="<?php echo esc_attr($split ? 'mt-8 rounded-3xl border border-white/10 bg-paper p-2 first:mt-0' : 'reveal mt-10 rounded-3xl border border-white/10 bg-paper p-2 first:mt-0'); ?>">
                <ul class="divide-y divide-white/[.07]">
                    <?php foreach ($items as $item) : ?>
                        <li class="flex items-center justify-between gap-4 p-4">
                            <span class="flex items-start gap-3 text-[13.5px] font-semibold text-zinc-200">
                                <?php echo optimum_lift_icon('check', 'mt-0.5 h-4 w-4 shrink-0 text-acid'); ?>
                                <span>
                                    <?php if ($item['bonus']) : ?>
                                        <span class="uppercase text-acid"><?php esc_html_e('Bonus:', 'optimum-lift'); ?></span>
                                    <?php endif; ?>
                                    <?php echo esc_html($item['text']); ?>
                                </span>
                            </span>
                            <?php if ($item['value'] > 0) : ?>
                                <s class="shrink-0 text-[12px] font-bold text-zinc-500">
                                    <span class="screen-reader-text"><?php esc_html_e('Value:', 'optimum-lift'); ?></span>
                                    <?php echo wp_kses_post(wc_price($item['value'])); ?>
                                </s>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($show_total || $price !== null) : ?>
                    <div class="m-2 mt-3 flex flex-wrap items-end justify-between gap-4 rounded-2xl bg-linear-to-r from-accent to-accent/70 p-5">
                        <?php if ($show_total) : ?>
                            <div>
                                <div class="text-[10px] font-extrabold uppercase tracking-[.2em] text-white/70"><?php echo esc_html($total_label !== '' ? $total_label : __('Total value', 'optimum-lift')); ?></div>
                                <s class="h-display block text-2xl text-white/60"><?php echo wp_kses_post(wc_price($total)); ?></s>
                            </div>
                        <?php endif; ?>
                        <?php if ($price !== null) : ?>
                            <div class="ml-auto text-right">
                                <div class="text-[10px] font-extrabold uppercase tracking-[.2em] text-white/70"><?php echo esc_html($pay_label !== '' ? $pay_label : __('You pay today', 'optimum-lift')); ?></div>
                                <div class="h-display text-4xl text-white"><?php echo wp_kses_post(wc_price($price)); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($cta_product !== null) : ?>
                <div class="<?php echo esc_attr($split ? 'mt-7' : 'reveal mt-7 text-center'); ?>">
                    <a href="<?php echo esc_url(optimum_lift_buy_now_url($cta_product)); ?>" rel="nofollow" data-buy-now="<?php echo esc_attr((string) $cta_product->get_id()); ?>" data-cta="value-stack" class="btn btn-light btn-lg gap-2.5">
                        <span><?php echo optimum_lift_replace_tokens($cta_label, $cta_product); ?></span>
                        <?php echo optimum_lift_icon('arrow-right', 'h-5 w-5 shrink-0'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($side_image !== '') : ?>
            <div class="reveal"><?php echo $side_image; ?></div>
        <?php elseif ($mockup !== null) : ?>
            <div class="reveal relative" aria-hidden="true">
                <div class="relative mx-auto max-w-md">
                    <div class="rounded-[2rem] border border-white/10 bg-paper p-5 shadow-card">
                        <div class="flex items-center justify-between gap-3">
                            <span class="h-display text-sm text-white"><?php echo esc_html($product !== null ? get_bloginfo('name') . ' · ' . $product->get_name() : get_bloginfo('name')); ?></span>
                            <span class="rounded-full bg-acid px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-widest text-paper"><?php esc_html_e('PDF', 'optimum-lift'); ?></span>
                        </div>
                        <div class="mt-4 space-y-2.5">
                            <?php if ($mockup['workout'] !== []) : ?>
                                <div class="rounded-2xl border border-white/[.07] bg-raised p-4">
                                    <div class="flex items-center justify-between gap-3 text-[11px] font-extrabold uppercase tracking-wider text-zinc-500">
                                        <span><?php echo esc_html($mockup['workout_label']); ?></span>
                                        <span class="text-acid"><?php echo esc_html($mockup['workout_tag']); ?></span>
                                    </div>
                                    <div class="mt-2.5 space-y-1.5 text-[12px] font-semibold text-zinc-300">
                                        <?php foreach ($mockup['workout'] as $row) : ?>
                                            <div class="flex justify-between gap-3">
                                                <span><?php echo esc_html($row['name']); ?></span>
                                                <span class="shrink-0 font-mono text-zinc-500"><?php echo esc_html($row['scheme']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($mockup['kcal'] !== '') : ?>
                                <div class="rounded-2xl border border-white/[.07] bg-raised p-4">
                                    <div class="flex items-center justify-between gap-3 text-[11px] font-extrabold uppercase tracking-wider text-zinc-500">
                                        <span><?php esc_html_e('Macros of the day', 'optimum-lift'); ?></span>
                                        <span class="text-acid"><?php echo esc_html($mockup['kcal']); ?></span>
                                    </div>
                                    <?php if ($mockup['macros'] !== []) : ?>
                                        <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                                            <?php foreach ($mockup['macros'] as $macro => $amount) : ?>
                                                <div class="rounded-xl bg-black/40 px-1 py-2">
                                                    <div class="h-display text-base text-white"><?php echo esc_html($amount); ?></div>
                                                    <div class="text-[9px] font-bold uppercase tracking-wider text-zinc-500"><?php echo esc_html($macro); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($mockup['shopping'] !== []) : ?>
                        <div class="floaty absolute -right-3 -bottom-5 w-40 rounded-2xl border border-white/10 bg-surface/95 p-3 shadow-card backdrop-blur">
                            <div class="text-[9px] font-extrabold uppercase tracking-widest text-zinc-500"><?php esc_html_e('Shopping list', 'optimum-lift'); ?></div>
                            <div class="mt-1.5 space-y-1 text-[11px] font-semibold text-zinc-300">
                                <?php foreach ($mockup['shopping'] as $line) : ?>
                                    <div class="flex items-center gap-1.5"><span class="text-acid">✓</span><?php echo esc_html($line); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
