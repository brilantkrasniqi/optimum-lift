<?php

/**
 * `wp ol-plans export-plan` and `wp ol-plans import-plan`: Plan files from the
 * command line, for local work and for Claude. wp-admin runs the same exporter
 * and importer. See docs/plan-files.md.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Cli;

use OptimumLift\Plans\PlanFile\PlanFile;
use OptimumLift\Plans\PlanFile\PlanFileExporter;
use OptimumLift\Plans\PlanFile\PlanFileImporter;
use OptimumLift\Plans\PlanFile\PlanFileReport;
use WP_CLI;

final class PlanFileCommand
{
    public function __construct(
        private readonly PlanFileExporter $exporter,
        private readonly PlanFileImporter $importer,
    ) {
    }

    /**
     * Exports a Training Plan as a Plan file (JSON).
     *
     * The file names Exercises by library key and carries no post IDs, uids or
     * dates, so exporting an unchanged Plan always gives the same bytes.
     * Warnings about Exercises the importing site may not have go to stderr.
     *
     * ## OPTIONS
     *
     * <plan-id>
     * : The Training Plan's post ID.
     *
     * [--file=<path>]
     * : Write the file here instead of to stdout. In Docker, /plans is the repo's content/plans/ folder.
     *
     * ## EXAMPLES
     *
     *     wp ol-plans export-plan 123 --file=/plans/home-hypertrophy.json
     *     wp ol-plans export-plan 123 > plan.json
     *
     * @param list<string>          $args
     * @param array<string, string> $assoc
     */
    public function export(array $args, array $assoc): void
    {
        $planId = (int) ($args[0] ?? 0);
        $result = $this->exporter->export($planId);

        foreach ($result->warnings as $warning) {
            WP_CLI::warning($warning);
        }

        if ($result->problems !== []) {
            foreach ($result->problems as $problem) {
                WP_CLI::log($problem);
            }

            WP_CLI::error(sprintf('Plan %d was not exported: %s.', $planId, $this->count(count($result->problems), 'problem', 'problems')));
        }

        if (!isset($assoc['file'])) {
            // The bytes exactly; WP_CLI::line() would add its own newline.
            fwrite(STDOUT, $result->json);

            return;
        }

        $path = (string) $assoc['file'];

        if (file_put_contents($path, $result->json) === false) {
            WP_CLI::error(sprintf('Could not write %s. In Docker, write under /plans (the repo\'s content/plans/ folder).', $path));
        }

        WP_CLI::success(sprintf('Plan %d exported to %s.', $planId, $path));
    }

    /**
     * Imports a Plan file as a new Training Plan.
     *
     * Always creates a new Plan, as a draft unless --status says otherwise; it
     * never updates an existing one. The whole file is checked first and every
     * problem is listed; a file with any problem imports nothing. Every
     * Exercise in the file must exist and be published on this site.
     *
     * ## OPTIONS
     *
     * <file>
     * : The Plan file, as a path inside the container, or - to read stdin. In Docker, /plans is the repo's content/plans/ folder and /plan-fixtures is tests/plan-files/.
     *
     * [--dry-run]
     * : Check the file and report what would be created, without writing anything.
     *
     * [--status=<status>]
     * : Status of the new Plan. publish is for reviewing it in the Portal locally.
     * ---
     * default: draft
     * options:
     *   - draft
     *   - publish
     * ---
     *
     * ## EXAMPLES
     *
     *     wp ol-plans import-plan /plans/home-hypertrophy.json --dry-run
     *     wp ol-plans import-plan /plans/home-hypertrophy.json
     *     wp ol-plans import-plan /plan-fixtures/full.json --status=publish
     *
     * @param list<string>               $args
     * @param array<string, string|bool> $assoc
     */
    public function import(array $args, array $assoc): void
    {
        $path   = (string) ($args[0] ?? '');
        $status = (string) ($assoc['status'] ?? 'draft');

        if (!in_array($status, PlanFileImporter::STATUSES, true)) {
            WP_CLI::error(sprintf('--status must be one of: %s.', implode(', ', PlanFileImporter::STATUSES)));
        }

        $report = $this->importer->run(PlanFile::parse($this->readFile($path)), !empty($assoc['dry-run']), $status);

        if (!$report->succeeded()) {
            foreach ($report->problems as $problem) {
                WP_CLI::log($problem);
            }

            WP_CLI::error(sprintf('Nothing was imported: %s.', $this->count(count($report->problems), 'problem', 'problems')));
        }

        foreach ($report->notes as $note) {
            WP_CLI::warning($note);
        }

        if ($report->dryRun) {
            WP_CLI::success(sprintf('Ready to import: %s. Dry run: nothing was written.', $this->counts($report)));

            return;
        }

        WP_CLI::success(sprintf(
            'Plan %d created %s: %s.',
            $report->planId,
            $report->status === 'publish' ? 'and published' : 'as a draft',
            $this->counts($report)
        ));
        WP_CLI::log($report->editUrl);
    }

    private function readFile(string $path): string
    {
        if ($path === '-') {
            return (string) stream_get_contents(STDIN);
        }

        if (!is_file($path)) {
            WP_CLI::error(sprintf(
                '%s does not exist. The path is inside the container: Plan files in the repo\'s content/plans/ are under /plans, and the test fixtures in tests/plan-files/ under /plan-fixtures.',
                $path
            ));
        }

        $bytes = file_get_contents($path);

        if ($bytes === false) {
            WP_CLI::error(sprintf('Could not read %s.', $path));
        }

        return $bytes;
    }

    private function counts(PlanFileReport $report): string
    {
        $c = $report->counts;

        return implode(', ', [
            $this->count($c['weeks'], 'Week', 'Weeks'),
            $this->count($c['workouts'], 'Workout', 'Workouts'),
            $this->count($c['prescriptions'], 'Prescription', 'Prescriptions'),
            $this->count($c['exercises'], 'Exercise', 'Exercises'),
        ]);
    }

    private function count(int $count, string $singular, string $plural): string
    {
        return $count . ' ' . ($count === 1 ? $singular : $plural);
    }
}
