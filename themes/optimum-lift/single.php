<?php

/**
 * A single blog post: intro band, featured image, prose, tags, the previous
 * and next posts, comments, then the way back to the shop.
 */

declare(strict_types=1);

get_header();

while (have_posts()) :
    the_post();

    $categories = array_values(array_filter(get_the_category(), static fn (WP_Term $term): bool => $term->term_id !== (int) get_option('default_category')));
    $tags       = get_the_tags();
    // str_word_count() splits Albanian words at ë and ç.
    $words      = preg_split('/\s+/u', trim(wp_strip_all_tags(get_the_content())), -1, PREG_SPLIT_NO_EMPTY);
    $minutes    = max(1, (int) round(count($words ?: []) / 220));
    ?>
    <main id="main" tabindex="-1">
        <?php
        get_template_part('template-parts/content-intro', null, [
            'title'   => get_the_title(),
            'eyebrow' => $categories !== [] ? $categories[0]->name : '',
            'width'   => 'narrow',
            'meta'    => implode(' · ', [
                get_the_date(),
                /* translators: %d: estimated reading time in minutes. */
                sprintf(_n('%d min read', '%d min read', $minutes, 'optimum-lift'), $minutes),
            ]),
        ]);
        ?>

        <article <?php post_class('mx-auto max-w-3xl px-4 py-12 md:py-16'); ?>>
            <?php if (has_post_thumbnail()) : ?>
                <figure class="mb-10 overflow-hidden rounded-3xl border border-white/[.08]">
                    <?php the_post_thumbnail('large', ['class' => 'h-auto w-full', 'sizes' => '(min-width: 800px) 768px, 100vw']); ?>
                </figure>
            <?php endif; ?>

            <div class="prose-ol text-[15px] sm:text-base">
                <?php the_content(); ?>
            </div>

            <?php
            wp_link_pages([
                'before' => '<nav class="ol-page-links" aria-label="' . esc_attr__('Page', 'optimum-lift') . '">',
                'after'  => '</nav>',
            ]);
            ?>

            <?php if (is_array($tags) && $tags !== []) : ?>
                <ul class="mt-10 flex flex-wrap gap-2" aria-label="<?php esc_attr_e('Tags', 'optimum-lift'); ?>">
                    <?php foreach ($tags as $tag) : ?>
                        <li><a href="<?php echo esc_url(get_tag_link($tag)); ?>" class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] font-bold text-zinc-300 transition hover:border-white/30 hover:text-white">#<?php echo esc_html($tag->name); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php
            the_post_navigation([
                'prev_text'          => '<span class="block text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-500">' . esc_html__('Previous', 'optimum-lift') . '</span><span class="mt-1 block font-extrabold text-white">%title</span>',
                'next_text'          => '<span class="block text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-500">' . esc_html__('Next', 'optimum-lift') . '</span><span class="mt-1 block font-extrabold text-white">%title</span>',
                'screen_reader_text' => __('More posts', 'optimum-lift'),
                'class'              => 'ol-post-nav',
            ]);

            if (comments_open() || get_comments_number() > 0) {
                comments_template();
            }
            ?>
        </article>

        <?php get_template_part('template-parts/content-shop-cta'); ?>
    </main>
    <?php
endwhile;

get_footer();
