<?php

/**
 * Phases: what the Product contains, one card per phase, plus an optional
 * checklist of everything included.
 *
 * `style` `numbers` (produkt.html: a large faint numeral per card) or `icons`
 * (produkt-dieta.html: an icon tile per card). A featured card gets the
 * accent frame.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block = $args['block'] ?? [];

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

$cards = [];
foreach (is_array($block['cards'] ?? null) ? $block['cards'] : [] as $row) {
    if (!is_array($row)) {
        continue;
    }

    $card = [
        'label'    => $text($row['label'] ?? null),
        'title'    => $text($row['title'] ?? null),
        'text'     => $text($row['text'] ?? null),
        'bullets'  => optimum_lift_lines(is_string($row['bullets'] ?? null) ? $row['bullets'] : null),
        'icon'     => $text($row['icon'] ?? null),
        'featured' => !empty($row['featured']),
    ];

    if ($card['title'] !== '' || $card['text'] !== '' || $card['bullets'] !== []) {
        $cards[] = $card;
    }
}

$checklist = optimum_lift_lines(is_string($block['checklist'] ?? null) ? $block['checklist'] : null);

if ($cards === [] && $checklist === []) {
    return;
}

$eyebrow = $text($block['eyebrow'] ?? null);
$heading = $text($block['heading'] ?? null);
$intro   = $text($block['intro'] ?? null);
$icons   = ($block['style'] ?? '') === 'icons';

$columns = match (count($cards)) {
    1       => 'mx-auto max-w-md',
    2       => 'mx-auto max-w-4xl md:grid-cols-2',
    4       => 'md:grid-cols-2 lg:grid-cols-4',
    default => 'md:grid-cols-3',
};
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'phases')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="mx-auto max-w-6xl px-4">
        <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
            <div class="reveal mx-auto max-w-2xl text-center">
                <?php if ($eyebrow !== '') : ?>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h2 class="<?php echo esc_attr($icons ? 'mt-5 h-display text-3xl text-white first:mt-0 sm:text-4xl' : 'mt-5 h-display text-3xl text-white first:mt-0 sm:text-5xl'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($intro !== '') : ?>
                    <p class="mt-4 text-[15px] leading-relaxed text-zinc-400 first:mt-0"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($cards !== []) : ?>
            <div class="<?php echo esc_attr('mt-12 grid gap-5 first:mt-0 ' . $columns); ?>">
                <?php foreach ($cards as $i => $card) : ?>
                    <div class="<?php echo esc_attr($card['featured'] ? 'reveal relative rounded-3xl border border-accent/30 bg-linear-to-b from-accent/[.12] to-paper p-7' : 'reveal relative rounded-3xl border border-white/[.08] bg-paper p-7'); ?>">
                        <?php if ($icons) : ?>
                            <?php
                            $icon = $card['icon'] !== '' ? optimum_lift_icon($card['icon'], 'h-6 w-6') : '';
                            if ($icon !== '') :
                                if ($card['featured']) {
                                    $tile = 'bg-accent text-white';
                                } else {
                                    // The design alternates two accent tiles with an acid one.
                                    $tile = $i % 3 === 2 ? 'bg-acid/15 text-acid' : 'bg-accent/15 text-accent-light';
                                }
                                ?>
                                <div class="<?php echo esc_attr('mb-5 grid h-12 w-12 place-items-center rounded-2xl ' . $tile); ?>"><?php echo $icon; ?></div>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="h-display absolute -top-4 right-6 text-5xl text-white/[.07]" aria-hidden="true"><?php echo esc_html(sprintf('%02d', $i + 1)); ?></span>
                        <?php endif; ?>

                        <?php if ($card['label'] !== '') : ?>
                            <div class="<?php echo esc_attr($card['featured'] ? 'text-[10px] font-extrabold uppercase tracking-[.2em] text-accent-light' : 'text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-500'); ?>"><?php echo esc_html($card['label']); ?></div>
                        <?php endif; ?>
                        <?php if ($card['title'] !== '') : ?>
                            <h3 class="<?php echo esc_attr($icons ? 'mt-2 h-display text-xl text-white' : 'mt-3 h-display text-xl text-white'); ?>"><?php echo esc_html($card['title']); ?></h3>
                        <?php endif; ?>
                        <?php if ($card['text'] !== '' && $icons) : ?>
                            <p class="<?php echo esc_attr($card['featured'] ? 'mt-3 text-[13.5px] leading-relaxed text-zinc-300' : 'mt-3 text-[13.5px] leading-relaxed text-zinc-400'); ?>"><?php echo esc_html($card['text']); ?></p>
                        <?php elseif ($card['text'] !== '') : ?>
                            <p class="mt-2.5 text-[13.5px] leading-relaxed text-zinc-400"><?php echo esc_html($card['text']); ?></p>
                        <?php endif; ?>
                        <?php if ($card['bullets'] !== []) : ?>
                            <ul class="mt-4 space-y-2 text-[12.5px] font-semibold text-zinc-400">
                                <?php foreach ($card['bullets'] as $bullet) : ?>
                                    <li class="flex gap-2"><span class="text-acid" aria-hidden="true">·</span><?php echo esc_html($bullet); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($checklist !== []) : ?>
            <ul class="reveal mt-10 grid gap-x-8 gap-y-3 rounded-3xl border border-white/[.08] bg-paper p-7 first:mt-0 sm:grid-cols-2">
                <?php foreach ($checklist as $line) : ?>
                    <li class="flex gap-3 text-[14px] font-semibold text-zinc-300">
                        <?php echo optimum_lift_icon('check', 'mt-0.5 size-4.5 shrink-0 text-acid'); ?>
                        <?php echo esc_html($line); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
