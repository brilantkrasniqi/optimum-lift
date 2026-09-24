<?php

/**
 * The versions included in the one Product ("Fillestar / Mesatar / I
 * avancuar"). They are check pills, not buttons: nothing here is a choice that
 * changes the purchase (ADR-0006).
 */

declare(strict_types=1);

/** @var array{product: WC_Product, class?: string} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$groups = array_filter(
    optimum_lift_versions($product),
    static fn (array $group): bool => $group['options'] !== []
);
if ($groups === []) {
    return;
}

$note = optimum_lift_field($product->get_id(), 'ol_versions_note');
?>
<div class="<?php echo esc_attr(trim('grid gap-2.5 ' . ($args['class'] ?? ''))); ?>">
    <div class="flex flex-wrap gap-x-8 gap-y-5">
        <?php foreach ($groups as $group) : ?>
            <div>
                <?php if ($group['label'] !== '') : ?>
                    <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-500"><?php echo esc_html($group['label']); ?></p>
                <?php endif; ?>
                <ul class="mt-2.5 flex flex-wrap gap-2"<?php echo $group['label'] !== '' ? ' aria-label="' . esc_attr($group['label']) . '"' : ''; ?>>
                    <?php foreach ($group['options'] as $option) : ?>
                        <li class="inline-flex items-center gap-1 rounded-xl border border-white/10 bg-white/[.03] px-3 py-2 text-[12px] font-bold text-zinc-200 sm:gap-1.5 sm:px-3.5 sm:text-[12.5px]">
                            <?php echo optimum_lift_icon('check', 'w-3 h-3 shrink-0 text-acid sm:w-3.5 sm:h-3.5'); ?>
                            <?php echo esc_html($option); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (is_string($note) && $note !== '') : ?>
        <p class="text-[11px] font-semibold text-zinc-600"><?php echo esc_html($note); ?></p>
    <?php endif; ?>
</div>
