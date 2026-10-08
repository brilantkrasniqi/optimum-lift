<?php
/**
 * Plugin Name: Optimum Lift - Local Mail
 * Description: Local/dev only. Sends every email to the Mailpit container, so
 *              order, account and download emails show at http://localhost:8025
 *              instead of vanishing (the WordPress image has no sendmail).
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ol_local_mail_smtp(PHPMailer\PHPMailer\PHPMailer $mailer): void
{
    if (wp_get_environment_type() !== 'local') {
        return;
    }

    $mailer->isSMTP();
    $mailer->Host = 'mailpit';
    $mailer->Port = 1025;
    $mailer->SMTPAuth = false;
    $mailer->SMTPAutoTLS = false;
}

add_action('phpmailer_init', 'ol_local_mail_smtp');

/**
 * WordPress's own emails (new account, password reset) come from
 * wordpress@<site host>, which on localhost has no dot, so PHPMailer rejects
 * it as invalid and the email is dropped.
 */
function ol_local_mail_from(string $from): string
{
    if (wp_get_environment_type() !== 'local' || !str_ends_with($from, '@localhost')) {
        return $from;
    }

    return $from . '.local';
}

add_filter('wp_mail_from', 'ol_local_mail_from');
