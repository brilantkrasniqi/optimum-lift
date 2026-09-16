<?php

/**
 * ol/v1: logging from the Workout screen. Cookie auth with the wp_rest nonce.
 * Every route checks that the log belongs to the current user and that they can
 * still follow its Plan.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Logging;

use OptimumLift\Plans\Access\AccessRepository;
use OptimumLift\Plans\Format;
use OptimumLift\Plans\Plan\Plan;
use OptimumLift\Plans\Plan\PlanRepository;
use OptimumLift\Plans\Plan\Prescription;
use OptimumLift\Plans\Plan\Workout;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class RestController
{
    public const NAMESPACE = 'ol/v1';

    public function __construct(
        private PlanRepository $plans,
        private AccessRepository $access,
        private WorkoutLogRepository $logs,
    ) {
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(): void
    {
        $loggedIn = static fn (): bool => is_user_logged_in();
        $logId    = ['type' => 'integer', 'required' => true, 'minimum' => 1];

        register_rest_route(self::NAMESPACE, '/workout-logs', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'start'],
            'permission_callback' => $loggedIn,
            'args'                => [
                'plan_id'     => ['type' => 'integer', 'required' => true, 'minimum' => 1],
                'workout_uid' => ['type' => 'string', 'required' => true, 'format' => 'uuid'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/workout-logs/(?P<id>\d+)/sets', [
            [
                'methods'             => 'PUT',
                'callback'            => [$this, 'saveSet'],
                'permission_callback' => $loggedIn,
                'args'                => [
                    'id'               => $logId,
                    'prescription_uid' => ['type' => 'string', 'required' => true, 'format' => 'uuid'],
                    'set_number'       => ['type' => 'integer', 'required' => true, 'minimum' => 1],
                    'load_kg'          => ['type' => ['number', 'null'], 'minimum' => 0, 'maximum' => 9999.99],
                    'reps'             => ['type' => ['integer', 'null'], 'minimum' => 0, 'maximum' => 999],
                    'seconds'          => ['type' => ['integer', 'null'], 'minimum' => 0, 'maximum' => 36000],
                ],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'deleteSet'],
                'permission_callback' => $loggedIn,
                'args'                => [
                    'id'               => $logId,
                    'prescription_uid' => ['type' => 'string', 'required' => true, 'format' => 'uuid'],
                    'set_number'       => ['type' => 'integer', 'required' => true, 'minimum' => 1],
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/workout-logs/(?P<id>\d+)/complete', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'complete'],
            'permission_callback' => $loggedIn,
            'args'                => [
                'id'    => $logId,
                'notes' => ['type' => 'string', 'default' => '', 'maxLength' => 2000],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/exercises/(?P<id>\d+)/history', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'history'],
            'permission_callback' => $loggedIn,
            'args'                => ['id' => $logId],
        ]);
    }

    public function start(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $userId = get_current_user_id();
        $plan   = $this->plans->find((int) $request['plan_id']);

        if ($plan === null || !$this->access->canFollow($userId, $plan->id)) {
            return $this->notFound();
        }

        $workout = $plan->workout((string) $request['workout_uid']);

        if ($workout === null) {
            return $this->notFound();
        }

        return new WP_REST_Response($this->logResponse($this->logs->start($userId, $plan, $workout)), 201);
    }

    public function saveSet(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $context = $this->context($request);

        if ($context instanceof WP_Error) {
            return $context;
        }

        [$log] = $context;
        $prescription = $context[2]->prescription((string) $request['prescription_uid']);
        $setNumber    = (int) $request['set_number'];

        if ($prescription === null) {
            return $this->notFound();
        }

        if ($setNumber > $prescription->sets) {
            return new WP_Error('ol_invalid_set', __('That set is not part of this Prescription.', 'optimum-lift-plans'), ['status' => 422]);
        }

        [$loadKg, $reps, $seconds] = $this->performance($request, $prescription);

        $personalRecord = $this->logs->saveSet($log, $prescription, $setNumber, $loadKg, $reps, $seconds);

        return new WP_REST_Response([
            'set_number'      => $setNumber,
            'load_kg'         => $loadKg,
            'reps'            => $reps,
            'seconds'         => $seconds,
            'personal_record' => $personalRecord,
        ]);
    }

    public function deleteSet(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $context = $this->context($request);

        if ($context instanceof WP_Error) {
            return $context;
        }

        $this->logs->deleteSet($context[0], (string) $request['prescription_uid'], (int) $request['set_number']);

        return new WP_REST_Response(null, 204);
    }

    public function complete(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $context = $this->context($request);

        if ($context instanceof WP_Error) {
            return $context;
        }

        $this->logs->complete($context[0], sanitize_textarea_field((string) $request['notes']));

        $log = $this->logs->find($context[0]->id);

        return new WP_REST_Response($log === null ? null : $this->logResponse($log));
    }

    public function history(WP_REST_Request $request): WP_REST_Response
    {
        $userId     = get_current_user_id();
        $exerciseId = (int) $request['id'];

        // Only the Customer's own sets are read, so no Access check is needed.
        $history = array_map(static fn (array $entry): array => [
            'workout_log_id' => $entry['log_id'],
            'performed_at'   => Format::utc($entry['performed_at']),
            'sets'           => array_map(static fn (LoggedSet $set): array => $set->toArray(), $entry['sets']),
        ], $this->logs->history($userId, $exerciseId));

        return new WP_REST_Response([
            'personal_record_kg' => $this->logs->personalRecord($userId, $exerciseId),
            'history'            => $history,
        ]);
    }

    /**
     * The log in the URL, with its Plan and Workout, if the current user owns
     * it and can still follow the Plan. Otherwise a 404, so log IDs cannot be
     * probed.
     *
     * @return array{WorkoutLog, Plan, Workout}|WP_Error
     */
    private function context(WP_REST_Request $request): array|WP_Error
    {
        $log = $this->logs->find((int) $request['id']);

        if ($log === null || $log->userId !== get_current_user_id() || !$this->access->canFollow($log->userId, $log->planId)) {
            return $this->notFound();
        }

        $plan    = $this->plans->find($log->planId);
        $workout = $plan?->workout($log->workoutUid);

        if ($plan === null || $workout === null) {
            return $this->notFound();
        }

        return [$log, $plan, $workout];
    }

    /**
     * Reps or seconds, depending on the Prescription's target type. A zero or
     * missing load means bodyweight.
     *
     * @return array{?float, ?int, ?int}
     */
    private function performance(WP_REST_Request $request, Prescription $prescription): array
    {
        $load   = $request['load_kg'];
        $loadKg = is_numeric($load) && (float) $load > 0 ? round((float) $load, 2) : null;

        if ($prescription->isTimed()) {
            return [$loadKg, null, is_numeric($request['seconds']) ? (int) $request['seconds'] : null];
        }

        return [$loadKg, is_numeric($request['reps']) ? (int) $request['reps'] : null, null];
    }

    /**
     * @return array<string, mixed>
     */
    private function logResponse(WorkoutLog $log): array
    {
        return [
            'id'           => $log->id,
            'plan_id'      => $log->planId,
            'workout_uid'  => $log->workoutUid,
            'status'       => $log->status,
            'notes'        => $log->notes,
            'started_at'   => Format::utc($log->startedAt),
            'completed_at' => $log->completedAt === null ? null : Format::utc($log->completedAt),
        ];
    }

    private function notFound(): WP_Error
    {
        return new WP_Error('ol_not_found', __('Not found.', 'optimum-lift-plans'), ['status' => 404]);
    }
}
