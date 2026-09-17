<?php

/**
 * Qualification: who the Product is for, and who should not buy it. Saying
 * plainly who it is not for makes the rest of the page believable and cuts
 * refunds.
 *
 * Diet Products get the ✓/✕ variant of the design (produkt-dieta.html), every
 * other Product the arrow variant (produkt.html). "For you" items may hold
 * inline HTML (bold, links); "not for you" items are plain text.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block   = $args['block'] ?? [];
$product = $args['product'] ?? null;

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

/**
 * @return list<string>
 */
$items = static function (mixed $rows) use ($text): array {
    $list = [];
    foreach (is_array($rows) ? $rows : [] as $row) {
        $item = is_array($row) ? $text($row['text'] ?? null) : '';
        if ($item !== '') {
            $list[] = $item;
        }
    }

    return $list;
};

$yes_items = $items($block['yes_items'] ?? null);
$no_items  = $items($block['no_items'] ?? null);

if ($yes_items === [] && $no_items === []) {
    return;
}

$eyebrow   = $text($block['eyebrow'] ?? null);
$heading   = $text($block['heading'] ?? null);
$intro     = $text($block['intro'] ?? null);
$yes_title = $text($block['yes_title'] ?? null);
$no_title  = $text($block['no_title'] ?? null);
$checks    = $product instanceof WC_Product && optimum_lift_product_kind($product) === 'diet';

$inline_html = [
    'a'      => ['href' => true, 'title' => true, 'rel' => true, 'target' => true],
    'strong' => [],
    'b'      => [],
    'em'     => [],
    'i'      => [],
    'br'     => [],
];
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'qualification')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="mx-auto max-w-5xl px-4">
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

        <div class="<?php echo esc_attr($yes_items !== [] && $no_items !== [] ? 'reveal mt-10 grid gap-5 first:mt-0 md:grid-cols-2' : 'reveal mx-auto mt-10 grid max-w-2xl gap-5 first:mt-0'); ?>">
            <?php if ($yes_items !== []) : ?>
                <div class="rounded-3xl border border-acid/25 bg-acid/[.05] p-7">
                    <?php if ($yes_title !== '') : ?>
                        <?php if ($checks) : ?>
                            <h3 class="flex items-center gap-2.5 h-display text-xl text-white">
                                <?php echo optimum_lift_icon('check', 'h-6 w-6 shrink-0 text-acid', ['stroke-width' => '2.6']); ?>
                                <?php echo esc_html($yes_title); ?>
                            </h3>
                        <?php else : ?>
                            <h3 class="flex items-center gap-2.5 text-[11px] font-extrabold uppercase tracking-[.16em] text-acid">
                                <?php echo optimum_lift_icon('check', 'h-4 w-4 shrink-0'); ?>
                                <?php echo esc_html($yes_title); ?>
                            </h3>
                        <?php endif; ?>
                    <?php endif; ?>
                    <ul class="<?php echo esc_attr($checks ? 'mt-5 space-y-3 text-[14px] leading-relaxed text-zinc-300 first:mt-0' : 'mt-5 space-y-3 text-[13.5px] leading-relaxed text-zinc-300 first:mt-0'); ?> [&_a]:font-bold [&_a]:text-white [&_a]:underline [&_a]:decoration-acid/50 [&_a]:underline-offset-2 [&_a:hover]:decoration-acid [&_strong]:text-white">
                        <?php foreach ($yes_items as $item) : ?>
                            <li class="<?php echo esc_attr($checks ? 'flex gap-3' : 'flex gap-2.5'); ?>">
                                <span class="text-acid" aria-hidden="true"><?php echo $checks ? '✓' : '→'; ?></span>
                                <span><?php echo wp_kses($item, $inline_html); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($no_items !== []) : ?>
                <div class="rounded-3xl border border-white/[.08] bg-surface p-7">
                    <?php if ($no_title !== '') : ?>
                        <?php if ($checks) : ?>
                            <h3 class="flex items-center gap-2.5 h-display text-xl text-white">
                                <?php echo optimum_lift_icon('x', 'h-6 w-6 shrink-0 text-accent-light'); ?>
                                <?php echo esc_html($no_title); ?>
                            </h3>
                        <?php else : ?>
                            <h3 class="flex items-center gap-2.5 text-[11px] font-extrabold uppercase tracking-[.16em] text-zinc-500">
                                <?php echo optimum_lift_icon('x', 'h-4 w-4 shrink-0'); ?>
                                <?php echo esc_html($no_title); ?>
                            </h3>
                        <?php endif; ?>
                    <?php endif; ?>
                    <ul class="<?php echo esc_attr($checks ? 'mt-5 space-y-3 text-[14px] leading-relaxed text-zinc-400 first:mt-0' : 'mt-5 space-y-3 text-[13.5px] leading-relaxed text-zinc-400 first:mt-0'); ?>">
                        <?php foreach ($no_items as $item) : ?>
                            <li class="<?php echo esc_attr($checks ? 'flex gap-3' : 'flex gap-2.5'); ?>">
                                <span class="<?php echo esc_attr($checks ? 'text-accent-light' : 'text-zinc-500'); ?>" aria-hidden="true"><?php echo $checks ? '✕' : '→'; ?></span>
                                <span><?php echo esc_html($item); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
