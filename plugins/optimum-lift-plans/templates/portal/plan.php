<?php

/**
 * One Plan: its Weeks and Workouts, with the Customer's progress.
 *
 * Override in a theme at optimum-lift-plans/portal/plan.php.
 *
 * @var \OptimumLift\Plans\Plan\Plan $plan
 * @var array<string, string>        $statuses workout uid => in_progress|completed
 */

declare(strict_types=1);

use OptimumLift\Plans\Logging\WorkoutLog;
use OptimumLift\Plans\Portal\Endpoint;
use OptimumLift\Plans\Portal\Portal;

if (!defined('ABSPATH')) {
    exit;
}

$next = Portal::nextWorkout($plan, $statuses);
?>
<div class="ol-portal">
    <p class="ol-back"><a href="<?php echo esc_url(Endpoint::url()); ?>">&larr; <?php esc_html_e('All Plans', 'optimum-lift-plans'); ?></a></p>

    <?php if ($plan->summary !== '') : ?>
        <p class="ol-lead"><?php echo esc_html($plan->summary); ?></p>
    <?php endif; ?>

    <p class="ol-actions">
        <?php if ($next !== null) : ?>
            <a class="ol-button" href="<?php echo esc_url(Endpoint::workoutUrl($plan->id, $next->uid)); ?>">
                <?php
                /* translators: 1: Week number, 2: Workout name */
                echo esc_html(sprintf(__('Next: Week %1$d, %2$s', 'optimum-lift-plans'), $next->weekNumber, $next->name));
                ?>
            </a>
        <?php endif; ?>
        <a class="ol-button ol-button--quiet" href="<?php echo esc_url(Endpoint::url($plan->id, 'download')); ?>"><?php esc_html_e('Download PDF', 'optimum-lift-plans'); ?></a>
    </p>

    <?php foreach ($plan->weeks as $week) : ?>
        <?php
        $done = count(array_filter($week->workouts, static fn ($w): bool => ($statuses[$w->uid] ?? '') === WorkoutLog::COMPLETED));
        $open = $next !== null && $next->weekNumber === $week->number;
        ?>
        <details class="ol-week" <?php echo $open ? 'open' : ''; ?>>
            <summary class="ol-week__summary">
                <span class="ol-week__title">
                    <?php
                    /* translators: %d: Week number */
                    echo esc_html(sprintf(__('Week %d', 'optimum-lift-plans'), $week->number));
                    ?>
                    <?php if ($week->phase !== null) : ?>
                        <span class="ol-tag"><?php echo esc_html($week->phase->name); ?></span>
                    <?php endif; ?>
                </span>
                <span class="ol-muted">
                    <?php
                    /* translators: 1: completed Workouts, 2: Workouts in the Week */
                    echo esc_html(sprintf(__('%1$d / %2$d', 'optimum-lift-plans'), $done, count($week->workouts)));
                    ?>
                </span>
            </summary>
            <ol class="ol-workout-list">
                <?php foreach ($week->workouts as $workout) : ?>
                    <?php $status = $statuses[$workout->uid] ?? ''; ?>
                    <li class="ol-workout-list__item ol-status--<?php echo esc_attr($status ?: 'todo'); ?>">
                        <a href="<?php echo esc_url(Endpoint::workoutUrl($plan->id, $workout->uid)); ?>">
                            <span class="ol-status-icon" aria-hidden="true"><?php echo $status === WorkoutLog::COMPLETED ? '✓' : ($status === WorkoutLog::IN_PROGRESS ? '•' : ''); ?></span>
                            <span>
                                <?php echo esc_html($workout->name); ?>
                                <span class="ol-muted">
                                    <?php
                                    /* translators: %d: number of Exercises */
                                    echo esc_html(sprintf(_n('%d Exercise', '%d Exercises', count($workout->prescriptions), 'optimum-lift-plans'), count($workout->prescriptions)));
                                    ?>
                                </span>
                            </span>
                            <span class="screen-reader-text">
                                <?php
                                echo esc_html(match ($status) {
                                    WorkoutLog::COMPLETED   => __('Completed', 'optimum-lift-plans'),
                                    WorkoutLog::IN_PROGRESS => __('In progress', 'optimum-lift-plans'),
                                    default                 => __('Not started', 'optimum-lift-plans'),
                                });
                                ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        </details>
    <?php endforeach; ?>
</div>
