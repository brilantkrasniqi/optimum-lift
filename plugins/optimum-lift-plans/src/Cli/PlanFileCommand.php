<?php

/**
 * `wp ol-plans export-plan` and `wp ol-plans import-plan`: Plan files from the
 * command line, for local work and for Claude. wp-admin runs the same exporter
 * and importer. See docs/plan-files.md.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Cli;

use OptimumLift\Plans\PlanFile\PlanFileExporter;
use WP_CLI;

final class PlanFileCommand
{
    public function __construct(private readonly PlanFileExporter $exporter)
    {
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

    private function count(int $count, string $singular, string $plural): string
    {
        return $count . ' ' . ($count === 1 ? $singular : $plural);
    }
}
