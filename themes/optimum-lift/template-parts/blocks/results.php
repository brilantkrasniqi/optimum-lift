<?php

/**
 * Before/after results: each card pairs the before and after photos (or
 * placeholders labelled "Para" / "Pas") with the name, details, result and
 * quote, then an optional call to action.
 *
 * Results are editor content the theme cannot verify (ADR-0008), so the
 * disclaimer always shows: the block's own, else the default sentence.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block   = $args['block'] ?? [];
$post_id = (int) ($args['post_id'] ?? 0);

$text  = static fn (mixed $value): string => is_string($value) ? trim($value) : '';
$image = static fn (mixed $value): int => is_numeric($value) ? (int) $value : 0;

$items = [];
foreach (is_array($block['items'] ?? null) ? $block['items'] : [] as $row) {
    if (!is_array($row)) {
        continue;
    }

    $item = [
        'name'   => $text($row['name'] ?? null),
        'meta'   => $text($row['meta'] ?? null),
        'result' => $text($row['result'] ?? null),
        'quote'  => $text($row['quote'] ?? null),
        'before' => $image($row['before_image'] ?? null),
        'after'  => $image($row['after_image'] ?? null),
    ];

    if ($item['name'] !== '' || $item['result'] !== '' || $item['quote'] !== '') {
        $items[] = $item;
    }
}

if ($items === []) {
    return;
}

$eyebrow    = $text($block['eyebrow'] ?? null);
$heading    = $text($block['heading'] ?? null);
$intro      = $text($block['intro'] ?? null);
$disclaimer = $text($block['disclaimer'] ?? null);
$disclaimer = $disclaimer !== '' ? $disclaimer : __('Results vary from person to person and depend on following the plan.', 'optimum-lift');
$alt        = optimum_lift_block_tone_class($block) !== '';

// Without an anchor the button leads to the page's buying point: the Product's
// price box, or the front page's pricing section.
$cta_label  = $text($block['cta_label'] ?? null);
$cta_anchor = sanitize_title(ltrim($text($block['cta_anchor'] ?? null), '#'));
if ($cta_label !== '' && $cta_anchor === '') {
    $cta_anchor = $post_id > 0 && $post_id === optimum_lift_front_page_id()
        ? optimum_lift_section_anchor($post_id, 'pricing')
        : 'blej';
}

$photos = [
    'before' => [
        'label' => _x('Before', 'result photo', 'optimum-lift'),
        'class' => 'absolute top-2.5 left-2.5 rounded bg-black/65 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-widest text-zinc-300',
    ],
    'after'  => [
        'label' => _x('After', 'result photo', 'optimum-lift'),
        'class' => 'absolute top-2.5 left-2.5 rounded bg-accent px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-widest text-white',
    ],
];
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'results')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="mx-auto max-w-7xl px-4">
        <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
            <div class="reveal mx-auto max-w-2xl text-center">
                <?php if ($eyebrow !== '') : ?>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h2 class="mt-5 h-display text-3xl text-white first:mt-0 sm:text-5xl"><?php echo optimum_lift_heading_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($intro !== '') : ?>
                    <p class="mt-4 text-[15px] leading-relaxed text-zinc-400 first:mt-0"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="mt-12 grid gap-5 first:mt-0 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($items as $i => $item) : ?>
                <?php
                $card = $alt
                    ? 'reveal overflow-hidden rounded-3xl border border-white/[.08] bg-paper'
                    : 'reveal overflow-hidden rounded-3xl border border-white/[.08] bg-surface';
                // An odd last card spans the two-column row instead of leaving a gap.
                if ($i === count($items) - 1 && count($items) % 2 === 1) {
                    $card .= ' sm:col-span-2 lg:col-span-1';
                }
                ?>
                <article class="<?php echo esc_attr($card); ?>">
                    <div class="grid grid-cols-2 gap-px bg-white/[.07]">
                        <?php foreach ($photos as $key => $photo) : ?>
                            <div class="ph-photo relative aspect-square overflow-hidden">
                                <?php
                                if ($item[$key] > 0) {
                                    echo wp_get_attachment_image($item[$key], 'medium_large', false, [
                                        'class'   => 'absolute inset-0 h-full w-full object-cover',
                                        'sizes'   => '(min-width: 1024px) 200px, (min-width: 640px) 25vw, 50vw',
                                        'loading' => 'lazy',
                                    ]);
                                }
                                ?>
                                <span class="<?php echo esc_attr($photo['class']); ?>"><?php echo esc_html($photo['label']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="p-5">
                        <?php if ($item['name'] !== '' || $item['meta'] !== '' || $item['result'] !== '') : ?>
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <?php if ($item['name'] !== '') : ?>
                                        <h3 class="font-extrabold text-white"><?php echo esc_html($item['name']); ?></h3>
                                    <?php endif; ?>
                                    <?php if ($item['meta'] !== '') : ?>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-500"><?php echo esc_html($item['meta']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($item['result'] !== '') : ?>
                                    <p class="shrink-0 rounded-xl bg-acid px-3 py-1.5 text-center h-display text-base leading-none text-paper"><?php echo esc_html($item['result']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($item['quote'] !== '') : ?>
                            <p class="mt-3.5 text-[13px] leading-relaxed text-zinc-400 first:mt-0"><?php
                                /* translators: %s: a customer's words, shown in quotation marks. */
                                echo esc_html(sprintf(_x('“%s”', 'quotation', 'optimum-lift'), $item['quote']));
                            ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($cta_label !== '' && $cta_anchor !== '') : ?>
            <div class="reveal mt-9 text-center">
                <a href="<?php echo esc_url('#' . $cta_anchor); ?>" data-cta="results" class="btn btn-primary btn-lg shadow-glow">
                    <?php echo esc_html($cta_label); ?>
                    <?php echo optimum_lift_icon('arrow-right', 'w-5 h-5 shrink-0'); ?>
                </a>
                <p class="mt-3 text-[11px] text-zinc-500"><?php echo esc_html($disclaimer); ?></p>
            </div>
        <?php else : ?>
            <p class="reveal mt-7 text-center text-[11px] text-zinc-500"><?php echo esc_html($disclaimer); ?></p>
        <?php endif; ?>
    </div>
</section>
