<?php

/**
 * Access rows (ADR-0004). A revoked row is kept, with revoked_at set, as the
 * record of what an order once granted.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Access;

use OptimumLift\Plans\Schema;

final class AccessRepository
{
    /** @var array<int, list<int>> */
    private array $planIdsByUser = [];

    /**
     * Grant, or restore a revoked grant from the same order.
     */
    public function grant(int $userId, int $planId, int $orderId, int $productId): void
    {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            'INSERT INTO ' . Schema::access() . ' (user_id, plan_id, order_id, product_id, granted_at)
             VALUES (%d, %d, %d, %d, %s)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), revoked_at = NULL',
            $userId,
            $planId,
            $orderId,
            $productId,
            current_time('mysql', true)
        ));

        unset($this->planIdsByUser[$userId]);
    }

    public function revokeOrder(int $orderId): void
    {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            'UPDATE ' . Schema::access() . ' SET revoked_at = %s WHERE order_id = %d AND revoked_at IS NULL',
            current_time('mysql', true),
            $orderId
        ));

        $this->planIdsByUser = [];
    }

    public function hasAccess(int $userId, int $planId): bool
    {
        return $userId > 0 && in_array($planId, $this->planIds($userId), true);
    }

    /**
     * Whether the user may open the Plan in the Portal: Access, or the right to
     * edit the Plan (so authors can preview it as a Customer would see it).
     */
    public function canFollow(int $userId, int $planId): bool
    {
        return $this->hasAccess($userId, $planId) || user_can($userId, 'edit_post', $planId);
    }

    /**
     * Plans the Customer currently has Access to, oldest grant first.
     *
     * @return list<int>
     */
    public function planIds(int $userId): array
    {
        if (!isset($this->planIdsByUser[$userId])) {
            global $wpdb;

            $ids = $wpdb->get_col($wpdb->prepare(
                'SELECT plan_id FROM ' . Schema::access() . '
                 WHERE user_id = %d AND revoked_at IS NULL
                 GROUP BY plan_id ORDER BY MIN(granted_at), plan_id',
                $userId
            ));

            $this->planIdsByUser[$userId] = array_map('intval', $ids);
        }

        return $this->planIdsByUser[$userId];
    }

    /**
     * @return list<array{plan_id: int, product_id: int, revoked: bool}>
     */
    public function forOrder(int $orderId): array
    {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT plan_id, product_id, revoked_at FROM ' . Schema::access() . ' WHERE order_id = %d ORDER BY id',
            $orderId
        ), ARRAY_A);

        return array_map(static fn (array $row): array => [
            'plan_id'    => (int) $row['plan_id'],
            'product_id' => (int) $row['product_id'],
            'revoked'    => $row['revoked_at'] !== null,
        ], $rows ?: []);
    }

    /**
     * Every row for a user, for the personal-data exporter.
     *
     * @return list<array<string, string|null>>
     */
    public function allForUser(int $userId): array
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            'SELECT plan_id, order_id, granted_at, revoked_at FROM ' . Schema::access() . ' WHERE user_id = %d ORDER BY id',
            $userId
        ), ARRAY_A) ?: [];
    }

    public function deleteForUser(int $userId): void
    {
        global $wpdb;

        $wpdb->delete(Schema::access(), ['user_id' => $userId], ['%d']);
        unset($this->planIdsByUser[$userId]);
    }
}
