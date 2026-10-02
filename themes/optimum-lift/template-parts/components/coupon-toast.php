<?php

/**
 * The confirmation a coupon link (inc/shop/coupon.php) leaves on the page it
 * lands on: the code is saved and applies itself on the next add to cart, or
 * the cart already carries it. Printed once; modules/coupon-toast.js shows it
 * and hides it when closed or when the cart opens (the drawer then shows the
 * discount itself).
 */

declare(strict_types=1);

/** @var array{coupon: WC_Coupon, applied: bool} $args */

$coupon = $args['coupon'] ?? null;
if (!$coupon instanceof WC_Coupon) {
    return;
}

$applied = !empty($args['applied']);
$code    = '<span class="uppercase tracking-wider text-acid">' . esc_html($coupon->get_code()) . '</span>';
/* translators: %s: the discount, e.g. 10% or 5,00 €. */
$amount = sprintf(esc_html__('%s extra off', 'optimum-lift'), optimum_lift_coupon_discount_html($coupon));

$title = $applied
    /* translators: %s: the coupon code. */
    ? esc_html__('Code %s applied', 'optimum-lift')
    /* translators: %s: the coupon code. */
    : esc_html__('Code %s saved', 'optimum-lift');
$text = $applied
    /* translators: %s: the discount, e.g. "10% extra off". */
    ? esc_html__('%s is already in your cart.', 'optimum-lift')
    /* translators: %s: the discount, e.g. "10% extra off". */
    : esc_html__('%s is applied automatically as soon as you add a product to your cart.', 'optimum-lift');
?>
<div data-coupon-toast hidden role="status" class="fixed inset-x-4 top-[calc(var(--ol-sticky-top)+4.75rem)] z-[45] mx-auto flex max-w-md items-start gap-3 rounded-2xl border border-acid/40 bg-surface/95 p-4 shadow-card backdrop-blur-xl sm:right-6 sm:left-auto sm:mx-0 sm:w-[26rem]">
    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-acid text-paper" aria-hidden="true">
        <?php echo optimum_lift_icon('check', 'w-5 h-5', ['stroke-width' => '2.6']); ?>
    </span>
    <div class="min-w-0 flex-1 text-[13px] leading-snug text-zinc-300">
        <p class="font-extrabold text-white"><?php printf($title, $code); ?></p>
        <p class="mt-1"><?php printf($text, '<strong class="text-white">' . $amount . '</strong>'); ?></p>
    </div>
    <button type="button" data-coupon-toast-close class="-m-1 grid h-9 w-9 shrink-0 place-items-center rounded-lg text-zinc-400 transition hover:text-white">
        <?php echo optimum_lift_icon('close', 'w-4 h-4'); ?>
        <span class="screen-reader-text"><?php esc_html_e('Close', 'optimum-lift'); ?></span>
    </button>
</div>
