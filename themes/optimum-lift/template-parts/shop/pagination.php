<?php

/**
 * Page links under the grid, for a catalogue larger than one page
 * (loop_shop_per_page). The sort survives because paginate_links() keeps the
 * query string.
 */

declare(strict_types=1);

/** @var WP_Query $wp_query */
global $wp_query;

if ($wp_query->max_num_pages < 2) {
    return;
}

$links = paginate_links([
    'total'     => $wp_query->max_num_pages,
    'current'   => max(1, (int) get_query_var('paged')),
    'type'      => 'array',
    'prev_text' => __('Previous', 'optimum-lift'),
    'next_text' => __('Next', 'optimum-lift'),
]);

if (!is_array($links) || $links === []) {
    return;
}
?>
<nav class="mt-10" aria-label="<?php esc_attr_e('Pages', 'optimum-lift'); ?>">
    <ul class="flex flex-wrap items-center justify-center gap-2 text-[13px] font-extrabold [&_.page-numbers]:inline-flex [&_.page-numbers]:min-h-11 [&_.page-numbers]:min-w-11 [&_.page-numbers]:items-center [&_.page-numbers]:justify-center [&_.page-numbers]:rounded-xl [&_.page-numbers]:border [&_.page-numbers]:border-white/10 [&_.page-numbers]:px-4 [&_.page-numbers]:text-zinc-400 [&_a.page-numbers]:transition [&_a.page-numbers:hover]:border-white/28 [&_a.page-numbers:hover]:text-white [&_.current]:border-white [&_.current]:bg-white [&_.current]:text-paper [&_.dots]:border-transparent">
        <?php foreach ($links as $link) : ?>
            <li><?php echo wp_kses_post($link); ?></li>
        <?php endforeach; ?>
    </ul>
</nav>
