<?php

/**
 * The Workout screen: what the Plan prescribes, and where the Customer logs
 * what they did. assets/portal.js saves each set as it changes.
 *
 * Override in a theme at optimum-lift-plans/portal/workout.php, keeping the
 * data-* attributes portal.js reads.
 *
 * @var \OptimumLift\Plans\Plan\Plan                                          $plan
 * @var \OptimumLift\Plans\Plan\Workout                                       $workout
 * @var \OptimumLift\Plans\Logging\WorkoutLog|null                            $log
 * @var array<string, array<int, \OptimumLift\Plans\Logging\LoggedSet>>       $sets     by prescription uid, set number
 * @var array<int, list<\OptimumLift\Plans\Logging\LoggedSet>>                $previous by Exercise ID
 * @var array<int, float|null>                                                $records  by Exercise ID
 * @var string                                                                $startAction
 */

declare(strict_types=1);

use OptimumLift\Plans\Format;
use OptimumLift\Plans\Portal\Endpoint;

if (!defined('ABSPATH')) {
    exit;
}

$startForm = static function (string $label, string $class = 'ol-button') use ($workout, $startAction): void {
    ?>
    <form method="post" class="ol-start">
        <?php wp_nonce_field($startAction . '_' . $workout->uid); ?>
        <button type="submit" name="<?php echo esc_attr($startAction); ?>" value="1" class="<?php echo esc_attr($class); ?>"><?php echo esc_html($label); ?></button>
    </form>
    <?php
};
?>
<div class="ol-portal ol-workout"
    <?php if ($log !== null) : ?>
        data-log-id="<?php echo esc_attr((string) $log->id); ?>"
        data-plan-url="<?php echo esc_url(Endpoint::url($plan->id)); ?>"
    <?php endif; ?>
