<?php

/**
 * Training › Import Exercises. Not overridable by the theme: it is an admin screen.
 * assets/admin-import.js runs the import from the [data-ol-import] markup.
 *
 * @var \OptimumLift\Plans\Library\LibraryFile        $file
 * @var \OptimumLift\Plans\Library\ImportReport|null  $report  The dry run; null when the file has problems.
 * @var array<string, string>                         $labels  What each count means.
 * @var string                                        $exercises URL of the Exercises list.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$total   = count($file->entries);
$counts  = $report !== null ? $report->counts : [];
$pending = array_sum(array_intersect_key($counts, array_flip(['created', 'adopted', 'filled', 'keys_added', 'media_added'])));
?>
<div class="wrap" data-ol-import data-total="<?php echo esc_attr((string) $total); ?>" data-exercises="<?php echo esc_url($exercises); ?>">
    <h1><?php esc_html_e('Import Exercises', 'optimum-lift-plans'); ?></h1>

    <p style="max-width: 46rem;">
        <?php
        echo esc_html(sprintf(
            /* translators: %d: number of Exercises in the library */
            _n(
                'The plugin comes with a library of %d Exercise, with a still image for the PDF and an animation for the Portal.',
                'The plugin comes with a library of %d Exercises, each with a still image for the PDF and an animation for the Portal.',
                $total,
                'optimum-lift-plans'
            ),
            $total
        ));
        ?>
        <?php esc_html_e('Importing adds the ones you do not have and fills in empty fields. It never changes a field you have filled in, so it is safe to run again.', 'optimum-lift-plans'); ?>
    </p>

    <?php if ($file->problems !== []) : ?>
        <div class="notice notice-error inline">
            <p><?php esc_html_e('The library file that comes with the plugin has problems, so nothing can be imported:', 'optimum-lift-plans'); ?></p>
            <ul style="list-style: disc; padding-left: 1.5em;">
                <?php foreach ($file->problems as $problem) : ?>
                    <li><?php echo esc_html($problem); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else : ?>
        <?php /* The preview and the button; hidden once an import finishes, when its numbers are out of date. */ ?>
        <div data-plan>
            <h2><?php esc_html_e('What an import would do now', 'optimum-lift-plans'); ?></h2>
            <table class="widefat striped" style="max-width: 46rem;">
                <tbody>
                    <?php foreach ($labels as $count => $label) : ?>
                        <?php if (($counts[$count] ?? 0) > 0) : ?>
                            <tr>
                                <td><?php echo esc_html($label); ?></td>
                                <td style="width: 6rem; text-align: right;"><?php echo esc_html(number_format_i18n($counts[$count])); ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($pending === 0) : ?>
                <div class="notice notice-success inline" style="max-width: 46rem;">
                    <p><?php esc_html_e('The library is fully imported. There is nothing to do.', 'optimum-lift-plans'); ?></p>
                </div>
            <?php else : ?>
                <p style="max-width: 46rem;">
                    <?php esc_html_e('Keep this page open until the import finishes; it takes a few minutes. If it stops, press the button again: it carries on and never imports anything twice.', 'optimum-lift-plans'); ?>
                </p>
                <p>
                    <button type="button" class="button button-primary button-hero" data-start><?php esc_html_e('Import Exercises', 'optimum-lift-plans'); ?></button>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($pending > 0) : ?>
            <div data-progress hidden style="max-width: 46rem;">
                <progress max="<?php echo esc_attr((string) $total); ?>" value="0" style="width: 100%; height: 1.25rem;"></progress>
                <p data-status role="status" aria-live="polite"></p>
            </div>
            <div data-result hidden style="max-width: 46rem;"></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
