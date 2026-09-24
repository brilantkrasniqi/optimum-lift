<?php

/**
 * A comparison table: one column per option, one row per question.
 *
 * Row types: `text` prints the cells, where a leading ✓ renders acid and ✕
 * muted; `price` prints the column Product's current price, so the table
 * follows price changes (a column without a Product keeps its cell, e.g. "0 €");
 * `cta` links to the column's Product, or says "Po e shikon" on that Product's
 * own page. A bundle gets the accent button. Rows with nothing to show are left
 * out. On narrow screens the table scrolls sideways with a fade hint.
 */

declare(strict_types=1);

/** @var array{block: array<string, mixed>, product: ?WC_Product, post_id: int, index: int} $args */

$block   = $args['block'] ?? [];
$post_id = (int) ($args['post_id'] ?? 0);

$text = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

/** @var list<array{label: string, product: ?WC_Product, highlight: bool}> $columns */
$columns = [];
foreach (is_array($block['columns'] ?? null) ? $block['columns'] : [] as $row) {
    if (!is_array($row)) {
        continue;
    }

    $column_product = is_numeric($row['product'] ?? null) ? wc_get_product((int) $row['product']) : null;
    $column_product = $column_product instanceof WC_Product && $column_product->get_status() === 'publish' ? $column_product : null;
    $label          = $text($row['label'] ?? null);

    $columns[] = [
        'label'     => $label === '' && $column_product !== null ? optimum_lift_plain_text($column_product->get_name()) : $label,
        'product'   => $column_product,
        'highlight' => !empty($row['highlight']),
    ];
}

/*
 * Each cell as HTML, built here so the markup below stays a plain loop. Values
 * are escaped as they are put together.
 */
$mark = static function (string $value): string {
    if (preg_match('/^(✓|✕)\s*(.*)$/su', $value, $match) !== 1) {
        return esc_html($value);
    }

    $yes  = $match[1] === '✓';
    $rest = trim($match[2]);
    $html = '<span aria-hidden="true">' . $match[1] . '</span>';
    $html .= $rest !== ''
        ? ' ' . esc_html($rest)
        : '<span class="screen-reader-text">' . esc_html($yes ? __('Yes', 'optimum-lift') : __('No', 'optimum-lift')) . '</span>';

    return '<span class="' . ($yes ? 'text-acid' : 'text-zinc-500') . '">' . $html . '</span>';
};

/** @var list<array{label: string, type: string, cells: list<string>}> $rows */
$rows = [];
foreach (is_array($block['rows'] ?? null) ? $block['rows'] : [] as $row) {
    if (!is_array($row) || $columns === []) {
        continue;
    }

    $type   = in_array($row['type'] ?? '', ['text', 'price', 'cta'], true) ? (string) $row['type'] : 'text';
    $values = [];
    foreach (is_array($row['cells'] ?? null) ? array_values($row['cells']) : [] as $cell) {
        $values[] = is_array($cell) ? $text($cell['value'] ?? null) : '';
    }

    $cells = [];
    $empty = true;
    foreach ($columns as $i => $column) {
        $value          = $values[$i] ?? '';
        $column_product = $column['product'];
        $html           = $mark($value);

        if ($type === 'price' && $column_product !== null && $column_product->get_price() !== '') {
            $html = wc_price(optimum_lift_current_price($column_product));
        } elseif ($type === 'cta' && $column_product !== null) {
            if ($column_product->get_id() === $post_id) {
                $html = '<span class="inline-block rounded-lg bg-white/10 px-3 py-2 text-[11px] font-extrabold uppercase tracking-wider text-zinc-400">' . esc_html__('You\'re viewing it', 'optimum-lift') . '</span>';
            } else {
                $html = sprintf(
                    '<a href="%1$s" data-cta="%2$s" class="%3$s">%4$s<span class="screen-reader-text">: %5$s</span></a>',
                    esc_url($column_product->get_permalink()),
                    esc_attr('compare-view-' . $column_product->get_id()),
                    esc_attr(optimum_lift_is_bundle($column_product)
                        ? 'inline-block rounded-lg bg-accent px-3 py-2 text-[11px] font-extrabold uppercase tracking-wider text-white transition hover:bg-accent-light'
                        : 'inline-block rounded-lg border border-white/15 px-3 py-2 text-[11px] font-extrabold uppercase tracking-wider text-white transition hover:bg-white/5'),
                    esc_html__('View', 'optimum-lift'),
                    esc_html($column_product->get_name())
                );
            }
        }

        $empty   = $empty && $html === '';
        $cells[] = $html;
    }

    $label = $text($row['label'] ?? null);
    if (!$empty) {
        $rows[] = ['label' => $label, 'type' => $type, 'cells' => $cells];
    }
}

