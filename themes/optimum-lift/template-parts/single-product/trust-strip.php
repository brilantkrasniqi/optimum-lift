<?php

/**
 * The stats band under the hero (`ol_stats`, at most 4). A stat whose token
 * has no value yet is already dropped by optimum_lift_stats(), so the grid
 * takes as many columns as there are stats instead of leaving holes. Values
 * and labels are HTML with the tokens replaced: they print as they are.
 */

declare(strict_types=1);

/** @var array{product: WC_Product} $args */

$product = $args['product'] ?? null;
if (!$product instanceof WC_Product) {
    return;
}

$stats = optimum_lift_stats($product);
if ($stats === []) {
    return;
}

$columns = match (count($stats)) {
    1       => 'grid-cols-1',
    2       => 'grid-cols-2',
    3       => 'grid-cols-3',
    default => 'grid-cols-2 md:grid-cols-4',
};
?>
<div class="relative border-y border-white/[.07] bg-surface/60">
    <ul class="<?php echo esc_attr('mx-auto grid max-w-7xl divide-x divide-white/[.07] ' . $columns); ?>">
        <?php foreach ($stats as $index => $stat) : ?>
            <li class="<?php echo esc_attr($index >= 2 && count($stats) === 4 ? 'border-t border-white/[.07] px-4 py-5 text-center md:border-t-0' : 'px-4 py-5 text-center'); ?>">
                <span class="block h-display text-2xl text-white"><?php echo $stat['value']; ?></span>
                <span class="mt-0.5 block text-[10px] font-bold uppercase tracking-widest text-zinc-500"><?php echo $stat['label']; ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
