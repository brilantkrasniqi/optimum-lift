<?php
/**
 * The offer bar above the header, with its countdown. Only while a real offer
 * with a future end date runs (ADR-0008): a Product's scheduled sale on its own
 * page, else the site offer from the Customizer. It hides itself at zero.
 */

declare(strict_types=1);

$offer = function_exists('optimum_lift_offer') ? optimum_lift_offer(optimum_lift_current_product()) : null;

if ($offer === null || $offer['ends_at'] <= time()) {
    return;
}
?>
<div data-countdown-scope class="relative z-50 bg-accent text-white">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-3 gap-y-1 px-4 py-2.5 text-center">
        <span class="text-[11px] font-extrabold uppercase tracking-[.14em] sm:text-xs"><?php echo esc_html($offer['label']); ?></span>
        <span class="hidden text-white/40 sm:inline" aria-hidden="true">|</span>
        <span class="flex items-center gap-1.5 text-[11px] font-semibold text-white sm:text-xs">
            <?php echo optimum_lift_icon('clock', 'w-3.5 h-3.5'); ?>
            <?php esc_html_e('Ends in', 'optimum-lift'); ?>
            <?php get_template_part('template-parts/components/countdown', null, ['ends_at' => $offer['ends_at'], 'variant' => 'inline']); ?>
        </span>
    </div>
</div>