$has_labels = array_filter($columns, static fn (array $column): bool => $column['label'] !== '') !== [];
if ($rows === [] || !$has_labels) {
    return;
}

$eyebrow = $text($block['eyebrow'] ?? null);
$heading = $text($block['heading'] ?? null);
$intro   = $text($block['intro'] ?? null);
$note    = $text($block['note'] ?? null);
$front   = $post_id > 0 && $post_id === optimum_lift_front_page_id();
$alt     = optimum_lift_block_tone_class($block) !== '';
$caption = $heading !== '' ? trim(str_replace('*', '', (string) preg_replace('/\s+/u', ' ', $heading))) : __('Comparison', 'optimum-lift');
$last    = count($rows) - 1;

/**
 * @param array{highlight: bool} $column
 * @param array{type: string} $row
 */
$cell_class = static fn (array $column, array $row): string => match (true) {
    $column['highlight'] && $row['type'] === 'price' => 'bg-accent/[.06] p-4 text-center font-extrabold text-white sm:p-5',
    $column['highlight']                             => 'bg-accent/[.06] p-4 text-center sm:p-5',
    default                                          => 'p-4 text-center text-zinc-400 sm:p-5',
};
?>
<section id="<?php echo esc_attr(optimum_lift_block_id($block, 'comparison')); ?>" class="py-20 md:py-28 <?php echo esc_attr(optimum_lift_block_tone_class($block)); ?>">
    <div class="mx-auto max-w-5xl px-4">
        <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
            <div class="reveal mx-auto max-w-2xl text-center">
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

        <div class="<?php echo esc_attr($alt ? 'reveal scroll-hint mt-10 rounded-3xl border border-white/[.08] bg-paper first:mt-0' : 'reveal scroll-hint mt-10 rounded-3xl border border-white/[.08] bg-surface first:mt-0'); ?>">
            <div class="scroll-x overflow-x-auto rounded-3xl" tabindex="0" role="region" aria-label="<?php echo esc_attr($caption); ?>">
                <table class="<?php echo esc_attr($front ? 'w-full min-w-[620px] text-left text-[13px]' : 'w-full min-w-[680px] text-left text-[13px]'); ?>">
                    <thead>
                        <tr class="border-b border-white/[.08] text-[10px] font-extrabold uppercase tracking-[.14em] text-zinc-500">
                            <td class="p-4 sm:p-5"></td>
                            <?php foreach ($columns as $column) : ?>
                                <th scope="col" class="<?php echo esc_attr($column['highlight'] ? 'bg-accent/10 p-4 text-center font-extrabold text-accent-light sm:p-5' : 'p-4 text-center font-extrabold sm:p-5'); ?>"><?php echo esc_html($column['label']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="font-semibold text-zinc-300">
                        <?php foreach ($rows as $r => $row) : ?>
                            <tr class="<?php echo esc_attr($r < $last ? 'border-b border-white/[.05]' : ''); ?>">
                                <th scope="row" class="p-4 text-left font-semibold text-zinc-400 sm:p-5"><?php echo esc_html($row['label']); ?></th>
                                <?php foreach ($columns as $i => $column) : ?>
                                    <td class="<?php echo esc_attr($cell_class($column, $row)); ?>"><?php echo wp_kses_post($row['cells'][$i]); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($note !== '') : ?>
            <p class="reveal mt-5 text-center text-[13px] leading-relaxed text-zinc-400"><?php echo esc_html($note); ?></p>
        <?php endif; ?>
    </div>
</section>
