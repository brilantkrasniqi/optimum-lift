<?php

/**
 * The heading band of a content view (page, blog, archive, search, 404): the
 * shop intro's look (glow, grain, breadcrumb, display heading, lead).
 *
 * `title` is plain text. `lead` is plain text; `lead_html` is trusted markup
 * (an archive description) and wins over `lead`. `width` `wide` lines the band
 * up with a max-w-7xl grid, `narrow` with a max-w-3xl reading column. `search`
 * adds the search form under the lead.
 */

declare(strict_types=1);

/**
 * @var array{title: string, eyebrow?: string, lead?: string, lead_html?: string, meta?: string, width?: string, search?: bool} $args
 */

$narrow    = ($args['width'] ?? 'wide') === 'narrow';
$eyebrow   = $args['eyebrow'] ?? '';
$lead      = $args['lead'] ?? '';
$lead_html = $args['lead_html'] ?? '';
$meta      = $args['meta'] ?? '';
?>
<section class="relative overflow-hidden border-b border-white/[.07]">
    <div class="pointer-events-none absolute -top-40 left-1/2 h-[40rem] w-[40rem] -translate-x-1/2 rounded-full bg-accent/15 blur-[130px]" aria-hidden="true"></div>
    <div class="grain pointer-events-none absolute inset-0 opacity-40" aria-hidden="true"></div>

    <div class="<?php echo esc_attr($narrow ? 'relative mx-auto max-w-3xl px-4 py-12 md:py-16' : 'relative mx-auto max-w-7xl px-4 py-12 md:py-16'); ?>">
        <?php
        if (function_exists('woocommerce_breadcrumb')) {
            woocommerce_breadcrumb();
        }
        ?>

        <?php if ($eyebrow !== '') : ?>
            <p class="mt-5"><span class="eyebrow"><?php echo esc_html($eyebrow); ?></span></p>
        <?php endif; ?>

        <h1 class="mt-5 h-display text-4xl break-words text-white sm:text-5xl"><?php echo esc_html($args['title']); ?></h1>

        <?php if ($meta !== '') : ?>
            <p class="mt-4 text-[12px] font-bold uppercase tracking-wider text-zinc-500"><?php echo esc_html($meta); ?></p>
        <?php endif; ?>

        <?php if ($lead_html !== '') : ?>
            <div class="mt-4 max-w-xl text-[15px] leading-relaxed text-zinc-400 [&_a]:font-bold [&_a]:text-white [&_a]:underline [&_a]:decoration-zinc-600 [&_a]:underline-offset-2"><?php echo wp_kses_post($lead_html); ?></div>
        <?php elseif ($lead !== '') : ?>
            <p class="mt-4 max-w-xl text-[15px] leading-relaxed text-zinc-400"><?php echo esc_html($lead); ?></p>
        <?php endif; ?>

        <?php if (!empty($args['search'])) : ?>
            <div class="mt-6 max-w-xl">
                <?php get_search_form(); ?>
            </div>
        <?php endif; ?>
    </div>
</section>
