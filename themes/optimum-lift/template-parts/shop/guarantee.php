<?php

/**
 * The guarantee band under the grid. The days come from the Customizer; with
 * no guarantee there is nothing to promise, so nothing renders.
 */

declare(strict_types=1);

$days = (int) optimum_lift_setting('guarantee_days');
if ($days < 1) {
    return;
}
?>
<section class="border-t border-white/[.07] bg-surface/50 py-16">
    <div class="mx-auto max-w-5xl px-4">
        <div class="flex flex-col items-start gap-6 rounded-[2rem] border border-acid/25 bg-linear-to-br from-acid/[.08] via-surface to-surface p-8 sm:flex-row sm:items-center">
            <div class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-acid text-paper">
                <?php echo optimum_lift_icon('shield-check', 'w-8 h-8', ['stroke-width' => '2.1']); ?>
            </div>
            <div>
                <h2 class="h-display text-2xl text-white sm:text-3xl"><?php
                    /* translators: %d: number of days of the money-back guarantee. */
                    echo esc_html(sprintf(_n('%d-day guarantee on every product', '%d-day guarantee on every product', $days, 'optimum-lift'), $days));
                ?></h2>
                <p class="mt-3 max-w-2xl text-[14.5px] leading-relaxed text-zinc-300">
                    <?php
                    /* translators: %d: number of days of the money-back guarantee. */
                    echo esc_html(sprintf(_n('Try it for %d day.', 'Try it for %d days.', $days, 'optimum-lift'), $days));
                    echo ' ';
                    esc_html_e('Not convinced? Send us an email and we refund all of your money, with no forms and no awkward conversation.', 'optimum-lift');
                    ?>
                </p>
            </div>
        </div>
    </div>
</section>
