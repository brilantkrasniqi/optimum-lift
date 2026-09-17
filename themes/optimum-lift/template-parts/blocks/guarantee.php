<?php

/**
 * The money-back guarantee, as a wide `band` (icon beside the text, chips) or a
 * centred `card` (icon above, call to action).
 *
 * Nothing renders while the Customizer guarantee is 0 days: a guarantee section
 * for a guarantee the store does not give would be false. The body takes
 * tokens ({guarantee_days}); the heading is editor text and must be kept equal
 * to the Customizer by hand.
 *
 * The block sits in its own section, and after a section with the same
 * background it drops its top padding, so it reads as that section's closing
 * card rather than floating between two gaps.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block   = $args['block'] ?? [];
$product = $args['product'] ?? null;
$product = $product instanceof WC_Product ? $product : null;
$post_id = (int) ($args['post_id'] ?? 0);
$index   = (int) ($args['index'] ?? 0);

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

$heading = $text($block['heading'] ?? null);
$body    = $text($block['body'] ?? null);

if ((int) optimum_lift_setting('guarantee_days') < 1 || ($heading === '' && $body === '')) {
    return;
}

$eyebrow = $text($block['eyebrow'] ?? null);
$intro   = $text($block['intro'] ?? null);
$chips   = optimum_lift_lines($text($block['chips'] ?? null));
$card    = ($block['style'] ?? '') === 'card';

$cta_label  = $text($block['cta_label'] ?? null);
$cta_anchor = sanitize_title(ltrim($text($block['cta_anchor'] ?? null), '#'));

$tone     = optimum_lift_block_tone_class($block);
$previous = $index > 0 ? (optimum_lift_blocks($post_id)[$index - 1] ?? null) : null;
$flush    = is_array($previous) && optimum_lift_block_tone_class($previous) === $tone;
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'guarantee')); ?>" class="<?php echo esc_attr(($flush ? 'pb-20 md:pb-28 ' : 'py-20 md:py-28 ') . $tone); ?>">
    <?php if ($card) : ?>
        <div class="mx-auto max-w-3xl px-4">
            <div class="reveal rounded-3xl border border-acid/25 bg-linear-to-b from-acid/[.08] to-transparent p-8 text-center sm:p-12">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-acid/15 text-acid">
                    <?php echo optimum_lift_icon('shield-check', 'w-8 h-8'); ?>
                </div>
                <?php if ($eyebrow !== '') : ?>
                    <span class="eyebrow mt-6"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h2 class="<?php echo esc_attr($eyebrow !== '' ? 'mt-5 h-display text-3xl text-white sm:text-4xl' : 'mt-6 h-display text-3xl text-white sm:text-4xl'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($intro !== '') : ?>
                    <p class="mx-auto mt-4 max-w-xl text-[15px] leading-relaxed text-zinc-300"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
                <?php if ($body !== '') : ?>
                    <p class="mx-auto mt-4 max-w-xl text-[14.5px] leading-relaxed text-zinc-400"><?php echo optimum_lift_replace_tokens($body, $product); ?></p>
                <?php endif; ?>
                <?php if ($chips !== []) : ?>
                    <ul class="mt-6 flex flex-wrap justify-center gap-2.5 text-[11px] font-extrabold uppercase tracking-wider">
                        <?php foreach ($chips as $chip) : ?>
                            <li class="rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-zinc-300"><?php echo esc_html($chip); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($cta_label !== '' && $cta_anchor !== '') : ?>
                    <a href="<?php echo esc_url('#' . $cta_anchor); ?>" data-cta="guarantee-cta" class="btn btn-light mt-8 gap-2.5 rounded-xl px-7 py-4 text-[15px]">
                        <?php echo esc_html($cta_label); ?>
                        <?php echo optimum_lift_icon('arrow-right', 'w-5 h-5 shrink-0'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else : ?>
        <div class="mx-auto max-w-5xl px-4">
            <div class="reveal relative overflow-hidden rounded-[2rem] border border-acid/25 bg-linear-to-br from-acid/[.1] via-surface to-surface p-8 sm:p-12">
                <div class="pointer-events-none absolute -top-20 -right-20 h-72 w-72 rounded-full bg-acid/10 blur-3xl" aria-hidden="true"></div>
                <div class="relative flex flex-col items-start gap-7 sm:flex-row">
                    <div class="grid h-20 w-20 shrink-0 place-items-center rounded-3xl bg-acid text-paper">
                        <?php echo optimum_lift_icon('shield-check', 'w-10 h-10', ['stroke-width' => '2.1']); ?>
                    </div>
                    <div>
                        <?php if ($eyebrow !== '') : ?>
                            <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                        <?php endif; ?>
                        <?php if ($heading !== '') : ?>
                            <h2 class="mt-5 h-display text-3xl text-white first:mt-0 sm:text-4xl"><?php echo optimum_lift_heading_html($heading); ?></h2>
                        <?php endif; ?>
                        <?php if ($intro !== '') : ?>
                            <p class="mt-4 max-w-2xl text-[15px] leading-relaxed text-zinc-300 first:mt-0"><?php echo esc_html($intro); ?></p>
                        <?php endif; ?>
                        <?php if ($body !== '') : ?>
                            <p class="mt-4 max-w-2xl text-[15px] leading-relaxed text-zinc-300 first:mt-0"><?php echo optimum_lift_replace_tokens($body, $product); ?></p>
                        <?php endif; ?>
                        <?php if ($chips !== []) : ?>
                            <ul class="mt-6 flex flex-wrap gap-2.5 text-[11px] font-extrabold uppercase tracking-wider">
                                <?php foreach ($chips as $chip) : ?>
                                    <li class="rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-zinc-300"><?php echo esc_html($chip); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($cta_label !== '' && $cta_anchor !== '') : ?>
                            <a href="<?php echo esc_url('#' . $cta_anchor); ?>" data-cta="guarantee-cta" class="btn btn-light mt-7 gap-2.5 rounded-xl px-7 py-4 text-[15px]">
                                <?php echo esc_html($cta_label); ?>
                                <?php echo optimum_lift_icon('arrow-right', 'w-5 h-5 shrink-0'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
