<?php

/**
 * Customer new account email, plain text: a login link instead of a
 * set-password link (see ../customer-new-account.php).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.9.0
 *
 * @var string   $email_heading
 * @var string   $additional_content
 * @var string   $user_login
 * @var string   $user_display_name
 * @var string   $blogname
 * @var bool     $password_generated
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$optimum_lift_user = get_user_by('login', $user_login);

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html(wp_strip_all_tags($email_heading));
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html(sprintf(/* translators: %s: customer's first name */ __('Hi %s,', 'optimum-lift'), $user_display_name)) . "\n\n";
echo esc_html(sprintf(/* translators: %s: site name */ __('Your %s account is ready. Your plans and downloads are waiting in it.', 'optimum-lift'), $blogname)) . "\n\n";

if ($password_generated && $optimum_lift_user instanceof WP_User && optimum_lift_login_link_allowed($optimum_lift_user)) {
    echo esc_html__('Log in to my account', 'optimum-lift') . ":\n";
    echo esc_url_raw(optimum_lift_login_link_url($optimum_lift_user, DAY_IN_SECONDS)) . "\n\n";
    echo esc_html__('No password needed. The link works once, within 24 hours. After that, enter your email on My Account and we will send you a new link.', 'optimum-lift') . "\n\n";
} else {
    echo esc_html(wc_get_page_permalink('myaccount')) . "\n\n";
}

echo "----------------------------------------\n\n";

if ($additional_content) {
    echo esc_html(wp_strip_all_tags(wptexturize($additional_content)));
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));
