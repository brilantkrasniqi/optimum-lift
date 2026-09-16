<?php

/**
 * Turns paid orders into Access, and refunds or cancellations into revoked
 * Access.
 *
 * Grants run on `woocommerce_order_status_{processing,completed}`, which fire
 * before the status-transition emails are sent, so those emails can already
 * link to the Portal.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Access;

use WC_Order;
use WC_Order_Item_Product;
use WP_User;

final class OrderAccess
{
    /** Order item meta: the Plans the Product included when it was bought. */
    private const ITEM_PLANS = '_ol_plan_ids';

    public function __construct(private AccessRepository $access)
    {
    }

    public function register(): void
    {
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'snapshotItemPlans'], 10, 3);
        add_action('woocommerce_order_status_processing', [$this, 'grant'], 5, 2);
        add_action('woocommerce_order_status_completed', [$this, 'grant'], 5, 2);
        add_action('woocommerce_order_status_refunded', [$this, 'revoke'], 5);
        add_action('woocommerce_order_status_cancelled', [$this, 'revoke'], 5);
        add_action('woocommerce_admin_order_data_after_order_details', [$this, 'renderOrderPanel']);
        add_filter('woocommerce_hidden_order_itemmeta', [$this, 'hideItemMeta']);
    }

    public function snapshotItemPlans(WC_Order_Item_Product $item, string $cartItemKey, mixed $values): void
    {
        $planIds = ProductFields::planIds($item->get_product_id());

        if ($planIds !== []) {
            $item->add_meta_data(self::ITEM_PLANS, $planIds, true);
        }
    }

    public function grant(int $orderId, ?WC_Order $order = null): void
    {
        $order ??= wc_get_order($orderId);

        if (!$order instanceof WC_Order) {
            return;
        }

        $grants = $this->itemPlans($order);

        if ($grants === []) {
            return;
        }

        $userId = $this->customerFor($order);

        if ($userId === 0) {
            $order->add_order_note(__('Could not create an account for this order, so no Plan Access was granted.', 'optimum-lift-plans'));

            return;
        }

        foreach ($grants as [$planId, $productId]) {
            $this->access->grant($userId, $planId, $orderId, $productId);
        }
    }

    public function revoke(int $orderId): void
    {
        $this->access->revokeOrder($orderId);
    }

    /**
     * @return list<array{int, int}> [plan ID, product ID] pairs
     */
    private function itemPlans(WC_Order $order): array
    {
        $grants = [];

        foreach ($order->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $planIds = $item->get_meta(self::ITEM_PLANS);

            // Orders created in wp-admin skip checkout, so snapshot on first grant.
            if (!is_array($planIds)) {
                $planIds = ProductFields::planIds($item->get_product_id());
                $item->update_meta_data(self::ITEM_PLANS, $planIds);
                $item->save();
            }

            foreach ($planIds as $planId) {
                $grants[] = [(int) $planId, $item->get_product_id()];
            }
        }

        return $grants;
    }

    /**
     * Access needs an account. A guest checkout is attached to the existing
     * account with its billing email, or gets a new account with a
     * set-password email (ADR-0004).
     */
    private function customerFor(WC_Order $order): int
    {
        if ($order->get_customer_id() > 0) {
            return $order->get_customer_id();
        }

        $email = $order->get_billing_email();

        if (!is_email($email)) {
            return 0;
        }

        $user = get_user_by('email', $email);

        if ($user instanceof WP_User) {
            $userId = $user->ID;
        } else {
            // With this option on, WooCommerce generates the password and its
            // new-account email carries a set-password link instead.
            $forceGenerated = static fn (): string => 'yes';
            add_filter('pre_option_woocommerce_registration_generate_password', $forceGenerated);

            $userId = wc_create_new_customer($email, '', '', [
                'first_name' => $order->get_billing_first_name(),
                'last_name'  => $order->get_billing_last_name(),
            ]);

            remove_filter('pre_option_woocommerce_registration_generate_password', $forceGenerated);

            if (is_wp_error($userId)) {
                return 0;
            }
        }

        $order->set_customer_id($userId);
        $order->save();

        return $userId;
    }

    public function renderOrderPanel(WC_Order $order): void
    {
        $rows = $this->access->forOrder($order->get_id());

        if ($rows === []) {
            return;
        }

        echo '<p class="form-field form-field-wide"><strong>' . esc_html__('Plan Access', 'optimum-lift-plans') . '</strong><br>';

        foreach ($rows as $row) {
            printf(
                '<a href="%s">%s</a>%s<br>',
                esc_url((string) get_edit_post_link($row['plan_id'])),
                esc_html(get_the_title($row['plan_id'])),
                $row['revoked'] ? ' <em>(' . esc_html__('revoked', 'optimum-lift-plans') . ')</em>' : ''
            );
        }

        echo '</p>';
    }

    /**
     * @param list<string> $hidden
     * @return list<string>
     */
    public function hideItemMeta(array $hidden): array
    {
        $hidden[] = self::ITEM_PLANS;

        return $hidden;
    }
}
