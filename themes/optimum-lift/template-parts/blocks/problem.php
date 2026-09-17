<?php

/**
 * The problem (agitation) section: why plans fail, as up to four pain cards,
 * then a closing line whose *accent* words turn red.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block   = $args['block'];
$eyebrow = is_string($block['eyebrow'] ?? null) ? trim($block['eyebrow']) : '';
$heading = is_string($block['heading'] ?? null) ? trim($block['heading']) : '';
$intro   = is_string($block['intro'] ?? null) ? trim($block['intro']) : '';
$footer  = is_string($block['footer'] ?? null) ? trim($block['footer']) : '';

$cards = [];
foreach (is_array($block['cards'] ?? null) ? $block['cards'] : [] as $row) {
    $title = is_array($row) && is_string($row['title'] ?? null) ? trim($row['title']) : '';
    $body  = is_array($row) && is_string($row['text'] ?? null) ? trim($row['text']) : '';

    if ($title !== '' || $body !== '') {
        $cards[] = ['icon' => is_string($row['icon'] ?? null) ? $row['icon'] : '', 'title' => $title, 'text' => $body];
    }
}

$grid = match (count($cards)) {
    1       => 'mx-auto max-w-md',
    2       => 'md:grid-cols-2',
    3       => 'md:grid-cols-3',
    default => 'md:grid-cols-2 lg:grid-cols-4',
};
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'problem')); ?>" class="<?php echo esc_attr(trim('relative py-20 md:py-28 ' . optimum_lift_block_tone_class($block))); ?>">
    <div class="mx-auto max-w-7xl px-4">
        <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
            <div class="reveal mx-auto max-w-3xl text-center">
                <?php if ($eyebrow !== '') : ?>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h2 class="<?php echo esc_attr($eyebrow !== '' ? 'mt-5 h-display text-3xl text-white sm:text-5xl' : 'h-display text-3xl text-white sm:text-5xl'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($intro !== '') : ?>
                    <p class="mt-5 text-[15px] leading-relaxed text-zinc-400 sm:text-base"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($cards !== []) : ?>
            <ul class="<?php echo esc_attr('mt-12 grid gap-4 ' . $grid); ?>">
                <?php foreach ($cards as $card) : ?>
                    <li class="reveal rounded-2xl border border-white/[.08] bg-surface p-6">
                        <?php if ($card['icon'] !== '') : ?>
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-accent/15 text-accent-light"><?php echo optimum_lift_icon($card['icon'], 'w-5 h-5'); ?></span>
                        <?php endif; ?>
                        <?php if ($card['title'] !== '') : ?>
                            <h3 class="<?php echo esc_attr($card['icon'] !== '' ? 'mt-4 font-extrabold text-white' : 'font-extrabold text-white'); ?>"><?php echo esc_html($card['title']); ?></h3>
                        <?php endif; ?>
                        <?php if ($card['text'] !== '') : ?>
                            <p class="mt-2 text-[13.5px] leading-relaxed text-zinc-400"><?php echo esc_html($card['text']); ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($footer !== '') : ?>
            <p class="reveal mt-10 text-center text-sm font-bold text-zinc-300"><?php echo optimum_lift_heading_html($footer); ?></p>
        <?php endif; ?>
    </div>
</section>
