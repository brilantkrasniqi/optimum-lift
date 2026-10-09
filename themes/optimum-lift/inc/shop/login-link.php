<?php

/**
 * Login links: customers log in from a link we email them, no password needed.
 *
 * - My Account's login form has a second button, "Email me a login link". It
 *   emails a link to the account with that email (or username), and always
 *   says the same thing, so it never tells who has an account.
 * - The link opens My Account with a "Log in" button. Opening the link logs no
 *   one in: mail scanners open links before the customer does, and would use up
 *   a link meant to work once. The button's POST does.
 * - A link works once and expires: 15 minutes when asked for, 24 hours in the
 *   new-account email (woocommerce/emails/customer-new-account.php), which
 *   carries one instead of WooCommerce's set-password link.
 * - Registering asks for no password and no longer logs the new account in; the
 *   new-account email's link does. Otherwise anyone could register someone
 *   else's email, stay logged in, and receive the Plans that person buys as a
 *   guest later (the Plans plugin attaches guest orders by email, ADR-0004).
 * - Only customers: anyone who can edit content logs in with a password.
 *
 * Passwords still work, and "Lost your password?" sets one.
 */

declare(strict_types=1);

const OPTIMUM_LIFT_LOGIN_LINK_META = '_ol_login_link';
const OPTIMUM_LIFT_LOGIN_LINK_SENT = '_ol_login_link_sent';

/**
 * Whether a user may log in from a link.
 */
function optimum_lift_login_link_allowed(WP_User $user): bool
{
    return !user_can($user, 'edit_posts');
}

/**
 * A new login link for a user, replacing any earlier one.
 */
function optimum_lift_login_link_url(WP_User $user, int $lifetime = 15 * MINUTE_IN_SECONDS): string
{
    $token = wp_generate_password(40, false);

    update_user_meta($user->ID, OPTIMUM_LIFT_LOGIN_LINK_META, [
        'hash'    => hash_hmac('sha256', $token, wp_salt('auth')),
        'expires' => time() + $lifetime,
    ]);

    return add_query_arg(
        ['ol-login' => $user->ID, 'ol-key' => $token],
        wc_get_page_permalink('myaccount')
    );
}

/**
 * The user a login link is for, if it is still valid.
 */
function optimum_lift_login_link_user(mixed $user_id, mixed $token): ?WP_User
{
    if (!is_numeric($user_id) || !is_string($token) || $token === '') {
        return null;
    }

    $user = get_user_by('id', (int) $user_id);
    if (!$user instanceof WP_User || !optimum_lift_login_link_allowed($user)) {
        return null;
    }

    $link = get_user_meta($user->ID, OPTIMUM_LIFT_LOGIN_LINK_META, true);
    if (!is_array($link) || !is_string($link['hash'] ?? null) || (int) ($link['expires'] ?? 0) < time()) {
        return null;
    }

    return hash_equals($link['hash'], hash_hmac('sha256', $token, wp_salt('auth'))) ? $user : null;
}

/**
 * The login link and its key from the request, unslashed.
 *
 * @param array<string, mixed> $source $_GET or $_POST.
 * @return array{0: mixed, 1: mixed}
 */
function optimum_lift_login_link_params(array $source): array
{
    $key = $source['ol-key'] ?? null;

    return [$source['ol-login'] ?? null, is_string($key) ? sanitize_text_field(wp_unslash($key)) : null];
}

/**
 * A notice that survives the redirect after it. A visitor who is not logged in
 * has no WooCommerce session yet, and without one the notice would be lost.
 */
function optimum_lift_login_link_notice(string $message, string $type = 'success'): void
{
    $session = WC()->session;
    if ($session instanceof WC_Session_Handler) {
        $session->set_customer_session_cookie(true);
    }

    wc_add_notice($message, $type);
}

/**
 * Emails a login link, at most one a minute per account.
 */
function optimum_lift_send_login_link(WP_User $user): void
{
    $sent = (int) get_user_meta($user->ID, OPTIMUM_LIFT_LOGIN_LINK_SENT, true);
    if ($sent > time() - MINUTE_IN_SECONDS) {
        return;
    }

    update_user_meta($user->ID, OPTIMUM_LIFT_LOGIN_LINK_SENT, time());

    $url     = optimum_lift_login_link_url($user);
    $mailer  = WC()->mailer();
    $heading = __('Your login link', 'optimum-lift');
    $name    = $user->first_name !== '' ? $user->first_name : $user->display_name;

    ob_start();
    ?>
    <p><?php echo esc_html(sprintf(/* translators: %s: customer's first name */ __('Hi %s,', 'optimum-lift'), $name)); ?></p>
    <p><?php esc_html_e('Tap the button to log in to your Optimum Lift account. No password needed.', 'optimum-lift'); ?></p>
    <?php optimum_lift_login_link_email_button($url); ?>
    <p><?php esc_html_e('The link works once, within 15 minutes. If you did not ask for it, ignore this email: no one can log in without it.', 'optimum-lift'); ?></p>
    <?php
    $body = (string) ob_get_clean();

    $mailer->send(
        $user->user_email,
        sprintf(/* translators: %s: site name */ __('Log in to %s', 'optimum-lift'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)),
        $mailer->wrap_message($heading, $body)
    );
}

/**
 * The email's button, in WooCommerce's email colours.
 */
function optimum_lift_login_link_email_button(string $url): void
{
    $colour = (string) get_option('woocommerce_email_base_color', '#E41B23');

    printf(
        '<p style="margin:24px 0;"><a href="%1$s" style="display:inline-block;padding:14px 24px;border-radius:10px;background:%2$s;color:#ffffff;font-weight:700;text-decoration:none;">%3$s</a></p>',
        esc_url($url),
        esc_attr($colour),
        esc_html__('Log in to my account', 'optimum-lift')
    );
}

