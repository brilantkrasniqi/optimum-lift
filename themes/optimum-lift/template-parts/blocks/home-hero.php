<?php

/**
 * The homepage hero: proof, the promise, two calls to action and the
 * before/after visual, then the trust strip.
 *
 * Every persuasive number is real (ADR-0008): the customer count and store
 * rating come from the proof helpers, the percent on the call to action and on
 * the floating badge is the bundle's saving (both hidden without one), "only
 * tonight" shows only when the running offer ends within 24 hours, and the
 * strip's tokens are replaced (a stat whose token has no value is left out).
 * The before/after tiles, progress card and day plan are editor content.
 *
 * Above the fold, so nothing here uses .reveal.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block  = $args['block'];
$bundle = $args['product'] ?? null;

/** @param array<string, mixed>|null $row */
$text = static fn (string $key, ?array $row = null): string => is_string(($row ?? $block)[$key] ?? null) ? trim(($row ?? $block)[$key]) : '';
/** @return list<array<string, mixed>> */
$rows = static fn (string $key): array => is_array($block[$key] ?? null) ? array_values(array_filter($block[$key], 'is_array')) : [];
$href = static fn (string $anchor): string => $anchor !== '' ? '#' . sanitize_title(ltrim($anchor, '#')) : '';

$saving  = $bundle !== null ? optimum_lift_saving($bundle) : null;
$offer   = $bundle !== null ? optimum_lift_offer($bundle) ?? optimum_lift_offer() : null;
// "Only tonight" only when the offer really ends today, in the site's time zone (ADR-0008).
$tonight = $offer !== null && wp_date('Y-m-d', $offer['ends_at']) === wp_date('Y-m-d');

$show_proof = !empty($block['show_proof']);
$rating     = $show_proof ? optimum_lift_store_rating() : null;

$heading       = $text('heading');
$body          = $text('body');
$primary_label = $text('primary_label');
$primary_url   = $href($text('primary_anchor'));
if ($primary_label !== '' && $saving !== null) {
    /* translators: 1: call to action label, 2: discount percent. */
    $primary_label = sprintf(__('%1$s — %2$d%% off', 'optimum-lift'), $primary_label, $saving['percent']);
}
$secondary_label = $text('secondary_label');
$secondary_url   = $href($text('secondary_anchor'));
$reassurance     = optimum_lift_lines($text('reassurance'));

// The strip's {rest} counts the cards the pricing section on this page shows.
$featured_limit = 3;
foreach (optimum_lift_blocks($args['post_id']) as $other) {
    if ($other['acf_fc_layout'] === 'pricing') {
        $featured_limit = is_numeric($other['featured_limit'] ?? null) ? max(0, (int) $other['featured_limit']) : 3;
        break;
    }
}

$stats = [];
foreach ($rows('stats') as $row) {
    $value = $text('value', $row);
    $label = $text('label', $row);

    if ($value === '' || optimum_lift_has_empty_token($value . ' ' . $label, $bundle)) {
        continue;
    }

    if (str_contains($value . $label, '{rest}')) {
        $rest  = optimum_lift_format_number(optimum_lift_catalog_rest($featured_limit));
        $value = str_replace('{rest}', $rest, $value);
        $label = str_replace('{rest}', $rest, $label);
    }

    $stats[] = [
        // A star after the rating is drawn in acid, as in the mock.
        'value' => str_replace('★', '<span class="text-acid">★</span>', optimum_lift_replace_tokens($value, $bundle)),
        'label' => optimum_lift_replace_tokens($label, $bundle),
    ];
}
$stats = array_slice($stats, 0, 4);

// Dividers between the strip's cells: two rows of two on mobile, one row from md.
$count = count($stats);
foreach ($stats as $i => $stat) {
    $stats[$i]['border'] = match (true) {
        $count === 4 && $i === 0 => 'border-r',
        $count === 4 && $i === 1 => 'md:border-r',
        $count === 4 && $i === 2 => 'border-t border-r md:border-t-0',
        $count === 4             => 'border-t md:border-t-0',
        $i < $count - 1          => 'border-r',
        default                  => '',
    };
}

$strip_grid = match (count($stats)) {
    1       => 'grid-cols-1',
    2       => 'grid-cols-2',
    3       => 'grid-cols-3',
    default => 'grid-cols-2 md:grid-cols-4',
};

$photo = static function (mixed $id): string {
    if (!is_numeric($id) || (int) $id <= 0) {
        return '';
    }

    return wp_get_attachment_image((int) $id, 'large', false, [
        'class'   => 'absolute inset-0 h-full w-full object-cover',
        'loading' => 'eager',
        'sizes'   => '(min-width: 1024px) 290px, 45vw',
    ]);
};

$tiles = [
    ['image' => $photo($block['before_image'] ?? null), 'label' => $text('before_label'), 'after' => false],
    ['image' => $photo($block['after_image'] ?? null), 'label' => $text('after_label'), 'after' => true],
];

