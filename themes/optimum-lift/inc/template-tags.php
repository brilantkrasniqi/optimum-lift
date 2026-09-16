<?php
/**
 * Small helpers used by templates. Keep presentation logic here rather than
 * inline in the template files.
 */

declare(strict_types=1);

/**
 * The heading for the current archive / search / 404 view.
 */
function optimum_lift_page_title(): string
{
    if (is_search()) {
        /* translators: %s: search query. */
        return sprintf(__('Results for "%s"', 'optimum-lift'), get_search_query());
    }

    if (is_404()) {
        return __('Page not found', 'optimum-lift');
    }

    if (is_home() && !is_front_page()) {
        return (string) get_the_title((int) get_option('page_for_posts'));
    }

    return wp_strip_all_tags(get_the_archive_title());
}

/**
 * Post meta line for the blog. Products do not use this.
 */
function optimum_lift_entry_meta(): void
{
    printf(
        '<p class="entry__meta"><time datetime="%1$s">%2$s</time></p>',
        esc_attr(get_the_date(DATE_W3C)),
        esc_html(get_the_date())
    );
}
