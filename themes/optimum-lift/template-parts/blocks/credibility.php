<?php

/**
 * Who is behind the plan: a photo (or placeholder) with the name and role on
 * it, the heading, the story, and badges (certifications, method) or chips.
 *
 * The trainer's name, photo and credentials are placeholders in the seed and
 * must be real before launch; in health, a title nobody holds is a real
 * problem, not marketing.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block   = $args['block'] ?? [];
$post_id = (int) ($args['post_id'] ?? 0);

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

$body   = $text($block['body'] ?? null);
$name   = $text($block['name'] ?? null);
$role   = $text($block['role'] ?? null);
$image  = is_numeric($block['image'] ?? null) ? (int) $block['image'] : 0;
$chips  = optimum_lift_lines($text($block['chips'] ?? null));
$badges = [];
foreach (is_array($block['badges'] ?? null) ? $block['badges'] : [] as $row) {
    $badge = [
        'title' => is_array($row) ? $text($row['title'] ?? null) : '',
        'text'  => is_array($row) ? $text($row['text'] ?? null) : '',
        'icon'  => is_array($row) ? $text($row['icon'] ?? null) : '',
    ];

    if ($badge['title'] !== '' || $badge['text'] !== '') {
        $badges[] = $badge;
    }
}

$eyebrow = $text($block['eyebrow'] ?? null);
$heading = $text($block['heading'] ?? null);
$intro   = $text($block['intro'] ?? null);

if ($body === '' && $intro === '' && $badges === [] && $chips === []) {
    return;
}

$front = $post_id > 0 && $post_id === optimum_lift_front_page_id();
$alt   = optimum_lift_block_tone_class($block) !== '';
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'credibility')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="<?php echo esc_attr($front ? 'mx-auto grid max-w-7xl items-center gap-10 px-4 lg:grid-cols-[.8fr_1fr] lg:gap-16' : 'mx-auto grid max-w-5xl items-center gap-10 px-4 md:grid-cols-[.7fr_1fr]'); ?>">
        <div class="<?php echo esc_attr($front ? 'reveal ph-photo relative mx-auto aspect-[4/5] w-full max-w-sm overflow-hidden rounded-3xl border border-white/10' : 'reveal ph-photo relative mx-auto aspect-[4/5] w-full max-w-xs overflow-hidden rounded-3xl border border-white/10'); ?>">
            <?php if ($image > 0) : ?>
                <?php
                echo wp_get_attachment_image($image, 'large', false, [
                    'class'   => 'absolute inset-0 h-full w-full object-cover',
                    'sizes'   => $front ? '(min-width: 640px) 384px, 100vw' : '(min-width: 640px) 320px, 100vw',
                    'loading' => 'lazy',
                ]);
                ?>
            <?php else : ?>
                <div class="absolute inset-0 grid place-items-center text-zinc-700" aria-hidden="true">
                    <?php echo optimum_lift_icon('avatar', $front ? 'w-16 h-16' : 'w-14 h-14'); ?>
                </div>
            <?php endif; ?>
            <?php if ($name !== '' || $role !== '') : ?>
                <div class="<?php echo esc_attr($front ? 'absolute right-4 bottom-4 left-4 rounded-2xl bg-black/70 p-4 backdrop-blur' : 'absolute right-4 bottom-4 left-4 rounded-2xl bg-black/70 p-3.5 backdrop-blur'); ?>">
                    <?php if ($name !== '') : ?>
                        <p class="font-extrabold text-white"><?php echo esc_html($name); ?></p>
                    <?php endif; ?>
                    <?php if ($role !== '') : ?>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400"><?php echo esc_html($role); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="reveal">
            <?php if ($eyebrow !== '') : ?>
                <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
            <?php endif; ?>
            <?php if ($heading !== '') : ?>
                <h2 class="<?php echo esc_attr($front ? 'mt-5 h-display text-3xl text-white first:mt-0 sm:text-4xl' : 'mt-5 h-display text-3xl text-white first:mt-0'); ?>"><?php echo optimum_lift_heading_html($heading); ?></h2>
            <?php endif; ?>
            <?php if ($intro !== '') : ?>
                <p class="mt-4 text-[15px] leading-relaxed text-zinc-400 first:mt-0"><?php echo esc_html($intro); ?></p>
            <?php endif; ?>
            <?php if ($body !== '') : ?>
                <div class="<?php echo esc_attr($front ? 'proof-copy mt-5 text-[15px] leading-relaxed text-zinc-400 first:mt-0' : 'proof-copy mt-4 text-[14.5px] leading-relaxed text-zinc-400 first:mt-0'); ?>"><?php echo wp_kses_post($body); ?></div>
            <?php endif; ?>

            <?php if ($badges !== []) : ?>
                <ul class="<?php echo esc_attr($front ? 'mt-7 grid gap-3.5 sm:grid-cols-2' : 'mt-6 grid gap-3 sm:grid-cols-2'); ?>">
                    <?php foreach ($badges as $badge) : ?>
                        <li class="<?php echo esc_attr($alt ? 'flex items-start gap-3 rounded-2xl border border-white/[.08] bg-paper p-4' : 'flex items-start gap-3 rounded-2xl border border-white/[.08] bg-surface p-4'); ?>">
                            <?php if ($badge['icon'] !== '') : ?>
                                <?php echo optimum_lift_icon($badge['icon'], 'w-5 h-5 shrink-0 text-acid'); ?>
                            <?php endif; ?>
                            <div>
                                <?php if ($badge['title'] !== '') : ?>
                                    <p class="text-[13px] font-extrabold text-white"><?php echo esc_html($badge['title']); ?></p>
                                <?php endif; ?>
                                <?php if ($badge['text'] !== '') : ?>
                                    <p class="text-[12px] text-zinc-500"><?php echo esc_html($badge['text']); ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($chips !== []) : ?>
                <ul class="mt-6 flex flex-wrap gap-2.5">
                    <?php foreach ($chips as $chip) : ?>
                        <li class="rounded-full border border-white/10 bg-white/5 px-3.5 py-1.5 text-[11px] font-bold text-zinc-400"><?php echo esc_html($chip); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
