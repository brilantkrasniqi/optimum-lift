<?php

/**
 * The payment methods the store actually offers (Customizer), then the SSL
 * note. A badge for a method we do not take costs trust at checkout, so the
 * list is never hard-coded.
 */

declare(strict_types=1);

/** @var array{class?: string, show_ssl?: bool} $args */

$setting  = optimum_lift_setting('payment_badges');
$methods  = is_string($setting) ? array_values(array_filter(array_map('trim', explode(',', $setting)))) : [];
$show_ssl = $args['show_ssl'] ?? true;

if ($methods === [] && !$show_ssl) {
    return;
}
?>
<div class="<?php echo esc_attr(trim('flex flex-wrap items-center gap-2 ' . ($args['class'] ?? ''))); ?>">
    <?php if ($methods !== []) : ?>
        <ul class="flex flex-wrap items-center gap-2" aria-label="<?php esc_attr_e('Accepted payment methods', 'optimum-lift'); ?>">
            <?php foreach ($methods as $method) : ?>
                <li class="rounded border border-white/15 px-2 py-1 text-[10px] font-bold text-zinc-400"><?php echo esc_html($method); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($show_ssl) : ?>
        <span class="ml-auto text-[10px] font-bold uppercase tracking-wider text-zinc-500"><?php esc_html_e('SSL-encrypted payment', 'optimum-lift'); ?></span>
    <?php endif; ?>
</div>
