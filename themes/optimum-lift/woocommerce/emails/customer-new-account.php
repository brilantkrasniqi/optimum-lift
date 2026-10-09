<?php

/**
 * Customer new account email: a login link instead of a set-password link
 * when the account has no password of its own yet (inc/shop/login-link.php).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.9.0
 *
 * @var string   $email_heading
 * @var string   $additional_content
 * @var string   $user_login
 * @var string   $user_display_name
 * @var string   $blogname
 * @var bool     $password_generated
 * @var WC_Email $email
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$optimum_lift_user = get_user_by('login', $user_login);

do_action('woocommerce_email_header', $email_heading, $email);
?>

<div class="email-introduction">
    <p><?php echo esc_html(sprintf(/* translators: %s: customer's first name */ __('Hi %s,', 'optimum-lift'), $user_display_name)); ?></p>
    <p><?php echo esc_html(sprintf(/* translators: %s: site name */ __('Your %s account is ready. Your plans and downloads are waiting in it.', 'optimum-lift'), $blogname)); ?></p>

    <?php if ($password_generated && $optimum_lift_user instanceof WP_User && optimum_lift_login_link_allowed($optimum_lift_user)) : ?>
        <?php optimum_lift_login_link_email_button(optimum_lift_login_link_url($optimum_lift_user, DAY_IN_SECONDS)); ?>
        <p><?php esc_html_e('No password needed. The link works once, within 24 hours. After that, enter your email on My Account and we will send you a new link.', 'optimum-lift'); ?></p>
    <?php else : ?>
        <p><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php esc_html_e('Go to my account', 'optimum-lift'); ?></a></p>
    <?php endif; ?>
</div>

<?php
if ($additional_content) {
    echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content email-additional-content-aligned">';
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
    echo '</td></tr></table>';
}

do_action('woocommerce_email_footer', $email);
