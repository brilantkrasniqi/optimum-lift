<?php
/**
 * The distraction-free checkout header (ADR-0007): the logo and two reasons to
 * finish paying. No nav, no offer bar, no cart.
 */

declare(strict_types=1);

$guarantee_days = (int) optimum_lift_setting('guarantee_days');
?>
<header class="border-b border-white/[.07] bg-paper">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4">
        <?php get_template_part('template-parts/header/logo'); ?>

        <div class="flex items-center gap-4 text-[11px] font-bold text-zinc-400">
            <span class="flex items-center gap-1.5">
                <?php echo optimum_lift_icon('lock', 'w-4 h-4 text-acid'); ?>
                <?php esc_html_e('Secure payment', 'optimum-lift'); ?>
            </span>
            <?php if ($guarantee_days > 0) : ?>
                <span class="hidden items-center gap-1.5 sm:flex">
                    <?php echo optimum_lift_icon('shield-check', 'w-4 h-4 text-acid', ['stroke-width' => '2.2']); ?>
                    <?php echo esc_html(optimum_lift_guarantee_label()); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</header>
