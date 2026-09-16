<?php

/**
 * Gets the Download to the Customer: the permission-checked file endpoint, My
 * Account › Downloads, the order-received page and the order emails. Also the
 * author's "Preview PDF" link.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Download;

use OptimumLift\Plans\Access\AccessRepository;
use OptimumLift\Plans\Content\PostTypes;
use OptimumLift\Plans\Plan\Plan;
use OptimumLift\Plans\Plan\PlanRepository;
use OptimumLift\Plans\Portal\Endpoint;
use Throwable;
use WC_Email;
use WC_Order;
use WP_Post;

final class Delivery
{
    private const PREVIEW_ACTION = 'ol_plan_pdf_preview';
    private const WARM_EVENT     = 'ol_plans_warm_pdf';

    public function __construct(
        private PlanRepository $plans,
        private AccessRepository $access,
        private PdfRenderer $renderer,
    ) {
    }

    public function register(): void
    {
        add_action('template_redirect', [$this, 'serveCustomerDownload']);
        add_action('admin_post_' . self::PREVIEW_ACTION, [$this, 'servePreview']);
        add_action('add_meta_boxes_' . PostTypes::PLAN, [$this, 'addPreviewBox']);
        add_filter('woocommerce_customer_get_downloadable_products', [$this, 'addToAccountDownloads']);
        add_action('woocommerce_order_details_after_order_table', [$this, 'renderOrderPlans']);
        add_action('woocommerce_email_after_order_table', [$this, 'renderEmailPlans'], 10, 4);
        add_action('acf/save_post', [$this, 'scheduleWarm'], 20);
        add_action(self::WARM_EVENT, [$this, 'warm']);
    }

    /**
     * Rendering takes seconds, so do it in the background after an author
     * saves, not on the first Customer's download.
     */
    public function scheduleWarm(int|string $postId): void
    {
        if (!is_numeric($postId) || get_post_type((int) $postId) !== PostTypes::PLAN || !PdfRenderer::available()) {
            return;
        }

        wp_clear_scheduled_hook(self::WARM_EVENT, [(int) $postId]);
        wp_schedule_single_event(time() + 10, self::WARM_EVENT, [(int) $postId]);
    }

    public function warm(int $planId): void
    {
        $plan = $this->plans->find($planId);

        if ($plan !== null && $plan->status === 'publish') {
            $this->renderer->file($plan);
        }
    }

    public function serveCustomerDownload(): void
    {
        $route = Endpoint::current();

        if ($route === null || $route['action'] !== 'download') {
            return;
        }

        // Logged out: My Account renders its login form at this same URL, and
        // WooCommerce sends the visitor back here (the referer) after login.
        if (!is_user_logged_in()) {
            return;
        }

        $plan = $this->plans->find($route['plan']);

        if ($plan === null || !$this->access->canFollow(get_current_user_id(), $plan->id)) {
            wp_die(esc_html__('You do not have Access to this Plan.', 'optimum-lift-plans'), '', ['response' => 403]);
        }

        $this->stream($plan);
    }

    public function servePreview(): void
    {
        $planId = (int) ($_GET['plan'] ?? 0); // phpcs:ignore -- nonce checked below.

        check_admin_referer(self::PREVIEW_ACTION . '_' . $planId);

        $plan = $this->plans->find($planId);

        if ($plan === null || !current_user_can('edit_post', $planId)) {
            wp_die(esc_html__('You cannot preview this Plan.', 'optimum-lift-plans'), '', ['response' => 403]);
        }

        // ?html=1 shows the template before Dompdf, for layout debugging.
        if (!empty($_GET['html'])) { // phpcs:ignore
            echo $this->renderer->html($plan); // phpcs:ignore -- template escapes its output.
            exit;
        }

        $this->stream($plan, 'inline');
    }

    public function addPreviewBox(WP_Post $post): void
    {
        add_meta_box('ol-plan-download', __('Download', 'optimum-lift-plans'), function () use ($post): void {
            if (!PdfRenderer::available()) {
                echo '<p>' . esc_html__('Dompdf is not installed. Run `npm run composer -- install`.', 'optimum-lift-plans') . '</p>';

                return;
            }

            printf(
                '<p><a class="button" target="_blank" href="%s">%s</a></p><p class="description">%s</p>',
                esc_url(self::previewUrl($post->ID)),
                esc_html__('Preview PDF', 'optimum-lift-plans'),
                esc_html__('Shows the last saved version.', 'optimum-lift-plans')
            );
        }, PostTypes::PLAN, 'side', 'low');
    }

    public static function previewUrl(int $planId): string
    {
        return wp_nonce_url(
            add_query_arg(['action' => self::PREVIEW_ACTION, 'plan' => $planId], admin_url('admin-post.php')),
            self::PREVIEW_ACTION . '_' . $planId
        );
    }

    /**
     * @param list<array<string, mixed>> $downloads
     * @return list<array<string, mixed>>
     */
    public function addToAccountDownloads(array $downloads): array
    {
        foreach ($this->access->planIds(get_current_user_id()) as $planId) {
            $plan = $this->plans->find($planId);

            if ($plan === null || $plan->status !== 'publish') {
                continue;
            }

            $downloads[] = [
                'download_url'        => Endpoint::url($plan->id, 'download'),
                'download_id'         => 'ol-plan-' . $plan->id,
                'product_id'          => 0,
                'product_name'        => $plan->title,
                'product_url'         => Endpoint::url($plan->id),
                'download_name'       => __('Plan PDF', 'optimum-lift-plans'),
                'order_id'            => 0,
                'order_key'           => '',
                'downloads_remaining' => '',
                'access_expires'      => null,
                'file'                => ['name' => PdfRenderer::filename($plan), 'file' => ''],
            ];
        }

        return $downloads;
    }

    public function renderOrderPlans(WC_Order $order): void
    {
        $plans = $this->grantedPlans($order);

        if ($plans === []) {
            return;
        }

        echo '<section class="ol-order-plans"><h2>' . esc_html__('Your Plans', 'optimum-lift-plans') . '</h2><ul>';

        foreach ($plans as $plan) {
            printf(
                '<li><strong>%1$s</strong> &mdash; <a href="%2$s">%3$s</a> &middot; <a href="%4$s">%5$s</a></li>',
                esc_html($plan->title),
                esc_url(Endpoint::url($plan->id)),
                esc_html__('Open', 'optimum-lift-plans'),
                esc_url(Endpoint::url($plan->id, 'download')),
                esc_html__('Download PDF', 'optimum-lift-plans')
            );
        }

        echo '</ul>';

        if (!is_user_logged_in()) {
            printf(
                '<p>%s</p>',
                esc_html(sprintf(
                    /* translators: %s: billing email */
                    __('Sign in with %s to open your Plans. If this is your first order, we have emailed you a link to set your password.', 'optimum-lift-plans'),
                    $order->get_billing_email()
                ))
            );
        }

        echo '</section>';
    }

    public function renderEmailPlans(WC_Order $order, bool $sentToAdmin, bool $plainText, ?WC_Email $email = null): void
    {
        if ($sentToAdmin || !$order->has_status(['processing', 'completed'])) {
            return;
        }

        $plans = $this->grantedPlans($order);

        if ($plans === []) {
            return;
        }

        if ($plainText) {
            echo "\n" . esc_html(mb_strtoupper(__('Your Plans', 'optimum-lift-plans'))) . "\n\n";

            foreach ($plans as $plan) {
                echo esc_html($plan->title) . "\n" . esc_url_raw(Endpoint::url($plan->id)) . "\n\n";
            }

            return;
        }

        echo '<h2>' . esc_html__('Your Plans', 'optimum-lift-plans') . '</h2><ul>';

        foreach ($plans as $plan) {
            printf(
                '<li><a href="%1$s">%2$s</a> (<a href="%3$s">%4$s</a>)</li>',
                esc_url(Endpoint::url($plan->id)),
                esc_html($plan->title),
                esc_url(Endpoint::url($plan->id, 'download')),
                esc_html__('PDF', 'optimum-lift-plans')
            );
        }

        echo '</ul>';
    }

    /**
     * @return list<Plan>
     */
    private function grantedPlans(WC_Order $order): array
    {
        $plans = [];

        foreach ($this->access->forOrder($order->get_id()) as $row) {
            $plan = $row['revoked'] ? null : $this->plans->find($row['plan_id']);

            if ($plan !== null) {
                $plans[$plan->id] = $plan;
            }
        }

        return array_values($plans);
    }

    private function stream(Plan $plan, string $disposition = 'attachment'): never
    {
        try {
            $path = $this->renderer->file($plan);
        } catch (Throwable $e) {
            error_log('[optimum-lift-plans] PDF render failed for Plan ' . $plan->id . ': ' . $e->getMessage()); // phpcs:ignore
            wp_die(esc_html__('The PDF could not be created. Please try again shortly.', 'optimum-lift-plans'), '', ['response' => 500]);
        }

        nocache_headers();
        header('Content-Type: application/pdf');
        header(sprintf('Content-Disposition: %s; filename="%s"', $disposition, PdfRenderer::filename($plan)));
        header('Content-Length: ' . (string) filesize($path));
        header('X-Robots-Tag: noindex');

        readfile($path);
        exit;
    }
}
