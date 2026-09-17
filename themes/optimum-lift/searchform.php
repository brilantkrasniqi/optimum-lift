<?php

/**
 * The search form (get_search_form()). It can appear twice on one page (the
 * search intro and the empty state), so the field id is unique per render.
 */

declare(strict_types=1);

$field_id = wp_unique_id('search-field-');
?>
<form role="search" method="get" class="flex gap-2" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="screen-reader-text" for="<?php echo esc_attr($field_id); ?>"><?php esc_html_e('Search', 'optimum-lift'); ?></label>
    <input type="search" id="<?php echo esc_attr($field_id); ?>" name="s"
           value="<?php echo esc_attr(get_search_query()); ?>"
           placeholder="<?php esc_attr_e('Search articles, programs, plans…', 'optimum-lift'); ?>"
           class="min-h-12 min-w-0 flex-1 rounded-xl border border-white/15 bg-surface px-4 text-base text-white placeholder:text-zinc-500 transition focus:border-white/35 focus:outline-2 focus:outline-offset-2 focus:outline-acid sm:text-[15px]">
    <button type="submit" class="btn btn-light btn-md min-h-12 shrink-0">
        <?php echo optimum_lift_icon('search', 'w-4 h-4'); ?>
        <span class="max-[379px]:sr-only"><?php esc_html_e('Search', 'optimum-lift'); ?></span>
    </button>
</form>
