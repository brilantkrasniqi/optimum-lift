<?php

/**
 * My Account › Plans: the Customer's Plans, one Plan, and the Workout screen
 * where training is logged.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Portal;

use OptimumLift\Plans\Access\AccessRepository;
use OptimumLift\Plans\Logging\RestController;
use OptimumLift\Plans\Logging\WorkoutLog;
use OptimumLift\Plans\Logging\WorkoutLogRepository;
use OptimumLift\Plans\Plan\Plan;
use OptimumLift\Plans\Plan\PlanRepository;
use OptimumLift\Plans\Plan\Workout;

final class Portal
{
    private const START_ACTION = 'ol_start_workout';

    public function __construct(
        private PlanRepository $plans,
        private AccessRepository $access,
        private WorkoutLogRepository $logs,
    ) {
    }

    public function register(): void
    {
        add_filter('woocommerce_get_query_vars', [$this, 'queryVars']);
        add_filter('woocommerce_account_menu_items', [$this, 'menuItems']);
        add_filter('woocommerce_endpoint_' . Endpoint::NAME . '_title', [$this, 'title']);
        add_action('woocommerce_account_' . Endpoint::NAME . '_endpoint', [$this, 'render']);
        add_action('template_redirect', [$this, 'guard'], 5);
        add_action('template_redirect', [$this, 'handleStart'], 6);
        add_action('wp_enqueue_scripts', [$this, 'assets']);
    }

    /**
     * @param array<string, string> $vars
     * @return array<string, string>
     */
    public function queryVars(array $vars): array
    {
        $vars[Endpoint::NAME] = Endpoint::NAME;

        return $vars;
    }

    /**
     * @param array<string, string> $items
     * @return array<string, string>
     */
    public function menuItems(array $items): array
    {
        $plans = [Endpoint::NAME => __('Plans', 'optimum-lift-plans')];

        // Straight after Dashboard: it is why most Customers have an account.
        $position = array_search('dashboard', array_keys($items), true);
        $offset   = $position === false ? 0 : $position + 1;

        return array_slice($items, 0, $offset, true) + $plans + array_slice($items, $offset, null, true);
    }

    public function title(string $title): string
    {
        $route = Endpoint::current();
        $plan  = $route !== null && $route['plan'] > 0 ? $this->plans->find($route['plan']) : null;

        return $plan === null ? __('Plans', 'optimum-lift-plans') : $plan->title;
    }

    /**
     * Unknown Plans, Plans without Access and unknown Workouts are a real 404,
     * decided before any output.
     */
    public function guard(): void
    {
        $route = Endpoint::current();

        if ($route === null || !is_user_logged_in() || ($route['plan'] === 0 && $route['action'] === '')) {
            return;
        }

        $plan  = $this->followablePlan($route['plan']);
        $valid = $plan !== null && match ($route['action']) {
            ''         => true,
            'download' => true,
            'workout'  => $plan->workout($route['arg']) !== null,
            default    => false,
        };

        if (!$valid) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            // Otherwise redirect_canonical() "guesses" /my-account/ and 301s.
            remove_action('template_redirect', 'redirect_canonical');
        }
    }

    public function handleStart(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST[self::START_ACTION])) {
            return;
        }

        $route = Endpoint::current();

        if ($route === null || $route['action'] !== 'workout' || !is_user_logged_in()) {
            return;
        }

        check_admin_referer(self::START_ACTION . '_' . $route['arg']);

        $plan    = $this->followablePlan($route['plan']);
        $workout = $plan?->workout($route['arg']);

        if ($plan !== null && $workout !== null) {
            $this->logs->start(get_current_user_id(), $plan, $workout);
        }

        wp_safe_redirect(Endpoint::workoutUrl($route['plan'], $route['arg']));
        exit;
    }

    public function assets(): void
    {
        $route = Endpoint::current();

        if ($route === null) {
            return;
        }

        wp_enqueue_style('ol-portal', plugins_url('assets/portal.css', \OptimumLift\Plans\FILE), [], $this->assetVersion('assets/portal.css'));

        if ($route['action'] !== 'workout') {
            return;
        }

        wp_enqueue_script('ol-portal', plugins_url('assets/portal.js', \OptimumLift\Plans\FILE), [], $this->assetVersion('assets/portal.js'), ['in_footer' => true, 'strategy' => 'defer']);
        wp_localize_script('ol-portal', 'olPortal', [
            'root'  => esc_url_raw(rest_url(RestController::NAMESPACE . '/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'i18n'  => [
                'saving'         => __('Saving…', 'optimum-lift-plans'),
                'saved'          => __('Saved', 'optimum-lift-plans'),
                'queued'         => __('Offline: will save when you reconnect', 'optimum-lift-plans'),
                'failed'         => __('Not saved', 'optimum-lift-plans'),
                'personalRecord' => __('New Personal Record!', 'optimum-lift-plans'),
                'unsaved'        => __('Some sets have not been saved yet. Reconnect, then finish the Workout.', 'optimum-lift-plans'),
            ],
        ]);
    }

    public function render(string $value): void
    {
        $route  = Endpoint::parse($value);
        $userId = get_current_user_id();

        if ($route['plan'] === 0) {
            $this->renderList($userId);

            return;
        }

        $plan = $this->followablePlan($route['plan']);

        if ($plan === null) {
            return; // guard() already turned this into a 404.
        }

        $workout = $route['action'] === 'workout' ? $plan->workout($route['arg']) : null;

        if ($workout !== null) {
            $this->renderWorkout($userId, $plan, $workout);

            return;
        }

        Templates::render('portal/plan', [
            'plan'     => $plan,
            'statuses' => $this->logs->statuses($userId, $plan->id),
        ]);
    }

    private function renderList(int $userId): void
    {
        $entries = [];

        foreach ($this->access->planIds($userId) as $planId) {
            $plan = $this->plans->find($planId);

            if ($plan === null || $plan->status !== 'publish') {
                continue;
            }

            $statuses  = $this->logs->statuses($userId, $plan->id);
            $entries[] = [
                'plan'      => $plan,
                'completed' => count(array_filter($statuses, static fn (string $s): bool => $s === WorkoutLog::COMPLETED)),
                'next'      => self::nextWorkout($plan, $statuses),
            ];
        }

        Templates::render('portal/plans', ['entries' => $entries]);
    }

    private function renderWorkout(int $userId, Plan $plan, Workout $workout): void
    {
        $log      = $this->logs->current($userId, $workout->uid);
        $previous = [];
        $records  = [];

        foreach ($workout->prescriptions as $prescription) {
            $exerciseId = $prescription->exercise->id;

            if (isset($records[$exerciseId])) {
                continue;
            }

            $records[$exerciseId]  = $this->logs->personalRecord($userId, $exerciseId);
            $previous[$exerciseId] = $this->logs->history($userId, $exerciseId, 1, $log === null ? 0 : $log->id)[0]['sets'] ?? [];
        }

        Templates::render('portal/workout', [
            'plan'        => $plan,
            'workout'     => $workout,
            'log'         => $log,
            'sets'        => $log === null ? [] : $this->logs->sets($log->id),
            'previous'    => $previous,
            'records'     => $records,
            'startAction' => self::START_ACTION,
        ]);
    }

    /**
     * The first Workout, in Plan order, that has not been completed.
     *
     * @param array<string, string> $statuses
     */
    public static function nextWorkout(Plan $plan, array $statuses): ?Workout
    {
        foreach ($plan->weeks as $week) {
            foreach ($week->workouts as $workout) {
                if (($statuses[$workout->uid] ?? '') !== WorkoutLog::COMPLETED) {
                    return $workout;
                }
            }
        }

        return null;
    }

    private function followablePlan(int $planId): ?Plan
    {
        $plan   = $planId > 0 ? $this->plans->find($planId) : null;
        $userId = get_current_user_id();

        if ($plan === null || !$this->access->canFollow($userId, $plan->id)) {
            return null;
        }

        // Customers see published Plans only; authors may preview drafts.
        return $plan->status === 'publish' || current_user_can('edit_post', $plan->id) ? $plan : null;
    }

    private function assetVersion(string $relative): string
    {
        $path = \OptimumLift\Plans\DIR . '/' . $relative;

        return wp_get_environment_type() === 'local' && is_file($path) ? (string) filemtime($path) : \OptimumLift\Plans\VERSION;
    }
}
