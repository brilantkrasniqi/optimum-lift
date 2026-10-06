<?php

/**
 * Training › Import: imports the Exercise library from wp-admin, for sites
 * without WP-CLI. The browser asks for one batch at a time, so each request
 * stays far inside a shared host's time limit; every batch is safe to repeat,
 * so a failed one is retried and a closed tab is finished by starting again.
 *
 * Administrators only: an import creates hundreds of posts and files.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Library;

use OptimumLift\Plans\Content\PostTypes;

use const OptimumLift\Plans\DIR;
use const OptimumLift\Plans\FILE;
use const OptimumLift\Plans\VERSION;

final class ImportScreen
{
    public const PAGE = 'ol-import-exercises';

    private const ACTION     = 'ol_import_exercises';
    private const CAPABILITY = 'manage_options';
    /** About 20 media files, 5 seconds at most on the local stack. */
    private const BATCH = 10;

    private string $hook = '';

    public function __construct(private readonly ExerciseImporter $importer)
    {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('wp_ajax_' . self::ACTION, [$this, 'batch']);
        add_action('admin_notices', [$this, 'emptyLibraryNotice']);
    }

    public function menu(): void
    {
        $this->hook = (string) add_submenu_page(
            PostTypes::MENU,
            __('Import Exercises', 'optimum-lift-plans'),
            __('Import', 'optimum-lift-plans'),
            self::CAPABILITY,
            self::PAGE,
            [$this, 'render']
        );
    }

    public function assets(string $hook): void
    {
        if ($this->hook === '' || $hook !== $this->hook) {
            return;
        }

        wp_enqueue_script('ol-admin-import', plugins_url('assets/admin-import.js', FILE), [], $this->assetVersion('assets/admin-import.js'), ['in_footer' => true]);
        wp_localize_script('ol-admin-import', 'olImport', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'action'  => self::ACTION,
            'nonce'   => wp_create_nonce(self::ACTION),
            'i18n'    => [
                /* translators: 1: Exercises done, 2: Exercises in the library */
                'progress' => __('Importing… %1$d of %2$d Exercises', 'optimum-lift-plans'),
                'done'     => __('Import finished.', 'optimum-lift-plans'),
                'errors'   => __('Import finished with errors. Running it again retries what failed.', 'optimum-lift-plans'),
                /* translators: %s: error message */
                'failed'   => __('The import stopped: %s', 'optimum-lift-plans'),
                'retry'    => __('Continue import', 'optimum-lift-plans'),
                'leave'    => __('The import is still running. Leave anyway?', 'optimum-lift-plans'),
                'offline'  => __('the site could not be reached. Check the connection', 'optimum-lift-plans'),
                /* translators: %d: number of notes */
                'details'  => __('Details (%d)', 'optimum-lift-plans'),
                'view'     => __('Go to Exercises', 'optimum-lift-plans'),
                'counts'   => $this->countLabels(),
            ],
        ]);
    }

    public function render(): void
    {
        $file   = LibraryFile::bundled();
        $report = $file->problems === [] ? $this->importer->run($file, new ImportOptions(dryRun: true)) : null;

        (static function (array $vars): void {
            extract($vars); // phpcs:ignore -- template variables.
            include DIR . '/templates/admin/import-exercises.php';
        })([
            'file'      => $file,
            'report'    => $report,
            'labels'    => $this->countLabels(),
            'exercises' => admin_url('edit.php?post_type=' . PostTypes::EXERCISE),
        ]);
    }

    /**
     * One batch. Answers with the next offset and what the batch did; the
     * browser adds up the counts.
     */
    public function batch(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('You are not allowed to import Exercises.', 'optimum-lift-plans')], 403);
        }

        if (check_ajax_referer(self::ACTION, '_ajax_nonce', false) === false) {
            wp_send_json_error(['message' => __('This page has expired. Reload it and import again.', 'optimum-lift-plans')], 403);
        }

        $file = LibraryFile::bundled();

        if ($file->problems !== []) {
            wp_send_json_error(['message' => __('The library file has problems. Reload the page to see them.', 'optimum-lift-plans')], 500);
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(120);
        }

        $total  = count($file->entries);
        $offset = min($total, max(0, (int) ($_POST['offset'] ?? 0))); // phpcs:ignore -- nonce checked above.
        $report = $this->importer->run($file, new ImportOptions(), $offset, self::BATCH);
        $next   = min($total, $offset + self::BATCH);

        // An error, not a result: the browser must not move past this batch.
        if ($report->locked) {
            wp_send_json_error(['message' => implode(' ', $report->errors)], 409);
        }

        wp_send_json_success([
            'next'     => $next,
            'total'    => $total,
            'finished' => $next >= $total,
            'counts'   => $report->counts,
            'notes'    => $report->notes,
            'errors'   => $report->errors,
        ]);
    }

    public function emptyLibraryNotice(): void
    {
        $screen = get_current_screen();

        if ($screen === null || $screen->id !== 'edit-' . PostTypes::EXERCISE || !current_user_can(self::CAPABILITY)) {
            return;
        }

        $live = 0;

        foreach ((array) wp_count_posts(PostTypes::EXERCISE) as $status => $count) {
            if ($status !== 'trash' && $status !== 'auto-draft') {
                $live += (int) $count;
            }
        }

        // Read the library file only when the notice may show.
        $total = $live === 0 ? count(LibraryFile::bundled()->entries) : 0;

        if ($total === 0) {
            return;
        }

        printf(
            '<div class="notice notice-info"><p>%s</p><p><a class="button button-primary" href="%s">%s</a></p></div>',
            esc_html(sprintf(
                /* translators: %d: number of Exercises in the library */
                _n(
                    'There are no Exercises yet. The plugin comes with a library of %d Exercise, with an image and an animation.',
                    'There are no Exercises yet. The plugin comes with a library of %d Exercises, each with an image and an animation.',
                    $total,
                    'optimum-lift-plans'
                ),
                $total
            )),
            esc_url(admin_url('admin.php?page=' . self::PAGE)),
            esc_html__('Import the library', 'optimum-lift-plans')
        );
    }

    /**
     * What each ImportReport count means, for the screen and its script.
     *
     * @return array<string, string>
     */
    private function countLabels(): array
    {
        return [
            'created'      => __('New Exercises', 'optimum-lift-plans'),
            'adopted'      => __('Your Exercises with a library name, taken over', 'optimum-lift-plans'),
            'filled'       => __('Library Exercises with empty fields filled in', 'optimum-lift-plans'),
            'skipped'      => __('Library Exercises already complete', 'optimum-lift-plans'),
            'in_trash'     => __('Library Exercises in the trash, left alone', 'optimum-lift-plans'),
            'keys_added'   => __('Other Exercises given a library key', 'optimum-lift-plans'),
            'media_added'  => __('Images and animations added to the Media Library', 'optimum-lift-plans'),
            'media_reused' => __('Images and animations already in the Media Library', 'optimum-lift-plans'),
        ];
    }

    private function assetVersion(string $relative): string
    {
        $path = DIR . '/' . $relative;

        return wp_get_environment_type() === 'local' && is_file($path) ? (string) filemtime($path) : VERSION;
    }
}