// The second button on My Account's login form. It submits the same form
// without WooCommerce's "login" field, so WooCommerce leaves it alone.
add_action('woocommerce_login_form_end', static function (): void {
    if (!is_account_page()) {
        return;
    }
    ?>
    <div class="ol-login-link">
        <p class="ol-login-link__or"><span><?php esc_html_e('or, without a password', 'optimum-lift'); ?></span></p>
        <button type="submit" name="ol_login_link" value="1" class="woocommerce-button button ol-login-link__button">
            <?php echo optimum_lift_icon('mail', 'w-4 h-4'); ?>
            <?php esc_html_e('Email me a login link', 'optimum-lift'); ?>
        </button>
    </div>
    <?php
}, 5);

// Asking for a link. Runs before WooCommerce's own form handlers (20).
add_action('wp_loaded', static function (): void {
    if (!isset($_POST['ol_login_link'], $_POST['woocommerce-login-nonce']) || is_user_logged_in()) {
        return;
    }

    $nonce = sanitize_text_field(wp_unslash((string) $_POST['woocommerce-login-nonce']));
    if (!wp_verify_nonce($nonce, 'woocommerce-login')) {
        return;
    }

    $login = isset($_POST['username']) && is_string($_POST['username']) ? trim(sanitize_text_field(wp_unslash($_POST['username']))) : '';
    if ($login === '') {
        optimum_lift_login_link_notice(__('Enter your email address, then ask for the link.', 'optimum-lift'), 'error');

        return;
    }

    $user = get_user_by(is_email($login) ? 'email' : 'login', $login);
    if ($user instanceof WP_User && optimum_lift_login_link_allowed($user)) {
        optimum_lift_send_login_link($user);
    }

    optimum_lift_login_link_notice(__('If an account uses that email, a login link is on its way. Check your inbox (and spam). The link works once, within 15 minutes.', 'optimum-lift'));
    wp_safe_redirect(wc_get_page_permalink('myaccount'));
    exit;
}, 15);

// Using a link: the "Log in" button's POST.
add_action('wp_loaded', static function (): void {
    if (!isset($_POST['ol_login_link_use'])) {
        return;
    }

    [$user_id, $token] = optimum_lift_login_link_params($_POST);
    $user              = optimum_lift_login_link_user($user_id, $token);

    if ($user === null) {
        optimum_lift_login_link_notice(__('That login link has expired or was already used. Ask for a new one below.', 'optimum-lift'), 'error');
        wp_safe_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }

    delete_user_meta($user->ID, OPTIMUM_LIFT_LOGIN_LINK_META);

    wp_clear_auth_cookie();
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true, is_ssl());
    do_action('wp_login', $user->user_login, $user);

    wp_safe_redirect(wc_get_page_permalink('myaccount'));
    exit;
}, 15);

// Opening a link: a valid one shows the "Log in" button instead of the login
// forms; anything else says so and returns to them. The key stays off any
// Referer header the page sends.
add_action('template_redirect', static function (): void {
    if (!isset($_GET['ol-login']) || !is_account_page()) {
        return;
    }

    if (is_user_logged_in()) {
        wp_safe_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }

    [$user_id, $token] = optimum_lift_login_link_params($_GET);
    if (optimum_lift_login_link_user($user_id, $token) === null) {
        optimum_lift_login_link_notice(__('That login link has expired or was already used. Ask for a new one below.', 'optimum-lift'), 'error');
        wp_safe_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }

    nocache_headers();
    header('Referrer-Policy: no-referrer');
});

add_filter('wc_get_template', static function (string $template, string $template_name): string {
    if ($template_name !== 'myaccount/form-login.php' || !isset($_GET['ol-login'])) {
        return $template;
    }

    [$user_id, $token] = optimum_lift_login_link_params($_GET);
    $user              = optimum_lift_login_link_user($user_id, $token);

    if ($user === null) {
        return $template;
    }

    set_query_var('ol_login_link', ['user' => $user, 'id' => $user->ID, 'key' => $token]);

    return OPTIMUM_LIFT_DIR . '/template-parts/account/login-link.php';
}, 10, 2);

/*
 * Logging in attaches the customer's guest orders, so a diet bought without
 * an account shows under My Account › Diets. The Plans plugin attaches only
 * orders with a Plan, when they are paid. Safe because a customer can only log
 * in after proving the email: from a login link, or a password set from a
 * reset link (registering logs no one in).
 */
add_action('wp_login', static function (string $login, WP_User $user): void {
    if (optimum_lift_login_link_allowed($user)) {
        wc_update_new_customer_past_orders($user->ID);
    }
}, 10, 2);

/*
 * Registering: no password field, and no login until the emailed link is used.
 * Forced here rather than left to WooCommerce › Settings › Accounts, so the
 * flow cannot drift from what the emails promise.
 */
add_filter('pre_option_woocommerce_registration_generate_password', static fn (): string => 'yes');

add_filter('woocommerce_registration_auth_new_customer', '__return_false');

// WooCommerce's "Your account is using a temporary password. We emailed you a
// link to change your password." notice: customers were emailed a login link
// instead, and need no password.
add_filter('get_user_option_default_password_nag', static function (mixed $nag, string $option, WP_User $user): mixed {
    return optimum_lift_login_link_allowed($user) ? false : $nag;
}, 10, 3);

add_filter('gettext_woocommerce', static function (string $translation, string $text): string {
    return match ($text) {
        'A link to set a new password will be sent to your email address.' => __('No password needed: we will email you a link to log in.', 'optimum-lift'),
        'Your account was created successfully and a password has been sent to your email address.' => __('Your account is ready. We emailed you a link to log in.', 'optimum-lift'),
        default => $translation,
    };
}, 10, 2);
