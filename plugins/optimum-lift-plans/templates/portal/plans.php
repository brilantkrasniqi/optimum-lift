<?php

/**
 * My Account › Plans.
 *
 * Override in a theme at optimum-lift-plans/portal/plans.php.
 *
 * @var list<array{plan: \OptimumLift\Plans\Plan\Plan, completed: int, next: ?\OptimumLift\Plans\Plan\Workout}> $entries
 */

declare(strict_types=1);

use OptimumLift\Plans\Portal\Endpoint;

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ol-portal">
    <?php if ($entries === []) : ?>
        <p class="ol-empty">
            <?php esc_html_e('You do not have any Plans yet.', 'optimum-lift-plans'); ?>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Browse the shop', 'optimum-lift-plans'); ?></a>
        </p>
    <?php endif; ?>

    <ul class="ol-plan-list">
        <?php foreach ($entries as $entry) : ?>
            <?php
            $plan  = $entry['plan'];
            $total = $plan->workoutCount();
            ?>
            <li class="ol-card">
                <?php if ($plan->coverId > 0) : ?>
                    <a class="ol-card__media" href="<?php echo esc_url(Endpoint::url($plan->id)); ?>" tabindex="-1" aria-hidden="true">
                        <?php echo wp_get_attachment_image($plan->coverId, 'medium_large'); ?>
                    </a>
                <?php endif; ?>
                <div class="ol-card__body">
                    <h2 class="ol-card__title">
                        <a href="<?php echo esc_url(Endpoint::url($plan->id)); ?>"><?php echo esc_html($plan->title); ?></a>
                    </h2>
                    <p class="ol-muted">
                        <?php
                        /* translators: 1: completed Workouts, 2: total Workouts */
                        echo esc_html(sprintf(__('%1$d of %2$d Workouts completed', 'optimum-lift-plans'), $entry['completed'], $total));
                        ?>
                    </p>
                    <progress class="ol-progress" max="<?php echo esc_attr((string) max(1, $total)); ?>" value="<?php echo esc_attr((string) $entry['completed']); ?>"></progress>
                    <p class="ol-actions">
                        <?php if ($entry['next'] !== null) : ?>
                            <a class="ol-button" href="<?php echo esc_url(Endpoint::workoutUrl($plan->id, $entry['next']->uid)); ?>">
                                <?php
                                /* translators: 1: Week number, 2: Workout name */
                                echo esc_html(sprintf(__('Next: Week %1$d, %2$s', 'optimum-lift-plans'), $entry['next']->weekNumber, $entry['next']->name));
                                ?>
                            </a>
                        <?php endif; ?>
                        <a class="ol-button ol-button--quiet" href="<?php echo esc_url(Endpoint::url($plan->id, 'download')); ?>">
                            <?php esc_html_e('Download PDF', 'optimum-lift-plans'); ?>
                        </a>
                    </p>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
