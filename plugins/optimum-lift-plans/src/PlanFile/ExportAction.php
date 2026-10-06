<?php

/**
 * Export as JSON in wp-admin: a row action on the Training Plans list and a
 * "Plan file" box on the edit screen, both downloading the same file as
 * `wp ol-plans export-plan`. Production has no WP-CLI, so this is how a Plan
 * leaves a site there.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

use OptimumLift\Plans\Content\PostTypes;
use WP_Post;

final class ExportAction
{
    private const ACTION = 'ol_export_plan';

    public function __construct(private readonly PlanFileExporter $exporter)
    {
    }

    public function register(): void
    {
        add_filter('post_row_actions', [$this, 'rowAction'], 10, 2);
        add_action('add_meta_boxes_' . PostTypes::PLAN, [$this, 'addMetaBox']);
        add_action('admin_post_' . self::ACTION, [$this, 'download']);
        add_action('admin_notices', [$this, 'notices']);
    }

    /**
     * @param array<string, string> $actions
     * @return array<string, string>
     */
    public function rowAction(array $actions, WP_Post $post): array
    {
        if ($post->post_type !== PostTypes::PLAN || $post->post_status === 'trash' || !current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        $actions[self::ACTION] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($this->url($post->ID)),
            esc_html__('Export as JSON', 'optimum-lift-plans')
        );

        return $actions;
    }

    public function addMetaBox(WP_Post $post): void
    {
        // A Plan never saved has nothing to export.
        if ($post->post_status === 'auto-draft') {
            return;
        }

        add_meta_box('ol-plan-file', __('Plan file', 'optimum-lift-plans'), [$this, 'renderMetaBox'], PostTypes::PLAN, 'side', 'low');
    }

    public function renderMetaBox(WP_Post $post): void
    {
        $result = $this->exporter->export($post->ID);

        if ($result->problems !== []) {
            printf('<p>%s</p>', esc_html__('This Plan cannot be exported until these are fixed:', 'optimum-lift-plans'));
            $this->printList($result->problems);

            return;
        }

        printf(
            '<p><a class="button" href="%s">%s</a></p><p class="description">%s</p>',
            esc_url($this->url($post->ID)),
            esc_html__('Export as JSON', 'optimum-lift-plans'),
            esc_html__('Exports the last saved version.', 'optimum-lift-plans')
        );

        if ($result->warnings !== []) {
            $this->printList($result->warnings);
        }
    }

    public function download(): void
    {
        // phpcs:disable -- the nonce is checked here; the values are cast or only compared.
        $planId = (int) ($_GET['plan'] ?? 0);
        $nonce  = (string) ($_GET['_wpnonce'] ?? '');
        // phpcs:enable

        if (wp_verify_nonce($nonce, self::ACTION . '_' . $planId) === false) {
            wp_die(
                esc_html__('This link has expired. Go back, reload the page and try again.', 'optimum-lift-plans'),
                '',
                ['response' => 403, 'back_link' => true]
            );
        }

        if (!current_user_can('edit_post', $planId)) {
            wp_die(esc_html__('You are not allowed to export this Plan.', 'optimum-lift-plans'), '', ['response' => 403]);
        }

        $result = $this->exporter->export($planId);

        if ($result->problems !== []) {
            set_transient($this->noticeKey(), $result->problems, 5 * MINUTE_IN_SECONDS);
            wp_safe_redirect(wp_get_referer() ?: admin_url('edit.php?post_type=' . PostTypes::PLAN));
            exit;
        }

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header(sprintf('Content-Disposition: attachment; filename="%s"', $this->exporter->fileName($planId)));
        header('Content-Length: ' . strlen($result->json));
        header('X-Content-Type-Options: nosniff');

        echo $result->json; // phpcs:ignore -- a JSON download, not HTML.
        exit;
    }

    public function notices(): void
    {
        $screen = get_current_screen();

        if ($screen === null || $screen->post_type !== PostTypes::PLAN) {
            return;
        }

        $problems = get_transient($this->noticeKey());

        if (!is_array($problems)) {
            return;
        }

        delete_transient($this->noticeKey());

        echo '<div class="notice notice-error is-dismissible">';
        printf('<p>%s</p>', esc_html__('The Plan was not exported:', 'optimum-lift-plans'));
        $this->printList(array_values(array_filter($problems, 'is_string')));
        echo '</div>';
    }

    private function url(int $planId): string
    {
        return wp_nonce_url(
            add_query_arg(['action' => self::ACTION, 'plan' => $planId], admin_url('admin-post.php')),
            self::ACTION . '_' . $planId
        );
    }

    /**
     * @param list<string> $items
     */
    private function printList(array $items): void
    {
        echo '<ul style="list-style: disc; padding-left: 1.5em;">';

        foreach ($items as $item) {
            printf('<li>%s</li>', esc_html($item));
        }

        echo '</ul>';
    }

    private function noticeKey(): string
    {
        return 'ol_plan_export_problems_' . get_current_user_id();
    }
}
