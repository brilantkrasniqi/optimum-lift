<?php

/**
 * A real week's shopping list with its cost, beside the copy that answers
 * "isn't eating like this expensive?". The list is the answer; a sentence is
 * not.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block = $args['block'] ?? [];

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

$body       = $text($block['body'] ?? null);
$cost_value = $text($block['cost_value'] ?? null);
$items      = optimum_lift_lines(is_string($block['items'] ?? null) ? $block['items'] : null);

if ($body === '' && $cost_value === '' && $items === []) {
    return;
}

$eyebrow     = $text($block['eyebrow'] ?? null);
$heading     = $text($block['heading'] ?? null);
$intro       = $text($block['intro'] ?? null);
$cost_label  = $text($block['cost_label'] ?? null);
$cost_suffix = $text($block['cost_suffix'] ?? null);
$cost_note   = $text($block['cost_note'] ?? null);
$list_label  = $text($block['list_label'] ?? null);
$list_note   = $text($block['list_note'] ?? null);
$has_copy    = $eyebrow !== '' || $heading !== '' || $intro !== '' || $body !== '' || $cost_value !== '';
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'shopping-list')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="mx-auto max-w-6xl px-4">
        <div class="<?php echo esc_attr($has_copy && $items !== [] ? 'reveal grid gap-10 lg:grid-cols-2 lg:items-center' : 'reveal mx-auto grid max-w-2xl gap-10'); ?>">
            <?php if ($has_copy) : ?>
                <div>
                    <?php if ($eyebrow !== '') : ?>
                        <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                    <?php endif; ?>
                    <?php if ($heading !== '') : ?>
                        <h2 class="mt-5 h-display text-3xl text-white first:mt-0 sm:text-4xl"><?php echo optimum_lift_heading_html($heading); ?></h2>
                    <?php endif; ?>
                    <?php if ($intro !== '') : ?>
                        <p class="mt-4 text-[15px] leading-relaxed text-zinc-400 first:mt-0"><?php echo esc_html($intro); ?></p>
                    <?php endif; ?>
                    <?php if ($body !== '') : ?>
                        <div class="prose-ol mt-4 text-[14.5px] leading-relaxed first:mt-0 [--tw-prose-body:var(--color-zinc-400)]"><?php echo wp_kses_post($body); ?></div>
                    <?php endif; ?>

                    <?php if ($cost_value !== '') : ?>
                        <div class="mt-7 rounded-2xl border border-acid/25 bg-acid/[.07] p-5 first:mt-0">
                            <?php if ($cost_label !== '') : ?>
                                <div class="mb-2 text-[10px] font-extrabold uppercase tracking-[.2em] text-acid"><?php echo esc_html($cost_label); ?></div>
                            <?php endif; ?>
                            <p class="flex flex-wrap items-end gap-x-2 gap-y-1">
                                <span class="h-display text-4xl text-white"><?php echo esc_html($cost_value); ?></span>
                                <?php if ($cost_suffix !== '') : ?>
                                    <span class="pb-1.5 text-[13px] font-bold text-zinc-400"><?php echo esc_html($cost_suffix); ?></span>
                                <?php endif; ?>
                            </p>
                            <?php if ($cost_note !== '') : ?>
                                <p class="mt-2 text-[11.5px] font-semibold text-zinc-500"><?php echo esc_html($cost_note); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($items !== []) : ?>
                <div class="rounded-3xl border border-white/[.08] bg-paper p-7">
                    <?php if ($list_label !== '') : ?>
                        <h3 class="mb-5 flex items-center gap-2.5 border-b border-white/[.07] pb-4 text-[12px] font-extrabold uppercase tracking-[.16em] text-zinc-400">
                            <?php echo optimum_lift_icon('cart', 'h-5 w-5 shrink-0 text-zinc-500'); ?>
                            <?php echo esc_html($list_label); ?>
                        </h3>
                    <?php endif; ?>
                    <ul class="grid gap-2.5 text-[13.5px] font-semibold text-zinc-300 sm:grid-cols-2">
                        <?php foreach ($items as $item) : ?>
                            <li class="flex gap-2.5"><span class="text-acid" aria-hidden="true">✓</span><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($list_note !== '') : ?>
                        <p class="mt-5 border-t border-white/[.07] pt-4 text-[12.5px] leading-relaxed text-zinc-400"><?php echo esc_html($list_note); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
