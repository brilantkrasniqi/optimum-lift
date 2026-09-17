<?php

/**
 * "Pagesë e njëhershme · Akses i menjëhershëm · Garanci 30 ditë": answers "is
 * this a subscription?" next to the price, where the question comes up.
 *
 * `items` lists the standard keys `one-time`, `instant` and `guarantee`, or any
 * other (already translated) text; the default is the three keys. The
 * guarantee follows the Customizer and drops out when it is zero.
 */

declare(strict_types=1);

/** @var array{items?: list<string>, class?: string} $args */

$days     = (int) optimum_lift_setting('guarantee_days');
$standard = [
    'one-time'  => __('One-time payment', 'optimum-lift'),
    'instant'   => __('Instant access', 'optimum-lift'),
    /* translators: %d: number of days of the money-back guarantee. */
    'guarantee' => sprintf(_n('%d-day guarantee', '%d-day guarantee', $days, 'optimum-lift'), $days),
];

$lines = [];
foreach ($args['items'] ?? array_keys($standard) as $item) {
    if ($item === 'guarantee' && $days < 1) {
        continue;
    }
    $lines[] = $standard[$item] ?? $item;
}

if ($lines === []) {
    return;
}
?>
<p class="<?php echo esc_attr($args['class'] ?? 'text-[11px] font-semibold text-zinc-500'); ?>"><?php echo esc_html(implode(' · ', $lines)); ?></p>