>
    <p class="ol-back"><a href="<?php echo esc_url(Endpoint::url($plan->id)); ?>">&larr; <?php echo esc_html($plan->title); ?></a></p>

    <header class="ol-workout__header">
        <p class="ol-eyebrow">
            <?php
            /* translators: 1: Week number, 2: Workout number */
            echo esc_html(sprintf(__('Week %1$d · Workout %2$d', 'optimum-lift-plans'), $workout->weekNumber, $workout->number));
            ?>
        </p>
        <h2 class="ol-workout__title"><?php echo esc_html($workout->name); ?></h2>
        <?php if ($log?->isCompleted()) : ?>
            <p class="ol-muted">
                <?php
                /* translators: %s: date */
                echo esc_html(sprintf(__('Completed %s', 'optimum-lift-plans'), wp_date(get_option('date_format'), (int) strtotime($log->completedAt . ' UTC'))));
                ?>
            </p>
        <?php endif; ?>
    </header>

    <noscript><p class="ol-notice"><?php esc_html_e('Logging needs JavaScript enabled.', 'optimum-lift-plans'); ?></p></noscript>

    <?php if ($log === null) : ?>
        <?php $startForm(__('Start Workout', 'optimum-lift-plans')); ?>
    <?php endif; ?>

    <?php foreach ($workout->prescriptions as $prescription) : ?>
        <?php
        $exercise = $prescription->exercise;
        $last     = $previous[$exercise->id] ?? [];
        $record   = $records[$exercise->id] ?? null;
        $media    = $exercise->animationId ?: $exercise->imageId;
        $unit     = $prescription->isTimed() ? __('Seconds', 'optimum-lift-plans') : __('Reps', 'optimum-lift-plans');
        $field    = $prescription->isTimed() ? 'seconds' : 'reps';
        ?>
        <section class="ol-prescription" data-prescription-uid="<?php echo esc_attr($prescription->uid); ?>">
            <div class="ol-prescription__head">
                <?php if ($media > 0) : ?>
                    <div class="ol-prescription__media"><?php echo wp_get_attachment_image($media, 'thumbnail', false, ['loading' => 'lazy']); ?></div>
                <?php endif; ?>
                <div>
                    <h3 class="ol-prescription__name"><?php echo esc_html($exercise->name); ?></h3>
                    <p class="ol-prescription__target">
                        <?php echo esc_html(implode(' · ', array_filter([Format::volume($prescription), $prescription->intensity, Format::rest($prescription->restSeconds) ? sprintf(/* translators: %s: rest time */ __('Rest %s', 'optimum-lift-plans'), Format::rest($prescription->restSeconds)) : '']))); ?>
                    </p>
                </div>
            </div>

            <?php if ($prescription->notes !== '') : ?>
                <p class="ol-prescription__notes"><?php echo esc_html($prescription->notes); ?></p>
            <?php endif; ?>

            <?php if ($last !== [] || $record !== null) : ?>
                <p class="ol-prescription__history ol-muted">
                    <?php if ($last !== []) : ?>
                        <?php esc_html_e('Last time:', 'optimum-lift-plans'); ?>
                        <?php echo esc_html(implode(', ', array_map(static fn ($s): string => Format::loggedSet($s->loadKg, $s->reps, $s->seconds), $last))); ?>
                    <?php endif; ?>
                    <?php if ($record !== null) : ?>
                        <span class="ol-record"><?php esc_html_e('Personal Record:', 'optimum-lift-plans'); ?> <?php echo esc_html(Format::load($record)); ?></span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if ($exercise->instructions !== '' || $exercise->videoUrl !== '') : ?>
                <details class="ol-howto">
                    <summary><?php esc_html_e('How to', 'optimum-lift-plans'); ?></summary>
                    <?php echo wp_kses_post(wpautop($exercise->instructions)); ?>
                    <?php if ($exercise->videoUrl !== '') : ?>
                        <p><a href="<?php echo esc_url($exercise->videoUrl); ?>" target="_blank" rel="noopener"><?php esc_html_e('Watch the video', 'optimum-lift-plans'); ?></a></p>
                    <?php endif; ?>
                </details>
            <?php endif; ?>

            <?php if ($log !== null) : ?>
                <table class="ol-sets">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Set', 'optimum-lift-plans'); ?></th>
                            <th scope="col"><?php esc_html_e('kg', 'optimum-lift-plans'); ?></th>
                            <th scope="col"><?php echo esc_html($unit); ?></th>
                            <th scope="col"><span class="screen-reader-text"><?php esc_html_e('Status', 'optimum-lift-plans'); ?></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($n = 1; $n <= $prescription->sets; $n++) : ?>
                            <?php
                            $saved = $sets[$prescription->uid][$n] ?? null;
                            $hint  = $last[$n - 1] ?? ($last !== [] ? $last[array_key_last($last)] : null);
                            $done  = $saved === null ? null : ($saved->reps ?? $saved->seconds);
                            $hintResult = $hint === null ? null : ($hint->reps ?? $hint->seconds);
                            ?>
                            <tr class="ol-set" data-set-number="<?php echo esc_attr((string) $n); ?>" data-result-field="<?php echo esc_attr($field); ?>">
                                <th scope="row"><?php echo esc_html((string) $n); ?></th>
                                <td>
                                    <input class="ol-input" type="text" inputmode="decimal" autocomplete="off" data-field="load_kg"
                                        aria-label="<?php echo esc_attr(sprintf(/* translators: %d: set number */ __('Set %d load in kg', 'optimum-lift-plans'), $n)); ?>"
                                        value="<?php echo esc_attr($saved?->loadKg === null ? '' : rtrim(rtrim(number_format($saved->loadKg, 2, '.', ''), '0'), '.')); ?>"
                                        placeholder="<?php echo esc_attr($hint?->loadKg === null ? '' : rtrim(rtrim(number_format($hint->loadKg, 2, '.', ''), '0'), '.')); ?>">
                                </td>
                                <td>
                                    <input class="ol-input" type="text" inputmode="numeric" autocomplete="off" data-field="<?php echo esc_attr($field); ?>"
                                        aria-label="<?php echo esc_attr(sprintf(/* translators: 1: set number, 2: Reps or Seconds */ __('Set %1$d %2$s', 'optimum-lift-plans'), $n, $unit)); ?>"
                                        value="<?php echo esc_attr($done === null ? '' : (string) $done); ?>"
                                        placeholder="<?php echo esc_attr($hintResult === null ? $prescription->target : (string) $hintResult); ?>">
                                </td>
                                <td class="ol-set__status" aria-live="polite"><?php echo $saved !== null ? '✓' : ''; ?></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php if ($log !== null) : ?>
        <footer class="ol-workout__footer">
            <label class="ol-label" for="ol-workout-notes"><?php esc_html_e('Notes', 'optimum-lift-plans'); ?></label>
            <textarea id="ol-workout-notes" class="ol-input ol-input--block" rows="3" data-workout-notes
                placeholder="<?php esc_attr_e('How did it feel?', 'optimum-lift-plans'); ?>"><?php echo esc_textarea($log->notes); ?></textarea>
            <p class="ol-finish-message" aria-live="polite"></p>
            <p class="ol-actions">
                <button type="button" class="ol-button" data-finish-workout>
                    <?php $log->isCompleted() ? esc_html_e('Save changes', 'optimum-lift-plans') : esc_html_e('Finish Workout', 'optimum-lift-plans'); ?>
                </button>
            </p>
            <?php if ($log->isCompleted()) : ?>
                <?php $startForm(__('Log this Workout again', 'optimum-lift-plans'), 'ol-button ol-button--quiet'); ?>
            <?php endif; ?>
        </footer>
    <?php endif; ?>
</div>
