<?php

/**
 * Questions and answers as an accordion (modules/accordion.js): one answer open
 * at a time, every answer readable without JavaScript.
 *
 * `note` is the medical disclaimer box (brief §4.3). It renders whenever it is
 * set, even with no questions, because a diet page must never lose it. The
 * WhatsApp help box needs both `help_box` and a number in the Customizer.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block   = $args['block'] ?? [];
$post_id = (int) ($args['post_id'] ?? 0);
$index   = (int) ($args['index'] ?? 0);

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

$items = [];
foreach (is_array($block['items'] ?? null) ? $block['items'] : [] as $row) {
    $question = is_array($row) ? $text($row['question'] ?? null) : '';
    $answer   = is_array($row) ? $text($row['answer'] ?? null) : '';

    if ($question !== '' && $answer !== '') {
        $items[] = ['question' => $question, 'answer' => $answer];
    }
}

$note     = $text($block['note'] ?? null);
$whatsapp = !empty($block['help_box']) ? optimum_lift_whatsapp_url() : '';

if ($items === [] && $note === '') {
    return;
}

$eyebrow = $text($block['eyebrow'] ?? null);
$heading = $text($block['heading'] ?? null);
$intro   = $text($block['intro'] ?? null);
$front   = $post_id > 0 && $post_id === optimum_lift_front_page_id();
$alt     = optimum_lift_block_tone_class($block) !== '';
$box     = $alt ? 'rounded-2xl border border-white/[.08] bg-paper' : 'rounded-2xl border border-white/[.08] bg-surface';
// Unique per block, since a page could hold two FAQ sections.
$prefix = 'faq-' . $index;
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'faq')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="mx-auto max-w-3xl px-4">
        <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
            <div class="reveal text-center">
                <?php if ($eyebrow !== '') : ?>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h2 class="<?php echo esc_attr($front ? 'mt-5 h-display text-3xl text-white first:mt-0 sm:text-5xl' : 'mt-5 h-display text-3xl text-white first:mt-0 sm:text-4xl'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($intro !== '') : ?>
                    <p class="mt-4 text-[15px] leading-relaxed text-zinc-400 first:mt-0"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($items !== []) : ?>
            <div class="mt-10 space-y-3 first:mt-0" data-accordion>
                <?php foreach ($items as $i => $item) : ?>
                    <div class="<?php echo esc_attr('acc ' . $box); ?>">
                        <h3>
                            <button type="button" id="<?php echo esc_attr($prefix . '-q' . $i); ?>" class="acc-btn flex w-full items-center justify-between gap-4 rounded-2xl p-5 text-left" aria-expanded="false" aria-controls="<?php echo esc_attr($prefix . '-a' . $i); ?>">
                                <span class="text-[14.5px] font-extrabold text-white"><?php echo esc_html($item['question']); ?></span>
                                <?php echo optimum_lift_icon('plus', 'acc-ico w-5 h-5 shrink-0 text-accent-light'); ?>
                            </button>
                        </h3>
                        <div class="acc-body" id="<?php echo esc_attr($prefix . '-a' . $i); ?>">
                            <div>
                                <div class="proof-copy px-5 pb-5 text-[14px] leading-relaxed text-zinc-400"><?php echo wp_kses_post($item['answer']); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($note !== '') : ?>
            <div class="<?php echo esc_attr('reveal mt-8 p-5 first:mt-0 ' . $box); ?>">
                <div class="proof-copy text-[12.5px] leading-relaxed text-zinc-400"><?php echo wp_kses_post($note); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($whatsapp !== '') : ?>
            <div class="<?php echo esc_attr('reveal mt-9 flex flex-col items-center justify-between gap-4 p-5 first:mt-0 sm:flex-row ' . $box); ?>">
                <div>
                    <p class="text-[14px] font-extrabold text-white"><?php esc_html_e('Have other questions before you buy?', 'optimum-lift'); ?></p>
                    <p class="text-[13px] text-zinc-400"><?php esc_html_e('Message us on WhatsApp and we reply within 24 hours.', 'optimum-lift'); ?></p>
                </div>
                <a href="<?php echo esc_url($whatsapp); ?>" target="_blank" rel="noopener" data-cta="faq-whatsapp" class="btn btn-light btn-md shrink-0">
                    <?php echo optimum_lift_icon('chat', 'w-4 h-4 shrink-0'); ?>
                    <?php esc_html_e('Message us on WhatsApp', 'optimum-lift'); ?>
                    <span class="screen-reader-text"><?php esc_html_e('(opens in a new tab)', 'optimum-lift'); ?></span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>
