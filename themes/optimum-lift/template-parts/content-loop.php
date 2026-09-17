<?php

/**
 * The main query as a list: a card grid (blog, archives) or a result list
 * (search), then pagination, or the empty state.
 */

declare(strict_types=1);

/** @var array{variant?: string} $args */

$search = ($args['variant'] ?? 'grid') === 'search';
?>
<div class="<?php echo esc_attr($search ? 'mx-auto max-w-3xl px-4 py-12 md:py-16' : 'mx-auto max-w-7xl px-4 py-12 md:py-16'); ?>">
    <?php if (have_posts()) : ?>
        <div class="<?php echo esc_attr($search ? 'grid gap-3' : 'grid gap-5 sm:grid-cols-2 lg:grid-cols-3'); ?>">
            <?php
            while (have_posts()) {
                the_post();
                get_template_part('template-parts/content', $search ? 'search' : (string) get_post_type());
            }
            ?>
        </div>

        <?php
        the_posts_pagination([
            'mid_size'           => 1,
            'prev_text'          => '<span aria-hidden="true">←</span><span class="screen-reader-text">' . esc_html__('Previous page', 'optimum-lift') . '</span>',
            'next_text'          => '<span class="screen-reader-text">' . esc_html__('Next page', 'optimum-lift') . '</span><span aria-hidden="true">→</span>',
            'before_page_number' => '<span class="screen-reader-text">' . esc_html__('Page', 'optimum-lift') . ' </span>',
            'class'              => 'ol-pagination',
        ]);
        ?>
    <?php else : ?>
        <?php get_template_part('template-parts/content', 'none'); ?>
    <?php endif; ?>
</div>
