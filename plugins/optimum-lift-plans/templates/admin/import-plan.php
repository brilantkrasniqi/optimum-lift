<?php

/**
 * Training › Import Plan. Not overridable by the theme: it is an admin screen.
 *
 * @var array<string, mixed>|null $result    The last check or failed import, or null.
 * @var string                    $action    admin-post.php action and nonce action.
 * @var string                    $field     Name of the file input.
 * @var string                    $postUrl   URL of admin-post.php.
 * @var string                    $exercises URL of Training › Import Exercises.
 * @var \Closure(array<mixed>): string $counts  "10 Weeks, 30 Workouts, 180 Prescriptions"
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$problems = is_array($result['problems'] ?? null) ? $result['problems'] : [];
$notes    = is_array($result['notes'] ?? null) ? $result['notes'] : [];
$file     = is_string($result['file'] ?? null) && $result['file'] !== '' ? $result['file'] : __('The file', 'optimum-lift-plans');
?>
<div class="wrap">
    <h1><?php esc_html_e('Import Plan', 'optimum-lift-plans'); ?></h1>

    <div style="max-width: 46rem;">
        <p><?php esc_html_e('A Plan file is one Training Plan saved as JSON, with its Exercises named by library key. Export one from another site with "Export as JSON" on its Training Plans list.', 'optimum-lift-plans'); ?></p>
        <p><?php esc_html_e('Importing always creates a new Plan, as a draft. It never changes a Plan you already have. Review the draft, publish it, then add it to a Product.', 'optimum-lift-plans'); ?></p>
        <p>
            <?php esc_html_e('Every Exercise in the file must already exist on this site, published. If the check finds one missing, import the Exercise library first:', 'optimum-lift-plans'); ?>
            <a href="<?php echo esc_url($exercises); ?>"><?php esc_html_e('Import Exercises', 'optimum-lift-plans'); ?></a>
        </p>
    </div>

    <?php if ($result !== null && $problems !== []) : ?>
        <div class="notice notice-error inline" style="max-width: 46rem;">
            <p>
                <?php
                echo esc_html(sprintf(
                    /* translators: %s: file name */
                    _n('Nothing was imported. %s has this problem:', 'Nothing was imported. %s has these problems:', count($problems), 'optimum-lift-plans'),
                    $file
                ));
                ?>
            </p>
            <ul style="list-style: disc; padding-left: 1.5em;">
                <?php foreach ($problems as $problem) : ?>
                    <li><?php echo esc_html((string) $problem); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($result !== null) : ?>
        <div class="notice notice-success inline" style="max-width: 46rem;">
            <p>
                <?php
                echo esc_html(sprintf(
                    /* translators: 1: file name, 2: what it holds, e.g. 10 Weeks, 30 Workouts, 180 Prescriptions */
                    __('%1$s is ready to import: %2$s. Nothing was imported yet.', 'optimum-lift-plans'),
                    $file,
                    $counts(is_array($result['counts'] ?? null) ? $result['counts'] : [])
                ));
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($notes !== []) : ?>
        <div class="notice notice-warning inline" style="max-width: 46rem;">
            <?php foreach ($notes as $note) : ?>
                <p><?php echo esc_html((string) $note); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url($postUrl); ?>" enctype="multipart/form-data" data-ol-import-plan>
        <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
        <?php wp_nonce_field($action); ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="ol-plan-file"><?php esc_html_e('Plan file', 'optimum-lift-plans'); ?></label></th>
                    <td><input type="file" id="ol-plan-file" name="<?php echo esc_attr($field); ?>" accept=".json,application/json"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Check only', 'optimum-lift-plans'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="dry_run" value="1">
                            <?php esc_html_e('Check only, don\'t import', 'optimum-lift-plans'); ?>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button(__('Import Plan', 'optimum-lift-plans')); ?>
    </form>
</div>
<script>
// One click, one import: a double click must not create two Plans.
document.querySelector('[data-ol-import-plan]').addEventListener('submit', function (event) {
    event.currentTarget.querySelector('[type="submit"]').disabled = true;
});
</script>
