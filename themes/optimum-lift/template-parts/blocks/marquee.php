<?php

/**
 * The sliding strip of short results under the hero. The list is printed
 * twice so the loop has no seam (.marquee slides by half); the copy is hidden
 * from assistive technology. It pauses on hover, and under reduced motion it
 * stops and wraps, so every item stays readable.
 *
 * The items are editor placeholders until real, consented results replace
 * them (spec: placeholder content).
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block = $args['block'];
$items = optimum_lift_lines(is_string($block['items'] ?? null) ? $block['items'] : null);

if ($items === []) {
    return;
}
?>
<div id="<?php echo esc_attr(optimum_lift_block_id($block, 'marquee')); ?>" class="overflow-hidden border-b border-white/[.07] bg-paper py-4">
    <div class="marquee flex w-max gap-10 whitespace-nowrap text-[12px] font-bold uppercase tracking-[.18em] text-zinc-500 hover:[animation-play-state:paused] motion-reduce:w-full motion-reduce:px-4 motion-reduce:whitespace-normal">
        <?php foreach ([false, true] as $copy) : ?>
            <ul class="flex gap-10 motion-reduce:w-full motion-reduce:flex-wrap motion-reduce:justify-center motion-reduce:gap-x-6 motion-reduce:gap-y-2<?php echo $copy ? ' motion-reduce:hidden' : ''; ?>"<?php echo $copy ? ' aria-hidden="true"' : ''; ?>>
                <?php foreach ($items as $item) : ?>
                    <li class="flex gap-10 motion-reduce:gap-6"><span><?php echo esc_html($item); ?></span><span class="text-accent" aria-hidden="true">✦</span></li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </div>
</div>
