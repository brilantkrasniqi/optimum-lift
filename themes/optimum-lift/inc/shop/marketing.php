<?php

/**
 * The email-offers opt-in at checkout, and the list it builds.
 *
 * Consent to marketing email only counts when the buyer gives it: a box that
 * starts unticked, with its own wording, separate from buying. A pre-ticked or
 * hidden box makes every address on the list unusable. So:
 *
 * - An optional box right under the withdrawal waiver above "Place order"
 *   (inc/shop/withdrawal.php), never ticked by default and never part of the
 *   required box, so saying no to offers never blocks the order.
 * - The order keeps when it was ticked and its exact wording, the proof a
 *   mail service or regulator asks for; the order screen shows it.
 * - WooCommerce › Email list downloads everyone who said yes as a CSV, to
 *   import into the mail service, which handles unsubscribes from then on.
 */

declare(strict_types=1);

const OPTIMUM_LIFT_OPTIN_FIELD = 'ol_marketing_optin';

/**
 * The box's wording, stored on the order exactly as the buyer saw it.
 */
function optimum_lift_optin_text(): string
{
    return __('Send me offers and new plans before anyone else. Unsubscribe anytime.', 'optimum-lift');
}

// Below the waiver (priority 10). This spot is inside #payment, which
// WooCommerce re-renders on every checkout refresh, so the box is re-ticked
// from the posted form, as the waiver is.
add_action('woocommerce_review_order_before_submit', static function (): void {
    ?>
    <p class="form-row ol-optin" id="<?php echo esc_attr(OPTIMUM_LIFT_OPTIN_FIELD . '_field'); ?>">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
            <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="<?php echo esc_attr(OPTIMUM_LIFT_OPTIN_FIELD); ?>" id="<?php echo esc_attr(OPTIMUM_LIFT_OPTIN_FIELD); ?>" value="1"<?php checked(optimum_lift_checkout_box_posted(OPTIMUM_LIFT_OPTIN_FIELD)); ?>>
            <span><?php echo esc_html(optimum_lift_optin_text()); ?></span>
        </label>
    </p>
    <?php
}, 20);

add_action('woocommerce_checkout_create_order', static function (mixed $order): void {
    if (!$order instanceof WC_Order || !optimum_lift_checkout_box_posted(OPTIMUM_LIFT_OPTIN_FIELD)) {
        return;
    }

    $order->update_meta_data('_ol_marketing_optin_at', (string) time());
    $order->update_meta_data('_ol_marketing_optin_text', optimum_lift_optin_text());
});

/**
 * When the order's buyer ticked the opt-in, as a Unix time, or 0.
 */
function optimum_lift_order_optin_at(WC_Order $order): int
{
    $at = $order->get_meta('_ol_marketing_optin_at');

    return is_numeric($at) ? (int) $at : 0;
}

add_action('woocommerce_admin_order_data_after_billing_address', static function (mixed $order): void {
    if (!$order instanceof WC_Order) {
        return;
    }

    $at = optimum_lift_order_optin_at($order);
    ?>
    <p>
        <strong><?php esc_html_e('Offer emails:', 'optimum-lift'); ?></strong>
        <?php
        echo esc_html($at > 0
            /* translators: %s: date and time the box was ticked. */
            ? sprintf(__('agreed at checkout, %s', 'optimum-lift'), wp_date(get_option('date_format') . ' ' . get_option('time_format'), $at))
            : __('not agreed', 'optimum-lift'));
        ?>
    </p>
    <?php
}, 20);

/**
 * Everyone who ticked the opt-in, one row per email, from their latest order
 * that says yes.
 *
 * @return list<array{email: string, first_name: string, last_name: string, country: string, agreed_at: string, wording: string}>
 */
function optimum_lift_optin_rows(): array
{
    $orders = wc_get_orders([
        'limit'      => -1,
        'orderby'    => 'date',
        'order'      => 'DESC',
        'meta_query' => [['key' => '_ol_marketing_optin_at', 'compare' => 'EXISTS']],
    ]);

    $rows = [];
    foreach (is_array($orders) ? $orders : [] as $order) {
        if (!$order instanceof WC_Order) {
            continue;
        }

        $email = strtolower($order->get_billing_email());
        $at    = optimum_lift_order_optin_at($order);
        if ($email === '' || $at === 0 || isset($rows[$email])) {
            continue;
        }

        $wording = $order->get_meta('_ol_marketing_optin_text');

        $rows[$email] = [
            'email'      => $email,
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
            'country'    => $order->get_billing_country(),
            'agreed_at'  => gmdate('Y-m-d H:i:s', $at),
            'wording'    => is_string($wording) ? $wording : '',
        ];
    }

    return array_values($rows);
}

add_action('admin_menu', static function (): void {
    add_submenu_page(
        'woocommerce',
        __('Email list', 'optimum-lift'),
        __('Email list', 'optimum-lift'),
        'manage_woocommerce',
        'ol-email-list',
        static function (): void {
            $count = count(optimum_lift_optin_rows());
            $url   = wp_nonce_url(admin_url('admin-post.php?action=ol_email_list'), 'ol_email_list');
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Email list', 'optimum-lift'); ?></h1>
                <p>
                    <?php
                    /* translators: %d: number of buyers. */
                    echo esc_html(sprintf(_n('%d buyer agreed to offer emails at checkout.', '%d buyers agreed to offer emails at checkout.', $count, 'optimum-lift'), $count));
                    ?>
                </p>
                <p><?php esc_html_e('Import the file into your mail service. It keeps track of unsubscribes, so importing again does not add back anyone who left. Never email anyone who is not on this list.', 'optimum-lift'); ?></p>
                <p><a class="button button-primary" href="<?php echo esc_url($url); ?>"><?php esc_html_e('Download CSV', 'optimum-lift'); ?></a></p>
            </div>
            <?php
        }
    );
});

add_action('admin_post_ol_email_list', static function (): void {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You are not allowed to download the email list.', 'optimum-lift'), '', ['response' => 403]);
    }
    check_admin_referer('ol_email_list');

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="email-list-' . gmdate('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }

    // The header names match what Brevo and MailerLite map automatically.
    fputcsv($out, ['email', 'first_name', 'last_name', 'country', 'agreed_at_utc', 'wording'], ',', '"', '');
    foreach (optimum_lift_optin_rows() as $row) {
        // A leading =, +, - or @ makes a spreadsheet run the cell as a formula.
        fputcsv($out, array_map(
            static fn (string $cell): string => preg_match('/^[=+\-@]/', $cell) === 1 ? "'" . $cell : $cell,
            array_values($row)
        ), ',', '"', '');
    }

    fclose($out);
    exit;
});
