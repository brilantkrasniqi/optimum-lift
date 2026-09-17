<?php

/**
 * The category filter, the sort and the result count.
 *
 * The filter pills are links to the shop and the kind archives. The sort is a
 * GET form on the current archive: modules/shop-sort.js submits it when the
 * select changes, and without JavaScript its own button does. Other query
 * arguments (a search) ride along as hidden fields.
 */

declare(strict_types=1);

/** @var array{count: int} $args */

$count   = $args['count'] ?? 0;
$current = optimum_lift_shop_orderby();
?>
<section class="mx-auto max-w-7xl px-4 pt-10">
    <div class="flex flex-col gap-4 border-b border-white/[.07] pb-5 lg:flex-row lg:items-center lg:justify-between">
        <nav aria-label="<?php esc_attr_e('Filter by category', 'optimum-lift'); ?>">
            <ul class="flex flex-wrap items-center gap-2">
                <?php foreach (optimum_lift_shop_filters() as $filter) : ?>
                    <li>
                        <?php if ($filter['current']) : ?>
                            <a href="<?php echo esc_url($filter['url']); ?>" aria-current="page" class="inline-flex min-h-11 items-center rounded-xl border border-white bg-white px-4 py-2.5 text-[12.5px] font-extrabold text-paper transition"><?php echo esc_html($filter['label']); ?></a>
                        <?php else : ?>
                            <a href="<?php echo esc_url($filter['url']); ?>" class="inline-flex min-h-11 items-center rounded-xl border border-white/10 px-4 py-2.5 text-[12.5px] font-extrabold text-zinc-400 transition hover:border-white/28 hover:text-white"><?php echo esc_html($filter['label']); ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <form method="get" action="<?php echo esc_url(get_pagenum_link(1, false)); ?>" class="flex flex-wrap items-center gap-3" autocomplete="off">
            <label class="flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-widest text-zinc-500">
                <?php esc_html_e('Sort', 'optimum-lift'); ?>
                <select name="orderby" data-shop-sort class="min-h-11 rounded-xl border border-white/10 bg-surface px-3 py-2.5 text-[12.5px] font-bold normal-case text-zinc-200 focus:border-white/30">
                    <?php foreach (optimum_lift_shop_orderby_options() as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>"<?php selected($current, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php wc_query_string_form_fields(null, ['orderby', 'paged', 'product-page', 'submit', 'add-to-cart', 'added-to-cart']); ?>
            <noscript>
                <button type="submit" class="btn btn-ghost btn-sm min-h-11"><?php esc_html_e('Apply', 'optimum-lift'); ?></button>
            </noscript>
        </form>
    </div>

    <p class="mt-4 text-[12px] font-bold uppercase tracking-wider text-zinc-500">
        <?php
        /* translators: %s: number of Products shown. */
        echo esc_html(sprintf(_n('%s product', '%s products', $count, 'optimum-lift'), optimum_lift_format_number($count)));
        ?>
    </p>
</section>
