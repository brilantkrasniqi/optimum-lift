<?php
/**
 * A countdown to a real offer end (modules/countdown.js keeps it ticking).
 *
 * `inline`: "2d 04:05:06" in a dark chip; the days drop out under 24 hours.
 * `boxes`: four boxes (days, hours, minutes, seconds), as in the final CTA.
 * `class` adds classes to the root. Wrap the surrounding offer UI in
 * [data-countdown-scope] and it is hidden when the countdown reaches zero.
 * Renders nothing once the end has passed.
 */

declare(strict_types=1);

/** @var array{ends_at: int, variant?: string, class?: string} $args */

$ends_at   = (int) ($args['ends_at'] ?? 0);
$remaining = $ends_at - time();

if ($remaining <= 0) {
    return;
}

$days  = intdiv($remaining, DAY_IN_SECONDS);
$units = [
    'd' => (string) $days,
    'h' => sprintf('%02d', intdiv($remaining % DAY_IN_SECONDS, HOUR_IN_SECONDS)),
    'm' => sprintf('%02d', intdiv($remaining % HOUR_IN_SECONDS, MINUTE_IN_SECONDS)),
    's' => sprintf('%02d', $remaining % MINUTE_IN_SECONDS),
];
$extra = $args['class'] ?? '';

if (($args['variant'] ?? 'inline') === 'boxes') :
    $boxes = [
        'd' => __('Days', 'optimum-lift'),
        'h' => __('Hours', 'optimum-lift'),
        'm' => __('Min', 'optimum-lift'),
        's' => __('Sec', 'optimum-lift'),
    ];
    ?>
    <span data-countdown="<?php echo esc_attr(gmdate('c', $ends_at)); ?>" role="timer" class="grid max-w-md grid-cols-4 gap-2.5 <?php echo esc_attr($extra); ?>">
        <?php foreach ($boxes as $unit => $label) : ?>
            <span class="block rounded-2xl border border-white/10 bg-black/40 py-3.5 text-center backdrop-blur">
                <span class="h-display block text-2xl tabular-nums <?php echo $unit === 's' ? 'text-accent-light' : 'text-white'; ?>" data-cd="<?php echo esc_attr($unit); ?>"><?php echo esc_html($units[$unit]); ?></span>
                <span class="block text-[9px] font-bold uppercase tracking-widest text-zinc-500"><?php echo esc_html($label); ?></span>
            </span>
        <?php endforeach; ?>
    </span>
<?php else : ?>
    <span data-countdown="<?php echo esc_attr(gmdate('c', $ends_at)); ?>" role="timer" class="rounded bg-black/25 px-1.5 py-0.5 font-mono font-bold tabular-nums <?php echo esc_attr($extra); ?>"><span data-cd-unit="d"<?php echo $days === 0 ? ' hidden' : ''; ?>><span data-cd="d"><?php echo esc_html($units['d']); ?></span><?php echo esc_html_x('d', 'days, abbreviated', 'optimum-lift'); ?> </span><span data-cd="h"><?php echo esc_html($units['h']); ?></span>:<span data-cd="m"><?php echo esc_html($units['m']); ?></span>:<span data-cd="s"><?php echo esc_html($units['s']); ?></span></span>
<?php endif; ?>
