<?php

/**
 * "Pagesë e njëhershme · Akses i menjëhershëm · Sukses i garantuar": answers
 * "is this a subscription?" next to the price, where the question comes up.
 *
 * `items` lists the standard keys `one-time`, `instant` and `guarantee`, or any
 * other (already translated) text; the default is the three keys. The
 * guarantee is the Customizer promise and drops out when it is empty.
 */

declare(strict_types=1);

/** @var array{items?: list<string>, class?: string} $args */

$standard = [
    'one-time'  => __('One-time payment', 'optimum-lift'),
    'instant'   => __('Instant access', 'optimum-lift'),
    'guarantee' => optimum_lift_guarantee_label(),
];

$lines = [];
foreach ($args['items'] ?? array_keys($standard) as $item) {
    $line = $standard[$item] ?? $item;
    if ($line !== '') {
        $lines[] = $line;
    }
}

if ($lines === []) {
    return;
}
?>
<p class="<?php echo esc_attr($args['class'] ?? 'text-[11px] font-semibold text-zinc-500'); ?>"><?php echo esc_html(implode(' · ', $lines)); ?></p>
