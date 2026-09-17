<?php

/**
 * One post in a list: a card whose title link covers the whole card. Posts
 * without a featured image get the placeholder tile, so a grid stays even.
 */

declare(strict_types=1);

// The default category ("Uncategorized") says nothing about the post.
$categories = array_values(array_filter(get_the_category(), static fn (WP_Term $term): bool => $term->term_id !== (int) get_option('default_category')));
$category   = $categories !== [] ? $categories[0]->name : '';
$excerpt    = wp_trim_words(get_the_excerpt(), 24);
?>
<article <?php post_class('group relative flex flex-col overflow-hidden rounded-3xl border border-white/[.08] bg-surface transition hover:border-white/20'); ?>>
    <div class="relative aspect-[16/9] overflow-hidden">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('medium_large', ['class' => 'absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]', 'sizes' => '(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw']); ?>
        <?php else : ?>
            <span class="ph-photo absolute inset-0 grid place-items-center text-zinc-700" aria-hidden="true">
                <?php echo optimum_lift_icon('logo', 'w-12 h-12', ['stroke-width' => '1.6']); ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="flex flex-1 flex-col p-6">
        <p class="flex flex-wrap items-center gap-x-2 text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-500">
            <?php if ($category !== '') : ?>
                <span class="text-accent-light"><?php echo esc_html($category); ?></span>
                <span aria-hidden="true">·</span>
            <?php endif; ?>
            <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date()); ?></time>
        </p>

        <h2 class="mt-3 text-lg leading-snug font-extrabold text-white">
            <a href="<?php echo esc_url(get_permalink()); ?>" class="transition after:absolute after:inset-0 group-hover:text-accent-light"><?php echo esc_html(get_the_title()); ?></a>
        </h2>

        <?php if ($excerpt !== '') : ?>
            <p class="mt-2 text-[14px] leading-relaxed text-zinc-400"><?php echo esc_html($excerpt); ?></p>
        <?php endif; ?>

        <span class="mt-auto inline-flex items-center gap-1.5 pt-5 text-[13px] font-extrabold text-white" aria-hidden="true">
            <?php esc_html_e('Read', 'optimum-lift'); ?>
            <?php echo optimum_lift_icon('arrow-right', 'w-3.5 h-3.5 transition group-hover:translate-x-0.5'); ?>
        </span>
    </div>
</article>