$progress_label   = $text('progress_label');
$progress_percent = is_numeric($block['progress_percent'] ?? null) ? max(0, min(100, (int) $block['progress_percent'])) : null;
$progress_stats   = array_slice($rows('progress_stats'), 0, 4);
$progress_grid    = match (count($progress_stats)) {
    1       => 'grid-cols-1',
    2       => 'grid-cols-2',
    4       => 'grid-cols-4',
    default => 'grid-cols-3',
};
$has_progress = $progress_label !== '' || $progress_percent !== null || $progress_stats !== [];

$day_plan_label = $text('day_plan_label');
$day_plan       = $rows('day_plan');
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'home-hero')); ?>" class="relative overflow-hidden">
    <div class="pointer-events-none absolute -top-40 -left-40 h-[42rem] w-[42rem] rounded-full bg-accent/20 blur-[130px]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute top-24 right-0 h-[30rem] w-[30rem] rounded-full bg-acid/[.07] blur-[120px]" aria-hidden="true"></div>
    <div class="grain pointer-events-none absolute inset-0 opacity-40" aria-hidden="true"></div>

    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 pt-12 pb-14 md:pt-20 md:pb-24 lg:grid-cols-[1.05fr_.95fr] lg:gap-14">
        <div>
            <?php if ($show_proof) : ?>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 py-1.5 pr-3.5 pl-1.5">
                        <span class="flex -space-x-2" aria-hidden="true">
                            <span class="h-6 w-6 rounded-full bg-linear-to-br from-zinc-500 to-zinc-700 ring-2 ring-paper"></span>
                            <span class="h-6 w-6 rounded-full bg-linear-to-br from-accent to-accent-light ring-2 ring-paper"></span>
                            <span class="h-6 w-6 rounded-full bg-linear-to-br from-zinc-600 to-zinc-800 ring-2 ring-paper"></span>
                            <span class="h-6 w-6 rounded-full bg-linear-to-br from-zinc-400 to-zinc-600 ring-2 ring-paper"></span>
                        </span>
                        <span class="text-[11px] font-bold text-zinc-300"><?php
                            /* translators: %s: customer count, e.g. 600+. */
                            echo esc_html(sprintf(__('%s Albanian customers', 'optimum-lift'), optimum_lift_format_count_plus(optimum_lift_customer_count())));
                        ?></span>
                    </div>
                    <?php if ($rating !== null) : ?>
                        <?php $stars = rtrim(rtrim(sprintf('%.1F', max(0, min(100, $rating['average'] / 5 * 100))), '0'), '.'); ?>
                        <div class="flex items-center gap-1.5 text-[11px] font-bold text-zinc-300">
                            <span class="olstars text-[14px] leading-none" style="--pct:<?php echo esc_attr($stars); ?>%" aria-hidden="true">★★★★★</span>
                            <span><?php
                                /* translators: 1: average rating out of 5, e.g. 4,8. 2: number of reviews. */
                                echo esc_html(sprintf(_n('%1$s/5 · %2$s review', '%1$s/5 · %2$s reviews', $rating['count'], 'optimum-lift'), optimum_lift_format_rating($rating['average']), optimum_lift_format_number($rating['count'])));
                            ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($heading !== '') : ?>
                <h1 class="<?php echo esc_attr($show_proof ? 'mt-6 h-display text-[2.6rem] leading-[1.06] text-white sm:text-6xl lg:text-[4.3rem]' : 'h-display text-[2.6rem] leading-[1.06] text-white sm:text-6xl lg:text-[4.3rem]'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h1>
            <?php endif; ?>

            <?php if ($body !== '') : ?>
                <div class="mt-6 max-w-xl text-[15px] leading-relaxed text-zinc-400 sm:text-lg [&_a]:text-accent-light [&_a]:underline [&_p+p]:mt-4 [&_strong]:font-semibold [&_strong]:text-zinc-200">
                    <?php echo wp_kses_post($body); ?>
                </div>
            <?php endif; ?>

            <?php if (($primary_label !== '' && $primary_url !== '') || ($secondary_label !== '' && $secondary_url !== '')) : ?>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <?php if ($primary_label !== '' && $primary_url !== '') : ?>
                        <a href="<?php echo esc_url($primary_url); ?>" data-cta="hero-primary" class="btn btn-primary btn-lg pulse group gap-2.5 shadow-glow">
                            <?php echo esc_html($primary_label); ?>
                            <?php echo optimum_lift_icon('arrow-right', 'w-5 h-5 shrink-0 transition group-hover:translate-x-1'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($secondary_label !== '' && $secondary_url !== '') : ?>
                        <a href="<?php echo esc_url($secondary_url); ?>" data-cta="hero-secondary" class="btn btn-ghost btn-lg bg-white/[.04] px-6 font-bold">
                            <?php echo optimum_lift_icon('play', 'w-5 h-5 shrink-0 text-acid'); ?>
                            <?php echo esc_html($secondary_label); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($reassurance !== []) : ?>
                <ul class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[12px] font-semibold text-zinc-500">
                    <?php foreach ($reassurance as $line) : ?>
                        <li class="flex items-center gap-1.5"><?php echo optimum_lift_icon('check', 'w-4 h-4 shrink-0 text-acid'); ?><?php echo esc_html($line); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="relative">
            <div class="relative rounded-[2rem] border border-white/10 bg-surface/80 p-3 shadow-card backdrop-blur">
                <div class="grid grid-cols-2 gap-3">
                    <?php foreach ($tiles as $tile) : ?>
                        <div class="<?php echo esc_attr($tile['after'] ? 'ph-photo relative aspect-[3/4] overflow-hidden rounded-[1.4rem] ring-1 ring-accent/40' : 'ph-photo relative aspect-[3/4] overflow-hidden rounded-[1.4rem]'); ?>">
                            <?php if ($tile['image'] !== '') : ?>
                                <?php echo $tile['image']; ?>
                            <?php else : ?>
                                <span class="absolute inset-0 grid place-items-center text-zinc-700" aria-hidden="true"><?php echo optimum_lift_icon('avatar', 'w-14 h-14'); ?></span>
                            <?php endif; ?>
                            <?php if ($tile['label'] !== '') : ?>
                                <span class="<?php echo esc_attr($tile['after'] ? 'absolute top-3 left-3 rounded-full bg-accent px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-widest text-white' : 'absolute top-3 left-3 rounded-full bg-black/60 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-widest text-zinc-300'); ?>"><?php echo esc_html($tile['label']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($has_progress) : ?>
                    <div class="mt-3 rounded-2xl border border-white/10 bg-raised/80 p-4">
                        <?php if ($progress_label !== '' || $progress_percent !== null) : ?>
                            <div class="flex items-center justify-between gap-3 text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                                <span><?php echo esc_html($progress_label); ?></span>
                                <?php if ($progress_percent !== null) : ?>
                                    <span class="text-acid">+<?php echo esc_html((string) $progress_percent); ?>%</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($progress_percent !== null) : ?>
                            <div class="mt-2.5 h-2 overflow-hidden rounded-full bg-white/10" aria-hidden="true">
                                <div class="h-full rounded-full bg-linear-to-r from-accent to-acid" style="width:<?php echo esc_attr((string) $progress_percent); ?>%"></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($progress_stats !== []) : ?>
                            <div class="<?php echo esc_attr('mt-3 grid gap-2 text-center ' . $progress_grid); ?>">
                                <?php foreach ($progress_stats as $row) : ?>
                                    <div class="rounded-xl bg-black/30 py-2">
                                        <div class="h-display text-lg text-white"><?php echo esc_html($text('value', $row)); ?></div>
                                        <div class="text-[9px] font-bold uppercase tracking-wider text-zinc-500"><?php echo esc_html($text('label', $row)); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($day_plan !== []) : ?>
                <div class="floaty absolute -bottom-6 -left-4 hidden w-56 rounded-2xl border border-white/10 bg-surface/95 p-3.5 shadow-card backdrop-blur sm:block">
                    <?php if ($day_plan_label !== '') : ?>
                        <div class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-widest text-zinc-500">
                            <?php echo optimum_lift_icon('bowl', 'w-3.5 h-3.5 shrink-0 text-acid'); ?>
                            <?php echo esc_html($day_plan_label); ?>
                        </div>
                    <?php endif; ?>
                    <ul class="mt-2.5 space-y-1.5 text-[11px] font-semibold">
                        <?php foreach ($day_plan as $row) : ?>
                            <li class="flex justify-between gap-2 text-zinc-300"><span><?php echo esc_html($text('meal', $row)); ?></span><span class="shrink-0 text-zinc-500"><?php echo esc_html($text('kcal', $row)); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($saving !== null) : ?>
                <div class="absolute -top-3 -right-2 rotate-6 rounded-xl bg-acid px-3 py-2 text-center shadow-glow">
                    <div class="h-display text-base leading-none text-paper"><?php
                        /* translators: %d: discount percentage. */
                        echo esc_html(sprintf(__('−%d%%', 'optimum-lift'), $saving['percent']));
                    ?></div>
                    <?php if ($tonight) : ?>
                        <div class="text-[8px] font-extrabold uppercase tracking-widest text-paper/70"><?php esc_html_e('Only tonight', 'optimum-lift'); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($stats !== []) : ?>
        <div class="relative border-y border-white/[.07] bg-surface/60">
            <dl class="<?php echo esc_attr('mx-auto grid max-w-7xl ' . $strip_grid); ?>">
                <?php foreach ($stats as $stat) : ?>
                    <div class="<?php echo esc_attr('flex flex-col-reverse border-white/[.07] px-4 py-5 text-center ' . $stat['border']); ?>">
                        <dt class="mt-0.5 text-[10px] font-bold uppercase tracking-widest text-zinc-500"><?php echo $stat['label']; ?></dt>
                        <dd class="h-display text-2xl text-white"><?php echo $stat['value']; ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    <?php endif; ?>
</section>
