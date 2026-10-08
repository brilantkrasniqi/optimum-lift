<?php

/**
 * The cookie banner (inc/consent.php). Printed hidden on every page while a
 * Meta Pixel ID is set; modules/consent.js shows it until the visitor
 * chooses, and again from the footer's "Cookie settings". Both buttons look
 * the same: declining must be as easy as accepting.
 */

declare(strict_types=1);

/** @var array{pixel_id: string} $args */

$privacy = get_privacy_policy_url();
$button  = 'flex-1 cursor-pointer rounded-xl border border-white/15 px-4 py-2.5 text-[13px] font-extrabold text-white transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-acid';
?>
<div data-ol-consent data-pixel="<?php echo esc_attr($args['pixel_id'] ?? ''); ?>" hidden role="region" aria-label="<?php esc_attr_e('Cookies', 'optimum-lift'); ?>" class="fixed inset-x-3 bottom-3 z-[60] mx-auto max-w-xl rounded-2xl border border-white/10 bg-surface/95 p-4 shadow-card backdrop-blur-xl sm:inset-x-6 sm:bottom-6">
    <p class="text-[13px] leading-relaxed text-zinc-300">
        <?php esc_html_e('We use cookies to see which ads bring you here and to improve the site. Cookies for the cart and your account are always on.', 'optimum-lift'); ?>
        <?php if ($privacy !== '') : ?>
            <a href="<?php echo esc_url($privacy); ?>" class="font-bold text-white underline decoration-zinc-600 underline-offset-2"><?php esc_html_e('Privacy policy', 'optimum-lift'); ?></a>
        <?php endif; ?>
    </p>
    <div class="mt-3 flex gap-2.5">
        <button type="button" data-ol-consent-choice="no" class="<?php echo esc_attr($button); ?>"><?php esc_html_e('Decline', 'optimum-lift'); ?></button>
        <button type="button" data-ol-consent-choice="yes" class="<?php echo esc_attr($button); ?>"><?php esc_html_e('Accept', 'optimum-lift'); ?></button>
    </div>
</div>
