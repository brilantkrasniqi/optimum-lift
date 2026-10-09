<?php

/**
 * My Account opened from a valid login link (inc/shop/login-link.php): one
 * button that logs in, in place of the login and register forms.
 */

declare(strict_types=1);

$optimum_lift_link = get_query_var('ol_login_link');

if (!is_array($optimum_lift_link) || !($optimum_lift_link['user'] ?? null) instanceof WP_User) {
    return;
}
?>
<form class="ol-login-link-confirm" method="post" action="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">
    <h2><?php esc_html_e('Log in', 'optimum-lift'); ?></h2>
    <p>
        <?php
        printf(
            /* translators: %s: the account's email address */
            esc_html__('You are logging in as %s.', 'optimum-lift'),
            '<strong>' . esc_html($optimum_lift_link['user']->user_email) . '</strong>'
        );
        ?>
    </p>
    <input type="hidden" name="ol-login" value="<?php echo esc_attr((string) $optimum_lift_link['id']); ?>">
    <input type="hidden" name="ol-key" value="<?php echo esc_attr((string) $optimum_lift_link['key']); ?>">
    <button type="submit" name="ol_login_link_use" value="1" class="woocommerce-button button ol-login-link-confirm__submit">
        <?php esc_html_e('Log in to my account', 'optimum-lift'); ?>
    </button>
</form>
