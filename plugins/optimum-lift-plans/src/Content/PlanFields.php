<?php

/**
 * The Training Plan builder: Phases, then Weeks > Workouts > Prescriptions.
 *
 * Two safeguards live here, both from ADR-0003:
 * - Workouts and Prescriptions carry a stable `uid`, because Workout Logs point
 *   at them and repeater rows only have a position.
 * - A sentinel input is printed after every meta box. If PHP's max_input_vars
 *   truncated the POST, the sentinel is missing and the ACF save is refused;
 *   a truncated repeater save would otherwise delete the rows it never saw.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Content;

use WP_Post;

final class PlanFields
{
    public const WEEKS              = 'field_ol_plan_weeks';
    public const PHASES             = 'field_ol_plan_phases';
    public const WORKOUT_UID        = 'field_ol_workout_uid';
    public const WORKOUT_NAME       = 'field_ol_workout_name';
    public const PRESCRIPTION_UID   = 'field_ol_prescription_uid';

    private const PHASE_NAME        = 'field_ol_phase_name';
    private const PHASE_FIRST       = 'field_ol_phase_first_week';
    private const PHASE_LAST        = 'field_ol_phase_last_week';
    private const WEEK_HEADING      = 'field_ol_week_heading';
    private const SENTINEL          = 'ol_plan_form_complete';

    /** @var array<string, true> uids already assigned during the current Plan save */
    private array $seenUids = [];

    public function register(): void
    {
        add_action('acf/include_fields', [$this, 'registerFields']);

        add_filter('acf/pre_update_value', [$this, 'startUidScope'], 10, 4);
        // Before the repeater's own save (priority 10) writes the rows.
        add_filter('acf/update_value/type=repeater', [$this, 'ensureUidInRows'], 5, 3);
        add_filter('acf/update_value/key=' . self::WORKOUT_UID, [$this, 'assignUid']);
        add_filter('acf/update_value/key=' . self::PRESCRIPTION_UID, [$this, 'assignUid']);

        add_action('dbx_post_sidebar', [$this, 'printSentinel']);
        add_filter('acf/form-post/skip_save', [$this, 'refuseTruncatedSave'], 10, 3);
        add_action('acf/validate_save_post', [$this, 'validate']);
        add_action('admin_notices', [$this, 'notices']);

        add_action('acf/input/admin_head', [$this, 'adminStyles']);
        add_action('acf/input/admin_footer', [$this, 'adminScript']);
    }

    public function registerFields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_ol_training_plan',
            'title'    => __('Training Plan', 'optimum-lift-plans'),
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => PostTypes::PLAN]]],
            'position' => 'acf_after_title',
            'style'    => 'seamless',
            'fields'   => [
                $this->tab('overview', __('Overview', 'optimum-lift-plans')),
                [
                    'key'          => 'field_ol_plan_summary',
                    'name'         => 'summary',
                    'label'        => __('Summary', 'optimum-lift-plans'),
                    'instructions' => __('Shown on the PDF cover and at the top of the Plan in the Portal.', 'optimum-lift-plans'),
                    'type'         => 'textarea',
                    'rows'         => 4,
                    'new_lines'    => '',
                ],
                [
                    'key'     => 'field_ol_plan_goal',
                    'name'    => 'goal',
                    'label'   => __('Goal', 'optimum-lift-plans'),
                    'type'    => 'text',
                    'wrapper' => ['width' => '33'],
                ],
                [
                    'key'     => 'field_ol_plan_target_audience',
                    'name'    => 'target_audience',
                    'label'   => __('Target audience', 'optimum-lift-plans'),
                    'type'    => 'text',
                    'wrapper' => ['width' => '33'],
                ],
                [
                    'key'           => 'field_ol_plan_difficulty',
                    'name'          => 'difficulty',
                    'label'         => __('Difficulty', 'optimum-lift-plans'),
                    'type'          => 'select',
                    'choices'       => Choices::difficulty(),
                    'allow_null'    => 1,
                    'return_format' => 'value',
                    'wrapper'       => ['width' => '34'],
                ],

                $this->tab('phases', __('Phases', 'optimum-lift-plans')),
                [
                    'key'          => self::PHASES,
                    'name'         => 'phases',
                    'label'        => __('Phases', 'optimum-lift-plans'),
                    'instructions' => __('Optional. A name over consecutive Weeks, e.g. "Strength", Weeks 9 to 12. Phases may not overlap.', 'optimum-lift-plans'),
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => __('Add Phase', 'optimum-lift-plans'),
                    'sub_fields'   => [
                        [
                            'key'      => self::PHASE_NAME,
                            'name'     => 'name',
                            'label'    => __('Name', 'optimum-lift-plans'),
                            'type'     => 'text',
                            'required' => 1,
                            'wrapper'  => ['width' => '60'],
                        ],
                        [
                            'key'      => self::PHASE_FIRST,
                            'name'     => 'first_week',
                            'label'    => __('First Week', 'optimum-lift-plans'),
                            'type'     => 'number',
                            'min'      => 1,
                            'step'     => 1,
                            'required' => 1,
                            'wrapper'  => ['width' => '20'],
                        ],
                        [
                            'key'      => self::PHASE_LAST,
                            'name'     => 'last_week',
                            'label'    => __('Last Week', 'optimum-lift-plans'),
                            'type'     => 'number',
                            'min'      => 1,
                            'step'     => 1,
                            'required' => 1,
                            'wrapper'  => ['width' => '20'],
                        ],
                    ],
                ],

                $this->tab('weeks', __('Weeks', 'optimum-lift-plans')),
                [
                    'key'          => self::WEEKS,
                    'name'         => 'weeks',
                    'label'        => __('Weeks', 'optimum-lift-plans'),
                    'instructions' => __('A Week is numbered by its position. To write the next Week, hold Shift and click the duplicate icon beside the last Week, then edit the copy.', 'optimum-lift-plans'),
                    'type'         => 'repeater',
                    'layout'       => 'block',
                    'collapsed'    => self::WEEK_HEADING,
                    'min'          => 1,
                    'button_label' => __('Add Week', 'optimum-lift-plans'),
                    'sub_fields'   => [
                        [
                            // Stores nothing. It is the visible label of a
                            // collapsed Week; admin JS writes "Week 3 · …" into it.
                            'key'     => self::WEEK_HEADING,
                            'name'    => 'heading',
                            'label'   => __('Week', 'optimum-lift-plans'),
                            'type'    => 'message',
                            'message' => '',
                        ],
                        [
                            'key'          => 'field_ol_week_workouts',
                            'name'         => 'workouts',
                            'label'        => __('Workouts', 'optimum-lift-plans'),
                            'type'         => 'repeater',
                            'layout'       => 'block',
                            'collapsed'    => self::WORKOUT_NAME,
                            'min'          => 1,
                            'button_label' => __('Add Workout', 'optimum-lift-plans'),
                            'sub_fields'   => [
                                $this->uidField(self::WORKOUT_UID),
                                [
                                    'key'          => self::WORKOUT_NAME,
                                    'name'         => 'name',
                                    'label'        => __('Workout name', 'optimum-lift-plans'),
                                    'instructions' => __('e.g. "Upper body". Not a weekday.', 'optimum-lift-plans'),
                                    'type'         => 'text',
                                    'required'     => 1,
                                ],
                                [
                                    'key'          => 'field_ol_workout_prescriptions',
                                    'name'         => 'prescriptions',
                                    'label'        => __('Prescriptions', 'optimum-lift-plans'),
                                    'type'         => 'repeater',
                                    'layout'       => 'table',
                                    'min'          => 1,
                                    'button_label' => __('Add Prescription', 'optimum-lift-plans'),
                                    'sub_fields'   => $this->prescriptionFields(),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function prescriptionFields(): array
    {
        return [
            $this->uidField(self::PRESCRIPTION_UID),
            [
                'key'           => 'field_ol_prescription_exercise',
                'name'          => 'exercise',
                'label'         => __('Exercise', 'optimum-lift-plans'),
                'type'          => 'post_object',
                'post_type'     => [PostTypes::EXERCISE],
                'post_status'   => ['publish'],
                'return_format' => 'id',
                'ui'            => 1,
                'required'      => 1,
                'wrapper'       => ['width' => '22'],
            ],
            [
                'key'           => 'field_ol_prescription_sets',
                'name'          => 'sets',
                'label'         => __('Sets', 'optimum-lift-plans'),
                'type'          => 'number',
                'min'           => 1,
                'max'           => 20,
                'step'          => 1,
                'default_value' => 3,
                'required'      => 1,
                'wrapper'       => ['width' => '9'],
            ],
            [
                'key'           => 'field_ol_prescription_target_type',
                'name'          => 'target_type',
                'label'         => __('Target in', 'optimum-lift-plans'),
                'type'          => 'button_group',
                'choices'       => Choices::targetTypes(),
                'default_value' => 'reps',
                'return_format' => 'value',
                'wrapper'       => ['width' => '15'],
            ],
            [
                'key'          => 'field_ol_prescription_target',
                'name'         => 'target',
                'label'        => __('Target', 'optimum-lift-plans'),
                'instructions' => __('8, 8-10, AMRAP, 30', 'optimum-lift-plans'),
                'type'         => 'text',
                'required'     => 1,
                'maxlength'    => 20,
                'wrapper'      => ['width' => '11'],
            ],
            [
                'key'          => 'field_ol_prescription_intensity',
                'name'         => 'intensity',
                'label'        => __('Intensity', 'optimum-lift-plans'),
                'instructions' => __('e.g. RPE 8', 'optimum-lift-plans'),
                'type'         => 'text',
                'maxlength'    => 20,
                'wrapper'      => ['width' => '11'],
            ],
            [
                'key'     => 'field_ol_prescription_rest_seconds',
                'name'    => 'rest_seconds',
                'label'   => __('Rest (s)', 'optimum-lift-plans'),
                'type'    => 'number',
                'min'     => 0,
                'step'    => 15,
                'wrapper' => ['width' => '10'],
            ],
            [
                'key'       => 'field_ol_prescription_notes',
                'name'      => 'notes',
                'label'     => __('Notes', 'optimum-lift-plans'),
                'type'      => 'textarea',
                'rows'      => 2,
                'new_lines' => '',
                'wrapper'   => ['width' => '22'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tab(string $slug, string $label): array
    {
        return [
            'key'       => 'field_ol_plan_tab_' . $slug,
            'label'     => $label,
            'type'      => 'tab',
            'placement' => 'top',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function uidField(string $key): array
    {
        return [
            'key'      => $key,
            'name'     => 'uid',
            'label'    => 'uid',
            'type'     => 'text',
            'readonly' => 1,
            'wrapper'  => ['class' => 'ol-uid'],
        ];
    }

    /**
     * A Plan save starts when ACF writes the Weeks repeater, before any uid
     * inside it. Forget the uids seen by any earlier save in this request.
     *
     * @param array<string, mixed> $field
     */
    public function startUidScope(mixed $check, mixed $value, int|string $postId, array $field): mixed
    {
        if (($field['key'] ?? '') === self::WEEKS) {
            $this->seenUids = [];
        }

        return $check;
    }

    /**
     * ACF only saves the sub fields a row contains. The edit form always sends
     * the uid input, but update_field() rows written in code may leave it out,
     * and then no uid would ever be assigned.
     *
     * @param array<string, mixed> $field
     */
    public function ensureUidInRows(mixed $rows, int|string $postId, array $field): mixed
    {
        $uidKey = match ($field['key'] ?? '') {
            'field_ol_week_workouts'         => self::WORKOUT_UID,
            'field_ol_workout_prescriptions' => self::PRESCRIPTION_UID,
            default                          => null,
        };

        if ($uidKey === null || !is_array($rows)) {
            return $rows;
        }

        foreach ($rows as $index => $row) {
            if (is_array($row) && !array_key_exists($uidKey, $row) && !array_key_exists('uid', $row)) {
                $rows[$index][$uidKey] = '';
            }
        }

        return $rows;
    }

    /**
     * Keep a valid uid; replace an empty one, or one already used earlier in
     * this save (a row duplicated in the ACF UI copies its uid).
     */
    public function assignUid(mixed $value): string
    {
        $uid = is_string($value) ? strtolower(trim($value)) : '';

        if (!wp_is_uuid($uid) || isset($this->seenUids[$uid])) {
            $uid = wp_generate_uuid4();
        }

        $this->seenUids[$uid] = true;

        return $uid;
    }

    public function printSentinel(WP_Post $post): void
    {
        if ($post->post_type === PostTypes::PLAN) {
            printf('<input type="hidden" name="%s" value="1">', esc_attr(self::SENTINEL));
        }
    }

    public function refuseTruncatedSave(bool $skip, int $postId, WP_Post $post): bool
    {
        if ($skip || $post->post_type !== PostTypes::PLAN || $this->sentinelPresent()) {
            return $skip;
        }

        set_transient($this->noticeKey(), 1, MINUTE_IN_SECONDS);

        return true;
    }

    public function validate(): void
    {
        // phpcs:disable -- ACF verifies the nonce before this action runs.
        if (($_POST['post_type'] ?? '') !== PostTypes::PLAN && get_post_type((int) ($_POST['post_id'] ?? 0)) !== PostTypes::PLAN) {
            return;
        }

        if (!$this->sentinelPresent()) {
            acf_add_validation_error('', $this->truncationMessage());

            return;
        }

        $values = is_array($_POST['acf'] ?? null) ? wp_unslash($_POST['acf']) : [];
        // phpcs:enable

        $weekCount = count($this->rows($values[self::WEEKS] ?? []));
        $taken     = [];

        foreach ($this->rows($values[self::PHASES] ?? []) as $row => $phase) {
            $first = (int) ($phase[self::PHASE_FIRST] ?? 0);
            $last  = (int) ($phase[self::PHASE_LAST] ?? 0);
            $input = sprintf('acf[%s][%s][%s]', self::PHASES, $row, self::PHASE_LAST);

            if ($first < 1 || $last < $first) {
                acf_add_validation_error($input, __('A Phase must end on or after the Week it starts.', 'optimum-lift-plans'));
                continue;
            }

            if ($last > $weekCount) {
                /* translators: %d: number of Weeks in the Plan */
                acf_add_validation_error($input, sprintf(__('This Plan only has %d Weeks.', 'optimum-lift-plans'), $weekCount));
                continue;
            }

            for ($week = $first; $week <= $last; $week++) {
                if (isset($taken[$week])) {
                    /* translators: %d: Week number */
                    acf_add_validation_error($input, sprintf(__('Phases overlap at Week %d.', 'optimum-lift-plans'), $week));
                    continue 2;
                }

                $taken[$week] = true;
            }
        }
    }

    public function notices(): void
    {
        $screen = get_current_screen();

        if ($screen === null || $screen->post_type !== PostTypes::PLAN || $screen->base !== 'post') {
            return;
        }

        if (get_transient($this->noticeKey())) {
            delete_transient($this->noticeKey());
            printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($this->truncationMessage()));
        }

        $this->warnWhenNearInputLimit();
    }

    /**
     * Estimate the inputs the edit form will submit and warn before the limit
     * is reached, not after a save has been refused.
     */
    private function warnWhenNearInputLimit(): void
    {
        $postId = (int) ($_GET['post'] ?? 0); // phpcs:ignore -- read-only.
        $limit  = (int) ini_get('max_input_vars');

        if ($postId === 0 || $limit <= 0) {
            return;
        }

        $inputs = 60 + 4 * (int) get_post_meta($postId, 'phases', true);
        $weeks  = (int) get_post_meta($postId, 'weeks', true);

        for ($w = 0; $w < $weeks; $w++) {
            $workouts = (int) get_post_meta($postId, "weeks_{$w}_workouts", true);
            $inputs  += 2;

            for ($o = 0; $o < $workouts; $o++) {
                $inputs += 4 + 9 * (int) get_post_meta($postId, "weeks_{$w}_workouts_{$o}_prescriptions", true);
            }
        }

        if ($inputs < 0.8 * $limit) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html(sprintf(
                /* translators: 1: estimated form inputs, 2: max_input_vars */
                __('This Plan submits about %1$d form inputs and the server accepts %2$d (max_input_vars). Raise the limit before adding more Weeks.', 'optimum-lift-plans'),
                $inputs,
                $limit
            ))
        );
    }

    public function adminStyles(): void
    {
        if (get_post_type() !== PostTypes::PLAN) {
            return;
        }

        printf(
            '<style>.acf-field.ol-uid,.acf-th[data-key="%1$s"],.acf-th[data-key="%2$s"]{display:none!important}'
            . '.acf-field[data-key="%3$s"] .acf-label label{font-size:1.1em}</style>',
            esc_attr(self::WORKOUT_UID),
            esc_attr(self::PRESCRIPTION_UID),
            esc_attr(self::WEEK_HEADING)
        );
    }

    /**
     * Label every Week row "Week N · Workout names", so collapsed Weeks can be
     * told apart. Purely cosmetic; nothing is saved.
     */
    public function adminScript(): void
    {
        if (get_post_type() !== PostTypes::PLAN) {
            return;
        }

        $config = wp_json_encode([
            'weeks'   => self::WEEKS,
            'heading' => self::WEEK_HEADING,
            'name'    => self::WORKOUT_NAME,
            'week'    => __('Week', 'optimum-lift-plans'),
        ]);

        echo <<<HTML
<script>
(function ($, c) {
    if (typeof acf === 'undefined') { return; }
    function label() {
        $('.acf-field[data-key="' + c.weeks + '"] > .acf-input > .acf-repeater > table > tbody > .acf-row:not(.acf-clone)').each(function (i) {
            var names = $(this).find('.acf-field[data-key="' + c.name + '"] input').map(function () { return this.value; }).get().filter(Boolean);
            $(this).find('.acf-field[data-key="' + c.heading + '"]').first().find('.acf-label label')
                .text(c.week + ' ' + (i + 1) + (names.length ? ' · ' + names.join(', ') : ''));
        });
    }
    acf.addAction('ready', label);
    acf.addAction('append', label);
    acf.addAction('sortstop', label);
    acf.addAction('remove', function () { setTimeout(label, 400); });
    $(document).on('change', '.acf-field[data-key="' + c.name + '"] input', label);
})(jQuery, {$config});
</script>
HTML;
    }

    private function sentinelPresent(): bool
    {
        return isset($_POST[self::SENTINEL]); // phpcs:ignore -- presence check only.
    }

    private function noticeKey(): string
    {
        return 'ol_plans_truncated_' . get_current_user_id();
    }

    private function truncationMessage(): string
    {
        return __('The Plan was not saved: the form was cut short because it exceeds the server\'s max_input_vars limit. Your last saved version is unchanged. Raise max_input_vars and save again.', 'optimum-lift-plans');
    }

    /**
     * Repeater rows from a POSTed ACF value, without ACF's clone template.
     *
     * @return array<string, array<string, mixed>>
     */
    private function rows(mixed $repeater): array
    {
        if (!is_array($repeater)) {
            return [];
        }

        unset($repeater['acfcloneindex']);

        return array_filter($repeater, 'is_array');
    }
}
