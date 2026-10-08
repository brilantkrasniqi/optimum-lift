<?php

/**
 * Cookie consent: nothing that tracks a visitor runs before they accept.
 *
 * The store's own cookies (cart, checkout, login) are essential and need no
 * consent. Two things do, and both wait for "Accept":
 *
 * - The Meta Pixel. It loads only when its ID is set in the Customizer, and
 *   setting it is also what shows the banner. With no ID there is nothing to
 *   ask about, so there is no banner.
 * - WooCommerce's order attribution (the sbjs_* cookies). It is switched off
 *   here for everyone; modules/consent.js switches it on in the browser for a
 *   visitor who accepted, so a cached page can never carry someone else's
 *   choice.
 *
 * The choice is a first-party cookie, ol_consent=yes|no, kept for six months.
 * "Decline" is as easy as "Accept", and the footer's "Cookie settings" brings
 * the banner back to change it.
 */

declare(strict_types=1);

add_filter('wc_order_attribution_allow_tracking', '__return_false');

/**
 * Whether anything on the site needs the visitor's consent.
 */
function optimum_lift_consent_needed(): bool
{
    return (string) optimum_lift_setting('meta_pixel_id') !== '';
}

add_action('wp_footer', static function (): void {
    if (optimum_lift_consent_needed()) {
        get_template_part('template-parts/components/consent-banner', null, [
            'pixel_id' => (string) optimum_lift_setting('meta_pixel_id'),
        ]);
    }
}, 5);

/**
 * The footer button that reopens the banner, when there is one.
 */
function optimum_lift_consent_settings_button(string $class): void
{
    if (!optimum_lift_consent_needed()) {
        return;
    }
    ?>
    <button type="button" data-ol-consent-open class="<?php echo esc_attr($class); ?>"><?php esc_html_e('Cookie settings', 'optimum-lift'); ?></button>
    <?php
}
