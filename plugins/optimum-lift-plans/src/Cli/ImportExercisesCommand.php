<?php

/**
 * `wp ol-plans import-exercises`: imports the Exercise library bundled with the
 * plugin. The wp-admin import screen runs the same importer.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Cli;

use OptimumLift\Plans\Library\ExerciseImporter;
use OptimumLift\Plans\Library\ImportOptions;
use OptimumLift\Plans\Library\ImportReport;
use OptimumLift\Plans\Library\LibraryFile;
use WP_CLI;

final class ImportExercisesCommand
{
    private const BATCH = 25;

    public function __construct(private readonly ExerciseImporter $importer)
    {
    }

    /**
     * Imports the Exercise library bundled with the plugin.
     *
     * Creates missing Exercises with their image and animation, adopts an
     * existing Exercise of the same name, and fills in empty fields. A filled
     * field is never overwritten unless --update is given. Afterwards every
     * Exercise has a library key.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Report what would happen and write nothing.
     *
     * [--update]
     * : Overwrite fields of existing Exercises that differ from the library.
     *
     * [--fields=<fields>]
     * : Comma-separated fields to fill in or overwrite on existing Exercises: name, primary_muscle, secondary_muscles, equipment, difficulty, pattern, settings, instructions, image, animation. Default: all.
     *
     * [--file=<path>]
     * : A library file other than the bundled one.
     *
     * ## EXAMPLES
     *
     *     wp ol-plans import-exercises --dry-run
     *     wp ol-plans import-exercises
     *     wp ol-plans import-exercises --update --fields=instructions
     *
     * @param list<string>               $args
     * @param array<string, string|bool> $assoc
     */
    public function __invoke(array $args, array $assoc): void
    {
        $fields = ImportOptions::FIELDS;

        if (isset($assoc['fields'])) {
            $fields  = array_values(array_filter(array_map('trim', explode(',', (string) $assoc['fields']))));
            $unknown = array_diff($fields, ImportOptions::FIELDS);

            if ($fields === [] || $unknown !== []) {
                WP_CLI::error(sprintf(
                    'Unknown --fields value "%s". Choose from: %s.',
                    implode(', ', $unknown),
                    implode(', ', ImportOptions::FIELDS)
                ));
            }
        }

        $options = new ImportOptions(!empty($assoc['dry-run']), !empty($assoc['update']), $fields);
        $file    = isset($assoc['file']) ? LibraryFile::read((string) $assoc['file']) : LibraryFile::bundled();

        if ($file->problems !== []) {
            foreach ($file->problems as $problem) {
                WP_CLI::warning($problem);
            }

            WP_CLI::error(sprintf('%d problem(s) in the library file. Nothing was imported.', count($file->problems)));
        }

        $total  = count($file->entries);
        $report = new ImportReport($options->dryRun);

        if ($options->dryRun) {
            $report = $this->importer->run($file, $options);
        } else {
            $offset = 0;

            do {
                $batch = $this->importer->run($file, $options, $offset, self::BATCH);

                if ($batch->locked) {
                    WP_CLI::error(implode(' ', $batch->errors));
                }

                $report->merge($batch);
                $offset += self::BATCH;
                WP_CLI::log(sprintf('%d/%d', min($offset, $total), $total));
            } while ($offset < $total);
        }

        foreach ($report->notes as $note) {
            WP_CLI::log($note);
        }

        foreach ($report->errors as $error) {
            WP_CLI::warning($error);
        }

        WP_CLI::log($this->summary($report));

        if ($report->errors !== []) {
            WP_CLI::error('Finished with errors. Running it again retries what failed.');
        }

        WP_CLI::success($options->dryRun ? 'Dry run: nothing was written.' : 'Exercise library imported.');
    }

    private function summary(ImportReport $report): string
    {
        $c = $report->counts;

        return sprintf(
            '%sExercises: %d created, %d adopted, %d filled in, %d updated, %d skipped, %d in the trash. Keys given to other Exercises: %d. Media: %d added, %d reused.',
            $report->dryRun ? 'Would do. ' : '',
            $c['created'],
            $c['adopted'],
            $c['filled'],
            $c['updated'],
            $c['skipped'],
            $c['in_trash'],
            $c['keys_added'],
            $c['media_added'],
            $c['media_reused']
        );
    }
}
