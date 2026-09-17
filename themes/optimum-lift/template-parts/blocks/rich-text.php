<?php

/**
 * Free editor copy under the standard section header, for what no other
 * layout fits.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block = $args['block'] ?? [];

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

$body = $text($block['body'] ?? null);

if (trim(wp_strip_all_tags($body)) === '') {
    return;
}

$eyebrow = $text($block['eyebrow'] ?? null);
$heading = $text($block['heading'] ?? null);
$intro   = $text($block['intro'] ?? null);
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'rich-text')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="mx-auto max-w-3xl px-4">
        <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
            <div class="reveal mx-auto max-w-2xl text-center">
                <?php if ($eyebrow !== '') : ?>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h2 class="mt-5 h-display text-3xl text-white first:mt-0 sm:text-4xl"><?php echo optimum_lift_heading_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($intro !== '') : ?>
                    <p class="mt-4 text-[15px] leading-relaxed text-zinc-400 first:mt-0"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="reveal prose-ol mt-10 text-[15px] leading-relaxed first:mt-0"><?php echo wp_kses_post($body); ?></div>
    </div>
</section>
