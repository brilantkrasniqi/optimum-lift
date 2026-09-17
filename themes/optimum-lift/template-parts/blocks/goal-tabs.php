<?php

/**
 * Self-segmentation: one tab per goal, each panel with what the plan does for
 * it and a call to action (the tab's Product, else an anchor).
 *
 * WAI-ARIA tabs driven by modules/tabs.js. Without JavaScript the tab list is
 * not shown and every panel is stacked, each under its own heading. With it,
 * panels after the first are hidden from first paint (`js:hidden`), before the
 * module takes over with the `hidden` attribute.
 *
 * The "customer average" and "typical result" figures are editor placeholders
 * (spec: placeholder content).
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block   = $args['block'];
$eyebrow = is_string($block['eyebrow'] ?? null) ? trim($block['eyebrow']) : '';
$heading = is_string($block['heading'] ?? null) ? trim($block['heading']) : '';
$intro   = is_string($block['intro'] ?? null) ? trim($block['intro']) : '';
$id      = optimum_lift_block_id($block, 'goal-tabs');
// Tab ids must be unique even when two tab sections share an anchor.
$prefix = 'ol-tabs-' . $args['index'];

$tabs = [];
foreach (is_array($block['tabs'] ?? null) ? $block['tabs'] : [] as $row) {
    if (!is_array($row)) {
        continue;
    }

    $field = static fn (string $key): string => is_string($row[$key] ?? null) ? trim($row[$key]) : '';
    $label = $field('label');

    if ($label === '') {
        continue;
    }

    $product = is_numeric($row['product'] ?? null) ? wc_get_product((int) $row['product']) : null;
    $product = $product instanceof WC_Product && $product->get_status() === 'publish' ? $product : null;
    $anchor  = $field('cta_anchor');
    $stat    = $field('stat');
    // "Label: value" shows the value in white, as in the mock.
    $stat_parts = $stat !== '' ? explode(': ', $stat, 2) : [];
    $image_id   = is_numeric($row['image'] ?? null) ? (int) $row['image'] : 0;

    $tabs[] = [
        'label'       => $label,
        'heading'     => $field('heading'),
        'text'        => $field('text'),
        'bullets'     => optimum_lift_lines($field('bullets')),
        'cta_label'   => $field('cta_label'),
        'cta_url'     => $product !== null ? $product->get_permalink() : ($anchor !== '' ? '#' . sanitize_title(ltrim($anchor, '#')) : ''),
        'stat_label'  => count($stat_parts) === 2 ? $stat_parts[0] : $stat,
        'stat_value'  => count($stat_parts) === 2 ? $stat_parts[1] : '',
        'image'       => $image_id > 0 ? wp_get_attachment_image($image_id, 'large', false, [
            'class' => 'absolute inset-0 h-full w-full object-cover',
            'sizes' => '(min-width: 1024px) 540px, 100vw',
        ]) : '',
        'product'     => $product,
        'image_label' => $field('image_label'),
        'image_value' => $field('image_value'),
    ];
}

if ($tabs === []) {
    return;
}

$tablist_grid = match (count($tabs)) {
    1       => 'grid-cols-1',
    2       => 'grid-cols-2',
    4       => 'grid-cols-2 sm:grid-cols-4',
    default => 'grid-cols-3',
};
?>
<section id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr(trim('py-20 md:py-28 ' . optimum_lift_block_tone_class($block))); ?>">
    <div class="mx-auto max-w-7xl px-4">
        <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
            <div class="reveal mx-auto max-w-2xl text-center">
                <?php if ($eyebrow !== '') : ?>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h2 id="<?php echo esc_attr($prefix . '-heading'); ?>" class="<?php echo esc_attr($eyebrow !== '' ? 'mt-5 h-display text-3xl text-white sm:text-5xl' : 'h-display text-3xl text-white sm:text-5xl'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($intro !== '') : ?>
                    <p class="mt-5 text-[15px] leading-relaxed text-zinc-400"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div data-tabs>
            <?php if (count($tabs) > 1) : ?>
                <div class="reveal mt-9 hidden justify-center js:flex">
                    <div role="tablist"<?php echo $heading !== '' ? ' aria-labelledby="' . esc_attr($prefix . '-heading') . '"' : ''; ?> class="<?php echo esc_attr('inline-grid gap-1 rounded-2xl border border-white/10 bg-surface p-1.5 text-center ' . $tablist_grid); ?>">
                        <?php foreach ($tabs as $i => $tab) : ?>
                            <button type="button" role="tab" id="<?php echo esc_attr($prefix . '-tab-' . $i); ?>" aria-controls="<?php echo esc_attr($prefix . '-panel-' . $i); ?>" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>" tabindex="<?php echo $i === 0 ? '0' : '-1'; ?>" data-cta="<?php echo esc_attr('goal-tab-' . ($i + 1)); ?>" class="min-h-11 rounded-xl px-3 py-3 text-[12px] font-extrabold text-zinc-400 transition hover:bg-white/5 hover:text-white sm:px-6 sm:text-sm aria-selected:bg-accent aria-selected:text-white aria-selected:shadow-glow aria-selected:hover:bg-accent">
                                <?php echo esc_html($tab['label']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($tabs as $i => $tab) : ?>
                <div<?php echo count($tabs) > 1 ? ' role="tabpanel" id="' . esc_attr($prefix . '-panel-' . $i) . '" aria-labelledby="' . esc_attr($prefix . '-tab-' . $i) . '" tabindex="0"' : ''; ?> class="<?php echo esc_attr($i === 0 ? 'mt-10 grid items-center gap-8 lg:grid-cols-[1fr_.8fr]' : 'mt-10 grid items-center gap-8 lg:grid-cols-[1fr_.8fr] js:hidden'); ?>">
                    <div class="rounded-3xl border border-white/[.08] bg-surface p-7 sm:p-9">
                        <?php if ($tab['heading'] !== '') : ?>
                            <h3 class="h-display text-2xl text-white sm:text-3xl"><?php echo esc_html($tab['heading']); ?></h3>
                        <?php endif; ?>
                        <?php if ($tab['text'] !== '') : ?>
                            <p class="mt-3 text-sm leading-relaxed text-zinc-400"><?php echo esc_html($tab['text']); ?></p>
                        <?php endif; ?>
                        <?php if ($tab['bullets'] !== []) : ?>
                            <ul class="mt-6 grid gap-3 text-[13.5px] font-semibold text-zinc-300 sm:grid-cols-2">
                                <?php foreach ($tab['bullets'] as $bullet) : ?>
                                    <li class="flex gap-2.5"><?php echo optimum_lift_icon('check', 'w-4 h-4 shrink-0 mt-0.5 text-acid'); ?><?php echo esc_html($bullet); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (($tab['cta_label'] !== '' && $tab['cta_url'] !== '') || $tab['stat_label'] !== '') : ?>
                            <div class="mt-7 flex flex-wrap items-center gap-4">
                                <?php if ($tab['cta_label'] !== '' && $tab['cta_url'] !== '') : ?>
                                    <a href="<?php echo esc_url($tab['cta_url']); ?>" data-cta="<?php echo esc_attr('goal-' . ($i + 1)); ?>" class="btn btn-primary rounded-xl px-6 py-3.5"><?php echo esc_html($tab['cta_label']); ?></a>
                                <?php endif; ?>
                                <?php if ($tab['stat_label'] !== '') : ?>
                                    <p class="text-[12px] font-bold text-zinc-500">
                                        <?php if ($tab['stat_value'] !== '') : ?>
                                            <?php echo esc_html($tab['stat_label']); ?>: <span class="text-white"><?php echo esc_html($tab['stat_value']); ?></span>
                                        <?php else : ?>
                                            <?php echo esc_html($tab['stat_label']); ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="ph-photo relative aspect-[4/5] overflow-hidden rounded-3xl border border-white/10">
                        <?php if ($tab['image'] !== '') : ?>
                            <?php echo $tab['image']; ?>
                        <?php elseif ($tab['product'] !== null) : ?>
                            <span class="absolute inset-0 grid place-items-center text-zinc-700" aria-hidden="true">
                                <?php get_template_part('template-parts/product/kind-icon', null, ['product' => $tab['product'], 'class' => 'w-16 h-16', 'attrs' => ['stroke-width' => '1.5']]); ?>
                            </span>
                        <?php else : ?>
                            <span class="absolute inset-0 grid place-items-center text-zinc-700" aria-hidden="true"><?php echo optimum_lift_icon('avatar', 'w-16 h-16'); ?></span>
                        <?php endif; ?>
                        <?php if ($tab['image_label'] !== '' || $tab['image_value'] !== '') : ?>
                            <div class="absolute right-4 bottom-4 left-4 rounded-2xl bg-black/65 p-3.5 backdrop-blur">
                                <?php if ($tab['image_label'] !== '') : ?>
                                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-zinc-400"><?php echo esc_html($tab['image_label']); ?></p>
                                <?php endif; ?>
                                <?php if ($tab['image_value'] !== '') : ?>
                                    <p class="h-display text-2xl text-white"><?php echo esc_html($tab['image_value']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
