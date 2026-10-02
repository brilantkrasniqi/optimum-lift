<?php

/**
 * The guarantee band under the grid: the Customizer promise and what backs it.
 * With the promise switched off there is nothing to say, so nothing renders.
 */

declare(strict_types=1);

$guarantee = optimum_lift_guarantee_label();
if ($guarantee === '') {
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
                <h2 class="h-display text-2xl text-white sm:text-3xl"><?php echo esc_html($guarantee); ?></h2>
                <p class="mt-3 max-w-2xl text-[14.5px] leading-relaxed text-zinc-300"><?php echo esc_html(optimum_lift_guarantee_text()); ?></p>
            </div>
        </div>
    </div>
</section>
