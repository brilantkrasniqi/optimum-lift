<?php

/**
 * The trust block next to "Place order" (woocommerce_review_order_after_submit,
 * inside #payment, so it is re-rendered with every checkout refresh): the
 * guarantee, instant access, encrypted payment and the accepted methods.
 */

declare(strict_types=1);

$items = [];

if ((int) optimum_lift_setting('guarantee_days') > 0) {
    $items[] = [
        'icon'  => 'shield-check',
        'title' => optimum_lift_guarantee_label(),
        'text'  => __('Not happy with it? You get your money back, no questions asked.', 'optimum-lift'),
    ];
}

$items[] = [
    'icon'  => 'bolt',
    'title' => __('Instant access', 'optimum-lift'),
    'text'  => __('Your plan arrives by email right after payment.', 'optimum-lift'),
];

$items[] = [
    'icon'  => 'lock',
    'title' => __('Encrypted payment', 'optimum-lift'),
    'text'  => __('Your payment is protected with SSL encryption.', 'optimum-lift'),
];
?>
<div class="ol-checkout-trust mt-5 rounded-2xl border border-white/[.07] bg-white/[.02] p-4">
    <ul class="grid gap-3">
        <?php foreach ($items as $item) : ?>
            <li class="flex items-start gap-3">
                <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-acid/10 text-acid" aria-hidden="true">
                    <?php echo optimum_lift_icon($item['icon'], 'w-4 h-4', ['stroke-width' => '2.2']); ?>
                </span>
                <span class="text-[12px] leading-snug text-zinc-400">
                    <strong class="block text-[13px] font-extrabold text-white"><?php echo esc_html($item['title']); ?></strong>
                    <?php echo esc_html($item['text']); ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php get_template_part('template-parts/product/payment-badges', null, ['class' => 'mt-4 border-t border-white/[.07] pt-4', 'show_ssl' => false]); ?>
</div>
