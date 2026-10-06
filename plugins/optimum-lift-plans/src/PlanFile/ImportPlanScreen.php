<?php

/**
 * Training › Import Plan: imports a Plan file from wp-admin, for sites without
 * WP-CLI. One request reads the uploaded file where PHP left it (it is never
 * moved into uploads), checks it and imports it. "Check only" runs every
 * check and writes nothing.
 *
 * Administrators only, like the Exercise import.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

use OptimumLift\Plans\Content\PostTypes;
use OptimumLift\Plans\Library\ImportScreen;

use const OptimumLift\Plans\DIR;

final class ImportPlanScreen
{
    public const PAGE = 'ol-import-plan';

    private const ACTION     = 'ol_import_plan';
    private const CAPABILITY = 'manage_options';
    private const FIELD      = 'plan_file';

    public function __construct(private readonly PlanFileImporter $importer)
    {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_' . self::ACTION, [$this, 'handle']);
        add_action('admin_notices', [$this, 'importedNotice']);
        add_action('admin_footer-edit.php', [$this, 'listButton']);
    }

    public function menu(): void
    {
        add_submenu_page(
            PostTypes::MENU,
            __('Import Plan', 'optimum-lift-plans'),
            __('Import Plan', 'optimum-lift-plans'),
            self::CAPABILITY,
            self::PAGE,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        $result = get_transient($this->resultKey());

        if ($result !== false) {
            delete_transient($this->resultKey());
        }

        (static function (array $vars): void {
            extract($vars); // phpcs:ignore -- template variables.
            include DIR . '/templates/admin/import-plan.php';
        })([
            'result'    => is_array($result) ? $result : null,
            'action'    => self::ACTION,
            'field'     => self::FIELD,
            'postUrl'   => admin_url('admin-post.php'),
            'exercises' => admin_url('admin.php?page=' . ImportScreen::PAGE),
            'counts'    => static fn (array $counts): string => self::countsText($counts),
        ]);
    }

    /**
     * The form's target. Every outcome but a finished import goes back to the
     * screen, which lists it.
     */
    public function handle(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You are not allowed to import Plans.', 'optimum-lift-plans'), '', ['response' => 403]);
        }

        // A request over post_max_size arrives with no fields at all, nonce included.
        if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) { // phpcs:ignore -- no data is read.
            $this->back(['problems' => [$this->tooBigForServer()]]);
        }

        if (wp_verify_nonce((string) ($_POST['_wpnonce'] ?? ''), self::ACTION) === false) { // phpcs:ignore -- compared only.
            wp_die(
                esc_html__('This link has expired. Go back, reload the page and try again.', 'optimum-lift-plans'),
                '',
                ['response' => 403, 'back_link' => true]
            );
        }

        $dryRun = !empty($_POST['dry_run']);
        $upload = $_FILES[self::FIELD] ?? null; // phpcs:ignore -- checked below, read only.
        $name   = is_array($upload) ? sanitize_file_name((string) ($upload['name'] ?? '')) : '';
        $error  = $this->uploadProblem($upload);

        if ($error !== null) {
            $this->back(['file' => $name, 'dry_run' => $dryRun, 'problems' => [$error]]);
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(120);
        }

        /** @var array{tmp_name: string} $upload */
        $report = $this->importer->run(PlanFile::parse((string) file_get_contents($upload['tmp_name'])), $dryRun);

        if (!$report->succeeded() || $report->dryRun) {
            $this->back([
                'file'     => $name,
                'dry_run'  => $dryRun,
                'problems' => $report->problems,
                'notes'    => $report->notes,
                'counts'   => $report->counts,
            ]);
        }

        set_transient($this->noticeKey(), [
            'plan'   => $report->planId,
            'counts' => $report->counts,
            'notes'  => $report->notes,
        ], 5 * MINUTE_IN_SECONDS);

        wp_safe_redirect($report->editUrl);
        exit;
    }

    /**
     * After an import, on the new draft's edit screen.
     */
    public function importedNotice(): void
    {
        $screen = get_current_screen();

        if ($screen === null || $screen->base !== 'post' || $screen->post_type !== PostTypes::PLAN) {
            return;
        }

        $notice = get_transient($this->noticeKey());

        if (!is_array($notice) || ($notice['plan'] ?? 0) !== (int) ($_GET['post'] ?? 0)) { // phpcs:ignore -- read-only.
            return;
        }

        delete_transient($this->noticeKey());

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p>',
            esc_html(sprintf(
                /* translators: %s: what was imported, e.g. 10 Weeks, 30 Workouts, 180 Prescriptions */
                __('Imported as a draft: %s. Review it, publish it, then add it to a Product.', 'optimum-lift-plans'),
                self::countsText(is_array($notice['counts'] ?? null) ? $notice['counts'] : [])
            ))
        );

        foreach (is_array($notice['notes'] ?? null) ? $notice['notes'] : [] as $note) {
            printf('<p>%s</p>', esc_html((string) $note));
        }

        echo '</div>';
    }

    /**
     * An "Import Plan" button beside "Add New" on the Training Plans list.
     * WordPress has no hook for that spot, so a few lines of script put it there.
     */
    public function listButton(): void
    {
        $screen = get_current_screen();

        if ($screen === null || $screen->id !== 'edit-' . PostTypes::PLAN || !current_user_can(self::CAPABILITY)) {
            return;
        }

        $config = wp_json_encode([
            'url'   => admin_url('admin.php?page=' . self::PAGE),
            'label' => __('Import Plan', 'optimum-lift-plans'),
        ]);

        echo <<<HTML
<script>
(function (c) {
    var add = document.querySelector('.wrap .page-title-action');
    if (!add) { return; }
    var button = document.createElement('a');
    button.href = c.url;
    button.className = 'page-title-action';
    button.textContent = c.label;
    add.after(button);
})({$config});
</script>
HTML;
    }

    /**
     * "10 Weeks, 30 Workouts, 180 Prescriptions"
     *
     * @param array<mixed> $counts
     */
    public static function countsText(array $counts): string
    {
        $weeks         = (int) ($counts['weeks'] ?? 0);
        $workouts      = (int) ($counts['workouts'] ?? 0);
        $prescriptions = (int) ($counts['prescriptions'] ?? 0);

        return implode(', ', [
            /* translators: %s: number of Weeks */
            sprintf(_n('%s Week', '%s Weeks', $weeks, 'optimum-lift-plans'), number_format_i18n($weeks)),
            /* translators: %s: number of Workouts */
            sprintf(_n('%s Workout', '%s Workouts', $workouts, 'optimum-lift-plans'), number_format_i18n($workouts)),
            /* translators: %s: number of Prescriptions */
            sprintf(_n('%s Prescription', '%s Prescriptions', $prescriptions, 'optimum-lift-plans'), number_format_i18n($prescriptions)),
        ]);
    }

    /**
     * The problem with an upload, or null when the file can be read.
     */
    private function uploadProblem(mixed $upload): ?string
    {
        $error = is_array($upload) ? (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            return __('Choose a Plan file to import.', 'optimum-lift-plans');
        }

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return $this->tooBigForServer();
        }

        if ($error !== UPLOAD_ERR_OK || !is_array($upload) || !is_uploaded_file((string) ($upload['tmp_name'] ?? ''))) {
            /* translators: %d: PHP upload error code */
            return sprintf(__('The upload failed (error %d). Try again.', 'optimum-lift-plans'), $error);
        }

        if ((int) ($upload['size'] ?? 0) > PlanFile::MAX_BYTES) {
            return PlanFile::tooLarge();
        }

        return null;
    }

    private function tooBigForServer(): string
    {
        return sprintf(
            /* translators: %s: the largest upload this server accepts, e.g. 2 MB */
            __('The file is larger than this server accepts (%s). A Plan file is usually about 100 KB; check that it is the right file.', 'optimum-lift-plans'),
            size_format(wp_max_upload_size())
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    private function back(array $result): never
    {
        set_transient($this->resultKey(), $result, 5 * MINUTE_IN_SECONDS);
        wp_safe_redirect(admin_url('admin.php?page=' . self::PAGE));
        exit;
    }

    private function resultKey(): string
    {
        return 'ol_plan_import_result_' . get_current_user_id();
    }

    private function noticeKey(): string
    {
        return 'ol_plan_imported_' . get_current_user_id();
    }
}
