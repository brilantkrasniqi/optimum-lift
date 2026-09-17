<?php

/**
 * How it works: numbered steps, a call to action, and, when enabled, how many
 * customers started in the last 24 hours. That line needs at least the
 * threshold of real paid orders (ADR-0008); below it, nothing replaces it.
 *
 * The step cards cycle through the mock's three looks (white, accent, acid
 * icon), so a fourth step reads like the first.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block     = $args['block'];
$eyebrow   = is_string($block['eyebrow'] ?? null) ? trim($block['eyebrow']) : '';
$heading   = is_string($block['heading'] ?? null) ? trim($block['heading']) : '';
$intro     = is_string($block['intro'] ?? null) ? trim($block['intro']) : '';
$cta_label = is_string($block['cta_label'] ?? null) ? trim($block['cta_label']) : '';
$cta_url   = is_string($block['cta_anchor'] ?? null) && trim($block['cta_anchor']) !== ''
    ? '#' . sanitize_title(ltrim(trim($block['cta_anchor']), '#'))
    : '';
$recent    = !empty($block['show_recent']) ? optimum_lift_recent_orders_count() : null;

$styles = [
    [
        'card' => 'reveal relative rounded-3xl border border-white/[.08] bg-paper p-7',
        'icon' => 'grid h-12 w-12 place-items-center rounded-2xl bg-white text-paper',
    ],
    [
        'card' => 'reveal relative rounded-3xl border border-accent/30 bg-linear-to-b from-accent/[.12] to-paper p-7',
        'icon' => 'grid h-12 w-12 place-items-center rounded-2xl bg-accent text-white shadow-glow',
    ],
    [
        'card' => 'reveal relative rounded-3xl border border-white/[.08] bg-paper p-7',
        'icon' => 'grid h-12 w-12 place-items-center rounded-2xl bg-acid text-paper',
    ],
];

$steps = [];
foreach (is_array($block['steps'] ?? null) ? $block['steps'] : [] as $row) {
    if (!is_array($row)) {
        continue;
    }

    $title = is_string($row['title'] ?? null) ? trim($row['title']) : '';
    $body  = is_string($row['text'] ?? null) ? trim($row['text']) : '';

    if ($title !== '' || $body !== '') {
        $steps[] = [
            'icon'  => is_string($row['icon'] ?? null) ? $row['icon'] : '',
            'title' => $title,
            'text'  => $body,
            'style' => $styles[count($steps) % count($styles)],
        ];
    }
}

$grid = match (count($steps)) {
    1       => 'mx-auto max-w-md',
    2       => 'md:grid-cols-2',
    4       => 'md:grid-cols-2 lg:grid-cols-4',
    default => 'md:grid-cols-3',
};
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'steps')); ?>" class="<?php echo esc_attr(trim('relative overflow-hidden py-20 md:py-28 ' . optimum_lift_block_tone_class($block))); ?>">
    <div class="pointer-events-none absolute -bottom-32 left-1/2 h-[40rem] w-[40rem] -translate-x-1/2 rounded-full bg-accent/10 blur-[130px]" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-4">
        <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
            <div class="reveal mx-auto max-w-2xl text-center">
                <?php if ($eyebrow !== '') : ?>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h2 class="<?php echo esc_attr($eyebrow !== '' ? 'mt-5 h-display text-3xl text-white sm:text-5xl' : 'h-display text-3xl text-white sm:text-5xl'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($intro !== '') : ?>
                    <p class="mt-5 text-[15px] leading-relaxed text-zinc-400"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($steps !== []) : ?>
            <ol class="<?php echo esc_attr('mt-14 grid gap-6 ' . $grid); ?>">
                <?php foreach ($steps as $i => $step) : ?>
                    <li class="<?php echo esc_attr($step['style']['card']); ?>">
                        <span class="h-display absolute -top-5 right-6 text-6xl text-white/[.07]" aria-hidden="true"><?php echo esc_html(sprintf('%02d', $i + 1)); ?></span>
                        <?php if ($step['icon'] !== '') : ?>
                            <span class="<?php echo esc_attr($step['style']['icon']); ?>"><?php echo optimum_lift_icon($step['icon'], 'w-6 h-6'); ?></span>
                        <?php endif; ?>
                        <?php if ($step['title'] !== '') : ?>
                            <h3 class="<?php echo esc_attr($step['icon'] !== '' ? 'mt-5 h-display text-xl text-white' : 'h-display text-xl text-white'); ?>"><?php echo esc_html($step['title']); ?></h3>
                        <?php endif; ?>
                        <?php if ($step['text'] !== '') : ?>
                            <p class="mt-2.5 text-sm leading-relaxed text-zinc-400"><?php echo esc_html($step['text']); ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php if (($cta_label !== '' && $cta_url !== '') || $recent !== null) : ?>
            <div class="reveal mt-12 text-center">
                <?php if ($cta_label !== '' && $cta_url !== '') : ?>
                    <a href="<?php echo esc_url($cta_url); ?>" data-cta="steps" class="btn btn-light btn-lg gap-2.5">
                        <?php echo esc_html($cta_label); ?>
                        <?php echo optimum_lift_icon('arrow-right', 'w-5 h-5 shrink-0'); ?>
                    </a>
                <?php endif; ?>
                <?php if ($recent !== null) : ?>
                    <p class="mt-3 text-[12px] font-semibold text-zinc-500"><?php
                        /* translators: %s: number of customers who paid for an order in the last 24 hours. */
                        echo esc_html(sprintf(_n('%s customer started in the last 24 hours', '%s customers started in the last 24 hours', $recent, 'optimum-lift'), optimum_lift_format_number($recent)));
                    ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
