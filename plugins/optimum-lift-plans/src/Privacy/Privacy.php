<?php

/**
 * GDPR: Tools › Export / Erase Personal Data cover the custom tables, and
 * deleting a user deletes their rows.
 *
 * Erasure removes Workout Logs and Logged Sets but keeps Access: it records a
 * purchase, which the shop must keep alongside the order.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Privacy;

use OptimumLift\Plans\Access\AccessRepository;
use OptimumLift\Plans\Logging\LoggedSet;
use OptimumLift\Plans\Logging\WorkoutLogRepository;
use WP_User;

final class Privacy
{
    private const PAGE_SIZE = 50;

    public function __construct(
        private WorkoutLogRepository $logs,
        private AccessRepository $access,
    ) {
    }

    public function register(): void
    {
        add_filter('wp_privacy_personal_data_exporters', [$this, 'registerExporter']);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'registerEraser']);
        add_action('deleted_user', [$this, 'deleteUser']);
    }

    /**
     * @param array<string, array<string, mixed>> $exporters
     * @return array<string, array<string, mixed>>
     */
    public function registerExporter(array $exporters): array
    {
        $exporters['optimum-lift-plans'] = [
            'exporter_friendly_name' => __('Training Plans', 'optimum-lift-plans'),
            'callback'               => [$this, 'export'],
        ];

        return $exporters;
    }

    /**
     * @param array<string, array<string, mixed>> $erasers
     * @return array<string, array<string, mixed>>
     */
    public function registerEraser(array $erasers): array
    {
        $erasers['optimum-lift-plans'] = [
            'eraser_friendly_name' => __('Workout Logs', 'optimum-lift-plans'),
            'callback'             => [$this, 'erase'],
        ];

        return $erasers;
    }

    /**
     * @return array{data: list<array<string, mixed>>, done: bool}
     */
    public function export(string $email, int $page = 1): array
    {
        $user = get_user_by('email', $email);

        if (!$user instanceof WP_User) {
            return ['data' => [], 'done' => true];
        }

        $data = [];

        if ($page === 1) {
            foreach ($this->access->allForUser($user->ID) as $index => $row) {
                $data[] = [
                    'group_id'    => 'ol-access',
                    'group_label' => __('Plan Access', 'optimum-lift-plans'),
                    'item_id'     => 'ol-access-' . $index,
                    'data'        => [
                        ['name' => __('Plan', 'optimum-lift-plans'), 'value' => get_the_title((int) $row['plan_id'])],
                        ['name' => __('Order', 'optimum-lift-plans'), 'value' => (string) $row['order_id']],
                        ['name' => __('Granted', 'optimum-lift-plans'), 'value' => (string) $row['granted_at']],
                        ['name' => __('Revoked', 'optimum-lift-plans'), 'value' => (string) $row['revoked_at']],
                    ],
                ];
            }
        }

        $logs = $this->logs->logsForUser($user->ID, self::PAGE_SIZE, ($page - 1) * self::PAGE_SIZE);

        foreach ($logs as $log) {
            $sets = [];

            foreach ($this->logs->sets($log->id) as $bySet) {
                foreach ($bySet as $set) {
                    $sets[] = sprintf(
                        '%s, set %d: %s kg, %s',
                        get_the_title($set->exerciseId),
                        $set->setNumber,
                        $set->loadKg === null ? '-' : (string) $set->loadKg,
                        $set->seconds !== null ? $set->seconds . ' s' : $set->reps . ' reps'
                    );
                }
            }

            $data[] = [
                'group_id'    => 'ol-workout-logs',
                'group_label' => __('Workout Logs', 'optimum-lift-plans'),
                'item_id'     => 'ol-workout-log-' . $log->id,
                'data'        => [
                    ['name' => __('Plan', 'optimum-lift-plans'), 'value' => get_the_title($log->planId)],
                    ['name' => __('Workout', 'optimum-lift-plans'), 'value' => sprintf('Week %d: %s', $log->weekNumber, $log->workoutName)],
                    ['name' => __('Started', 'optimum-lift-plans'), 'value' => $log->startedAt],
                    ['name' => __('Completed', 'optimum-lift-plans'), 'value' => (string) $log->completedAt],
                    ['name' => __('Notes', 'optimum-lift-plans'), 'value' => $log->notes],
                    ['name' => __('Sets', 'optimum-lift-plans'), 'value' => implode("\n", $sets)],
                ],
            ];
        }

        return ['data' => $data, 'done' => count($logs) < self::PAGE_SIZE];
    }

    /**
     * @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool}
     */
    public function erase(string $email, int $page = 1): array
    {
        $user = get_user_by('email', $email);

        if (!$user instanceof WP_User) {
            return ['items_removed' => false, 'items_retained' => false, 'messages' => [], 'done' => true];
        }

        $removed  = $this->logs->deleteForUser($user->ID) > 0;
        $retained = $this->access->planIds($user->ID) !== [];

        return [
            'items_removed'  => $removed,
            'items_retained' => $retained,
            'messages'       => $retained ? [__('Plan Access was kept as a record of purchase.', 'optimum-lift-plans')] : [],
            'done'           => true,
        ];
    }

    public function deleteUser(int $userId): void
    {
        $this->logs->deleteForUser($userId);
        $this->access->deleteForUser($userId);
    }
}
