<?php

/**
 * Look inside: a sample Workout, a photo or video, and a progress table. A
 * digital Product cannot be touched, so showing what it looks like answers
 * "what am I actually buying?".
 *
 * The video is a link out, never an embedded player: a third-party iframe on
 * load would cost the page its speed and send visitors' data elsewhere.
 * Without a video URL the tile shows no play button, since it could not play.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block = $args['block'] ?? [];

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

$workout_rows = [];
foreach (is_array($block['workout_rows'] ?? null) ? $block['workout_rows'] : [] as $row) {
    $name = is_array($row) ? $text($row['name'] ?? null) : '';
    if ($name !== '') {
        $workout_rows[] = ['name' => $name, 'scheme' => $text($row['scheme'] ?? null)];
    }
}

$progress_rows = [];
foreach (is_array($block['progress_rows'] ?? null) ? $block['progress_rows'] : [] as $row) {
    $label = is_array($row) ? $text($row['label'] ?? null) : '';
    if ($label !== '') {
        $progress_rows[] = [
            'label'   => $label,
            'value'   => $text($row['value'] ?? null),
            'percent' => is_numeric($row['percent'] ?? null) ? max(0, min(100, (int) $row['percent'])) : null,
        ];
    }
}

$image_id = is_numeric($block['media_image'] ?? null) ? (int) $block['media_image'] : 0;
$image    = $image_id > 0
    ? wp_get_attachment_image($image_id, 'large', false, [
        'class'   => 'absolute inset-0 h-full w-full object-cover',
        'sizes'   => '(min-width: 1024px) 400px, 100vw',
        'loading' => 'lazy',
    ])
    : '';
$video    = esc_url_raw($text($block['media_video_url'] ?? null));
$caption  = $text($block['media_caption'] ?? null);

$has_workout  = $workout_rows !== [];
$has_media    = $image !== '' || $video !== '' || $caption !== '';
$has_progress = $progress_rows !== [];
$column_count = (int) $has_workout + (int) $has_media + (int) $has_progress;

if ($column_count === 0) {
    return;
}

$eyebrow       = $text($block['eyebrow'] ?? null);
$heading       = $text($block['heading'] ?? null);
$intro         = $text($block['intro'] ?? null);
$workout_label = $text($block['workout_label'] ?? null);
$workout_tag   = $text($block['workout_tag'] ?? null);
$workout_note  = $text($block['workout_note'] ?? null);
$progress_note = $text($block['progress_note'] ?? null);
$progress_head = $text($block['progress_title'] ?? null);

// Beside other cards the media tile stretches to their height; stacked, it keeps its ratio.
[$columns, $tile_ratio] = match ($column_count) {
    1       => ['mx-auto max-w-md', 'aspect-[4/3]'],
    2       => ['mx-auto max-w-4xl md:grid-cols-2', 'aspect-[4/3] md:aspect-auto'],
    default => ['lg:grid-cols-3', 'aspect-[4/3] lg:aspect-auto'],
};
$tile_class = 'reveal group ph-photo relative grid place-items-center overflow-hidden rounded-3xl border border-white/10 ' . $tile_ratio;
$tile_tag   = $video !== '' ? 'a' : 'div';
$tile_link  = $video !== '' ? sprintf(' href="%s" target="_blank" rel="noopener"', esc_url($video)) : '';
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'preview')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="mx-auto max-w-7xl px-4">
        <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
            <div class="reveal mx-auto max-w-2xl text-center">
                <?php if ($eyebrow !== '') : ?>
                    <span class="eyebrow"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h2 class="mt-5 h-display text-3xl text-white first:mt-0 sm:text-5xl"><?php echo optimum_lift_heading_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($intro !== '') : ?>
                    <p class="mt-4 text-[15px] leading-relaxed text-zinc-400 first:mt-0"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="<?php echo esc_attr('mt-12 grid gap-5 first:mt-0 ' . $columns); ?>">
            <?php if ($has_workout) : ?>
                <div class="reveal rounded-3xl border border-white/10 bg-surface p-5">
                    <?php if ($workout_label !== '' || $workout_tag !== '') : ?>
                        <h3 class="flex items-center justify-between gap-3 text-[11px] font-extrabold uppercase tracking-wider text-zinc-500">
                            <span><?php echo esc_html($workout_label); ?></span>
                            <?php if ($workout_tag !== '') : ?>
                                <span class="text-acid"><?php echo esc_html($workout_tag); ?></span>
                            <?php endif; ?>
                        </h3>
                    <?php endif; ?>
                    <ul class="mt-3 space-y-2 text-[12.5px] font-semibold text-zinc-300 first:mt-0">
                        <?php foreach ($workout_rows as $row) : ?>
                            <li class="flex justify-between gap-3 rounded-lg bg-black/30 px-3 py-2">
                                <span><?php echo esc_html($row['name']); ?></span>
                                <?php if ($row['scheme'] !== '') : ?>
                                    <span class="shrink-0 font-mono text-zinc-400"><?php echo esc_html($row['scheme']); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($workout_note !== '') : ?>
                        <p class="mt-3 rounded-lg border border-acid/25 bg-acid/[.06] px-3 py-2 text-[11.5px] font-semibold text-acid"><?php echo esc_html($workout_note); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($has_media) : ?>
                <<?php echo $tile_tag; ?> class="<?php echo esc_attr($tile_class); ?>"<?php echo $tile_link; ?>>
                    <?php if ($image !== '') : ?>
                        <?php echo $image; ?>
                        <?php if ($video !== '') : ?>
                            <span class="absolute inset-0 bg-black/35" aria-hidden="true"></span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($video !== '' || $image === '') : ?>
                        <span class="relative px-6 text-center">
                            <?php if ($video !== '') : ?>
                                <span class="mx-auto grid h-16 w-16 place-items-center rounded-full border border-white/15 bg-black/40 transition group-hover:border-accent group-hover:bg-accent">
                                    <?php echo optimum_lift_icon('play', 'h-7 w-7 text-white'); ?>
                                </span>
                            <?php else : ?>
                                <?php echo optimum_lift_icon('video', 'mx-auto h-12 w-12 text-zinc-600'); ?>
                            <?php endif; ?>
                            <?php if ($caption !== '' && $image === '') : ?>
                                <span class="mt-3 block text-[11px] font-extrabold uppercase tracking-widest text-zinc-400"><?php echo esc_html($caption); ?></span>
                            <?php elseif ($caption === '' && $video !== '') : ?>
                                <span class="screen-reader-text"><?php esc_html_e('Watch the video', 'optimum-lift'); ?></span>
                            <?php endif; ?>
                            <?php if ($video !== '') : ?>
                                <span class="screen-reader-text"><?php esc_html_e('(opens in a new tab)', 'optimum-lift'); ?></span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($caption !== '' && $image !== '') : ?>
                        <span class="absolute right-4 bottom-4 left-4 rounded-xl bg-black/70 p-3 text-[11.5px] font-semibold text-zinc-300 backdrop-blur"><?php echo esc_html($caption); ?></span>
                    <?php endif; ?>
                </<?php echo $tile_tag; ?>>
            <?php endif; ?>

            <?php if ($has_progress) : ?>
                <div class="reveal rounded-3xl border border-white/10 bg-surface p-5">
                    <?php if ($progress_head !== '') : ?>
                        <h3 class="text-[11px] font-extrabold uppercase tracking-wider text-zinc-500"><?php echo esc_html($progress_head); ?></h3>
                    <?php endif; ?>
                    <ul class="mt-3 space-y-2.5 first:mt-0">
                        <?php foreach ($progress_rows as $row) : ?>
                            <li>
                                <div class="flex justify-between gap-3 text-[11.5px] font-bold text-zinc-400">
                                    <span><?php echo esc_html($row['label']); ?></span>
                                    <?php if ($row['value'] !== '') : ?>
                                        <span class="text-right text-white"><?php echo esc_html($row['value']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($row['percent'] !== null) : ?>
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-white/10" aria-hidden="true">
                                        <div class="h-full rounded-full bg-linear-to-r from-accent to-acid" style="<?php echo esc_attr('width: ' . $row['percent'] . '%'); ?>"></div>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($progress_note !== '') : ?>
                        <p class="mt-4 text-[12px] leading-relaxed text-zinc-400"><?php echo esc_html($progress_note); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
