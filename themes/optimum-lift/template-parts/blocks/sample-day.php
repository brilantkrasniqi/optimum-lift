<?php

/**
 * A full day of eating with real amounts and macros: the diet buyer's main
 * fear is being hungry, and only the actual table answers it.
 *
 * Day totals carry their unit ("2.200 kcal", "143 g"); a meal's kcal and
 * macros do not, since the column headers name them. The note allows bold.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block = $args['block'] ?? [];

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

$meals = [];
foreach (is_array($block['meals'] ?? null) ? $block['meals'] : [] as $row) {
    if (!is_array($row)) {
        continue;
    }

    $meal = [
        'time'        => $text($row['time'] ?? null),
        'name'        => $text($row['name'] ?? null),
        'description' => $text($row['description'] ?? null),
        'kcal'        => $text($row['kcal'] ?? null),
        'macros'      => $text($row['macros'] ?? null),
    ];

    if ($meal['name'] !== '' || $meal['description'] !== '') {
        $meals[] = $meal;
    }
}

$facts = [];
foreach (is_array($block['facts'] ?? null) ? $block['facts'] : [] as $row) {
    $value = is_array($row) ? $text($row['value'] ?? null) : '';
    if ($value !== '') {
        $facts[] = ['value' => $value, 'text' => $text($row['text'] ?? null)];
    }
}

if ($meals === [] && $facts === []) {
    return;
}

$eyebrow   = $text($block['eyebrow'] ?? null);
$heading   = $text($block['heading'] ?? null);
$intro     = $text($block['intro'] ?? null);
$day_label = $text($block['day_label'] ?? null);
$kcal      = $text($block['kcal'] ?? null);
$note      = $text($block['note'] ?? null);

$filled = static fn (string $value): bool => $value !== '';

$macros = array_filter([
    __('Protein', 'optimum-lift')                    => $text($block['protein'] ?? null),
    __('Carbs', 'optimum-lift')                      => $text($block['carbs'] ?? null),
    _x('Fat', 'macronutrient total', 'optimum-lift') => $text($block['fat'] ?? null),
], $filled);

// A column no meal fills is left out rather than printed empty.
$has_kcal   = array_filter(array_column($meals, 'kcal'), $filled) !== [];
$has_macros = array_filter(array_column($meals, 'macros'), $filled) !== [];

$facts_columns = match (count($facts)) {
    1       => 'mx-auto max-w-md',
    2       => 'md:grid-cols-2',
    4       => 'md:grid-cols-2 lg:grid-cols-4',
    default => 'md:grid-cols-3',
};
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'sample-day')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="mx-auto max-w-6xl px-4">
        <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
            <div class="reveal mx-auto max-w-2xl text-center">
                <?php if ($eyebrow !== '') : ?>
                    <span class="eyebrow eyebrow--acid"><?php echo esc_html($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($heading !== '') : ?>
                    <h2 class="mt-5 h-display text-3xl text-white first:mt-0 sm:text-4xl"><?php echo optimum_lift_heading_html($heading); ?></h2>
                <?php endif; ?>
                <?php if ($intro !== '') : ?>
                    <p class="mt-4 text-[15px] leading-relaxed text-zinc-400 first:mt-0"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($meals !== []) : ?>
            <div class="reveal mt-10 overflow-hidden rounded-3xl border border-white/[.08] bg-surface first:mt-0">
                <?php if ($day_label !== '' || $kcal !== '' || $macros !== []) : ?>
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/[.07] bg-paper/60 px-6 py-4">
                        <?php if ($day_label !== '') : ?>
                            <h3 class="text-[12px] font-extrabold uppercase tracking-[.16em] text-zinc-400"><?php echo esc_html($day_label); ?></h3>
                        <?php endif; ?>
                        <?php if ($kcal !== '' || $macros !== []) : ?>
                            <p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11.5px] font-bold">
                                <?php if ($kcal !== '') : ?>
                                    <span class="text-white"><?php echo esc_html($kcal); ?></span>
                                <?php endif; ?>
                                <?php foreach ($macros as $macro => $amount) : ?>
                                    <span class="text-zinc-500"><?php echo esc_html($macro); ?> <span class="text-acid"><?php echo esc_html($amount); ?></span></span>
                                <?php endforeach; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="scroll-x scroll-hint overflow-x-auto" role="region" tabindex="0" aria-label="<?php echo esc_attr($day_label !== '' ? $day_label : __('Meals', 'optimum-lift')); ?>">
                    <table class="w-full min-w-[42rem] text-left">
                        <thead>
                            <tr class="border-b border-white/[.07] text-[10px] uppercase tracking-[.16em] text-zinc-500">
                                <th scope="col" class="px-6 py-3 font-extrabold"><?php esc_html_e('Time', 'optimum-lift'); ?></th>
                                <th scope="col" class="px-6 py-3 font-extrabold"><?php esc_html_e('Meal', 'optimum-lift'); ?></th>
                                <?php if ($has_kcal) : ?>
                                    <th scope="col" class="px-6 py-3 text-right font-extrabold"><?php esc_html_e('Kcal', 'optimum-lift'); ?></th>
                                <?php endif; ?>
                                <?php if ($has_macros) : ?>
                                    <th scope="col" class="px-6 py-3 text-right font-extrabold">
                                        <span aria-hidden="true"><?php esc_html_e('P / C / F', 'optimum-lift'); ?></span>
                                        <span class="screen-reader-text"><?php esc_html_e('Protein / carbs / fat (g)', 'optimum-lift'); ?></span>
                                    </th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="text-[13.5px]">
                            <?php foreach ($meals as $meal) : ?>
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 align-top font-bold text-zinc-500"><?php echo esc_html($meal['time']); ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($meal['name'] !== '') : ?>
                                            <div class="font-extrabold text-white"><?php echo esc_html($meal['name']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($meal['description'] !== '') : ?>
                                            <div class="mt-1 text-zinc-400 first:mt-0"><?php echo esc_html($meal['description']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($has_kcal) : ?>
                                        <td class="px-6 py-4 text-right align-top font-bold text-white"><?php echo esc_html($meal['kcal']); ?></td>
                                    <?php endif; ?>
                                    <?php if ($has_macros) : ?>
                                        <td class="whitespace-nowrap px-6 py-4 text-right align-top font-semibold text-zinc-400"><?php echo esc_html($meal['macros']); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($note !== '') : ?>
                    <div class="border-t border-white/[.07] bg-paper/60 px-6 py-4">
                        <p class="text-[12.5px] leading-relaxed text-zinc-400 [&_b]:text-white [&_strong]:text-white"><?php echo wp_kses($note, ['strong' => [], 'b' => []]); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($facts !== []) : ?>
            <div class="<?php echo esc_attr('reveal mt-6 grid gap-5 first:mt-0 ' . $facts_columns); ?>">
                <?php foreach ($facts as $fact) : ?>
                    <div class="rounded-2xl border border-white/[.08] bg-surface p-5">
                        <div class="h-display text-2xl text-acid"><?php echo esc_html($fact['value']); ?></div>
                        <?php if ($fact['text'] !== '') : ?>
                            <p class="mt-1 text-[13px] font-semibold text-zinc-400"><?php echo esc_html($fact['text']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
