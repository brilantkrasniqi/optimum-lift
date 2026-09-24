<?php
/**
 * The minimal checkout footer: copyright and the legal pages a buyer may want
 * to read before paying, nothing that leads away from the order.
 */

declare(strict_types=1);

$legal = optimum_lift_legal_links();
?>
<footer class="border-t border-white/[.07] bg-surface">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 sm:flex-row">
        <p class="text-[11px] text-zinc-500">
            <?php
            /* translators: 1: year, 2: site name. */
            echo esc_html(sprintf(__('© %1$s %2$s. All rights reserved.', 'optimum-lift'), wp_date('Y'), get_bloginfo('name')));
            ?>
        </p>
        <?php if ($legal !== []) : ?>
            <ul class="flex flex-wrap justify-center gap-x-5 gap-y-2 text-[11px] font-semibold text-zinc-500">
                <?php foreach ($legal as $link) : ?>
                    <li><a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener" class="transition hover:text-white"><?php echo esc_html($link['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</footer>
