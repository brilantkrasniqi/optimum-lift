<?php

/**
 * The Download. Rendered by Dompdf, which supports CSS 2.1 plus a little more:
 * no flexbox or grid, so lay out with tables and blocks.
 *
 * Override in a theme at optimum-lift-plans/pdf/plan.php. Cached PDFs are keyed
 * by the HTML this produces, so template edits take effect immediately.
 *
 * @var \OptimumLift\Plans\Plan\Plan $plan
 */

declare(strict_types=1);

use OptimumLift\Plans\Content\Choices;
use OptimumLift\Plans\Format;

if (!defined('ABSPATH')) {
    exit;
}

$cover = Format::imagePath($plan->coverId, 'large');
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr(get_bloginfo('language')); ?>">
<head>
<meta charset="utf-8">
<title><?php echo esc_html($plan->title); ?></title>
<style>
    @page { margin: 56px 48px 64px; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #14181c; line-height: 1.45; }
    h1, h2, h3, h4 { margin: 0; line-height: 1.2; }
    .muted { color: #5b6670; }
    .eyebrow { font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #1d4ed8; }
    .page-break { page-break-before: always; }

    .cover { text-align: left; padding-top: 80px; }
    .cover h1 { font-size: 34px; margin: 8px 0 16px; }
    .cover img { width: 100%; max-height: 300px; margin-bottom: 24px; }
    .cover .summary { font-size: 13px; margin-bottom: 24px; }
    .facts { border-collapse: collapse; margin-bottom: 24px; }
    .facts th { text-align: left; font-weight: normal; color: #5b6670; padding: 3px 16px 3px 0; }
    .facts td { font-weight: bold; padding: 3px 0; }

    .week-head { border-bottom: 2px solid #14181c; padding-bottom: 6px; margin-bottom: 14px; }
    .week-head h2 { font-size: 20px; }
    .workout { margin-bottom: 18px; page-break-inside: avoid; }
    .workout h3 { font-size: 13px; margin-bottom: 6px; }

    table.prescriptions { width: 100%; border-collapse: collapse; }
    table.prescriptions th { white-space: nowrap; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #5b6670; border-bottom: 1px solid #dfe3e8; padding: 4px 6px; }
    table.prescriptions td { border-bottom: 1px solid #dfe3e8; padding: 6px; vertical-align: top; }
    table.prescriptions td.exercise { width: 32%; font-weight: bold; }
    table.prescriptions td.volume { width: 13%; white-space: nowrap; }
    table.prescriptions td.intensity, table.prescriptions td.rest { width: 9%; white-space: nowrap; }

    .exercise-entry { page-break-inside: avoid; border-bottom: 1px solid #dfe3e8; padding: 12px 0; }
    .exercise-entry table { width: 100%; border-collapse: collapse; }
    .exercise-entry td { vertical-align: top; }
    .exercise-entry img { width: 150px; }
    .exercise-entry h3 { font-size: 13px; margin-bottom: 4px; }
    .exercise-entry .instructions p { margin: 4px 0; }
</style>
</head>
<body>

<div class="cover">
    <?php if ($cover !== '') : ?>
        <img src="<?php echo esc_attr($cover); ?>" alt="">
    <?php endif; ?>
    <div class="eyebrow"><?php echo esc_html(get_bloginfo('name')); ?></div>
    <h1><?php echo esc_html($plan->title); ?></h1>

    <?php if ($plan->summary !== '') : ?>
        <p class="summary"><?php echo esc_html($plan->summary); ?></p>
    <?php endif; ?>

    <table class="facts">
        <tr>
            <th><?php esc_html_e('Length', 'optimum-lift-plans'); ?></th>
            <td>
                <?php
                /* translators: 1: number of Weeks, 2: number of Workouts */
                echo esc_html(sprintf(__('%1$d Weeks, %2$d Workouts', 'optimum-lift-plans'), count($plan->weeks), $plan->workoutCount()));
                ?>
            </td>
        </tr>
        <?php if ($plan->goal !== '') : ?>
            <tr><th><?php esc_html_e('Goal', 'optimum-lift-plans'); ?></th><td><?php echo esc_html($plan->goal); ?></td></tr>
        <?php endif; ?>
        <?php if ($plan->targetAudience !== '') : ?>
            <tr><th><?php esc_html_e('For', 'optimum-lift-plans'); ?></th><td><?php echo esc_html($plan->targetAudience); ?></td></tr>
        <?php endif; ?>
        <?php if ($plan->difficulty !== '') : ?>
            <tr><th><?php esc_html_e('Difficulty', 'optimum-lift-plans'); ?></th><td><?php echo esc_html(Choices::labels(Choices::difficulty(), $plan->difficulty)); ?></td></tr>
        <?php endif; ?>
        <?php foreach ($plan->phases as $phase) : ?>
            <tr>
                <th><?php echo esc_html($phase->name); ?></th>
                <td>
                    <?php
                    /* translators: 1: first Week, 2: last Week */
                    echo esc_html(sprintf(__('Weeks %1$d–%2$d', 'optimum-lift-plans'), $phase->firstWeek, $phase->lastWeek));
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php foreach ($plan->weeks as $week) : ?>
    <div class="page-break">
        <div class="week-head">
            <?php if ($week->phase !== null) : ?>
                <div class="eyebrow"><?php echo esc_html($week->phase->name); ?></div>
            <?php endif; ?>
            <h2>
                <?php
                /* translators: %d: Week number */
                echo esc_html(sprintf(__('Week %d', 'optimum-lift-plans'), $week->number));
                ?>
            </h2>
        </div>

        <?php foreach ($week->workouts as $workout) : ?>
            <div class="workout">
                <h3>
                    <?php
                    /* translators: 1: Workout number, 2: Workout name */
                    echo esc_html(sprintf(__('Workout %1$d: %2$s', 'optimum-lift-plans'), $workout->number, $workout->name));
                    ?>
                </h3>
                <table class="prescriptions">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Exercise', 'optimum-lift-plans'); ?></th>
                            <th><?php esc_html_e('Sets × target', 'optimum-lift-plans'); ?></th>
                            <th><?php esc_html_e('Intensity', 'optimum-lift-plans'); ?></th>
                            <th><?php esc_html_e('Rest', 'optimum-lift-plans'); ?></th>
                            <th><?php esc_html_e('Notes', 'optimum-lift-plans'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workout->prescriptions as $prescription) : ?>
                            <tr>
                                <td class="exercise"><?php echo esc_html($prescription->exercise->name); ?></td>
                                <td class="volume"><?php echo esc_html(Format::volume($prescription)); ?></td>
                                <td class="intensity"><?php echo esc_html($prescription->intensity); ?></td>
                                <td class="rest"><?php echo esc_html(Format::rest($prescription->restSeconds)); ?></td>
                                <td class="muted"><?php echo esc_html($prescription->notes); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php $exercises = $plan->exercises(); ?>
<?php if ($exercises !== []) : ?>
    <div class="page-break">
        <div class="week-head"><h2><?php esc_html_e('Exercises', 'optimum-lift-plans'); ?></h2></div>

        <?php foreach ($exercises as $exercise) : ?>
            <?php $image = Format::imagePath($exercise->imageId); ?>
            <div class="exercise-entry">
                <table>
                    <tr>
                        <?php if ($image !== '') : ?>
                            <td style="width: 165px;"><img src="<?php echo esc_attr($image); ?>" alt=""></td>
                        <?php endif; ?>
                        <td>
                            <h3><?php echo esc_html($exercise->name); ?></h3>
                            <p class="muted">
                                <?php echo esc_html(implode(' · ', array_filter([Format::muscles($exercise), Format::equipment($exercise)]))); ?>
                            </p>
                            <div class="instructions"><?php echo wp_kses_post($exercise->instructions); ?></div>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html>
