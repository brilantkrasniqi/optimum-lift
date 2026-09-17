<?php

/**
 * The exit-intent offer: the Customizer coupon, offered once to a desktop
 * visitor about to leave (modules/exit-intent.js decides when).
 *
 * Rendered only while the coupon exists and can be used (ADR-0008), and not
 * for a visitor who already holds it. The call to action is a coupon link
 * (inc/shop/coupon.php) to the pricing section, so the discount applies on its
 * own as soon as a Product is in the cart: nobody types a code.
 */

declare(strict_types=1);

/** @var array{anchor: string} $args */

$coupon = optimum_lift_exit_coupon();

if ($coupon === null || wc_is_same_coupon(optimum_lift_stored_coupon(), $coupon->get_code()) || WC()->cart?->has_discount($coupon->get_code())) {
    return;
}

$code = $coupon->get_code();
$url  = add_query_arg('ol_coupon', rawurlencode($code), home_url('/'));
if ($args['anchor'] !== '') {
    $url .= '#' . $args['anchor'];
}

$discount = $coupon->get_discount_type() === 'percent'
    ? esc_html(optimum_lift_format_number((float) $coupon->get_amount(), fmod((float) $coupon->get_amount(), 1.0) > 0 ? 1 : 0) . '%')
    : wp_kses_post(wc_price((float) $coupon->get_amount()));

/* translators: %s: the discount, e.g. 10% or 5,00 €. */
$amount = sprintf(esc_html__('%s extra off', 'optimum-lift'), $discount);

$offer = optimum_lift_offer() !== null
    /* translators: %s: the discount, e.g. "10% extra off", in bold. */
    ? esc_html__('Get %s on top of the current offer.', 'optimum-lift')
    /* translators: %s: the discount, e.g. "10% extra off", in bold. */
    : esc_html__('Get %s on your order.', 'optimum-lift');
?>
<div data-exit-modal hidden class="fixed inset-0 z-[60] grid place-items-center overflow-y-auto bg-black/80 p-4 backdrop-blur-sm">
    <div role="dialog" aria-modal="true" aria-labelledby="ol-exit-title" aria-describedby="ol-exit-text" class="relative w-full max-w-md overflow-hidden rounded-3xl border border-white/10 bg-surface p-7 text-center shadow-card">
        <div class="pointer-events-none absolute -top-16 left-1/2 h-64 w-64 -translate-x-1/2 rounded-full bg-accent/25 blur-3xl" aria-hidden="true"></div>

        <button type="button" data-exit-close class="absolute top-4 right-4 z-10 grid h-11 w-11 place-items-center rounded-lg border border-white/10 text-zinc-400 transition hover:text-white">
            <?php echo optimum_lift_icon('close'); ?>
            <span class="screen-reader-text"><?php esc_html_e('Close', 'optimum-lift'); ?></span>
        </button>

        <div class="relative">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-accent text-white shadow-glow" aria-hidden="true"><?php echo optimum_lift_icon('bolt', 'w-7 h-7'); ?></span>
            <h2 id="ol-exit-title" class="mt-5 h-display text-2xl text-white"><?php esc_html_e('Wait — don’t leave it for Monday', 'optimum-lift'); ?></h2>
            <p id="ol-exit-text" class="mt-3 text-[14px] leading-relaxed text-zinc-400">
                <?php printf($offer, '<strong class="text-white">' . $amount . '</strong>'); ?>
                <?php esc_html_e('It is applied automatically as soon as you add a product to your cart:', 'optimum-lift'); ?>
            </p>
            <p class="mt-4 rounded-xl border border-dashed border-acid/50 bg-acid/10 px-4 py-3">
                <span class="h-display text-2xl tracking-widest text-acid"><?php echo esc_html($code); ?></span>
            </p>
            <a href="<?php echo esc_url($url); ?>" rel="nofollow" data-cta="exit-modal" data-exit-cta class="btn btn-light btn-block mt-5 rounded-xl px-5 py-3.5 text-base"><?php esc_html_e('Use it now', 'optimum-lift'); ?></a>
            <button type="button" data-exit-close class="mt-3 min-h-11 text-[12px] font-semibold text-zinc-500 transition hover:text-zinc-300"><?php esc_html_e('No thanks, I’ll continue without the discount', 'optimum-lift'); ?></button>
        </div>
    </div>
</div>
