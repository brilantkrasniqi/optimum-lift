<?php

/**
 * Writes a Training Plan as a Plan file. Reads the raw ACF rows, not
 * PlanRepository, which leaves out Prescriptions whose Exercise is not
 * published: an export must never lose a row silently.
 *
 * The importer reads a new Plan back through read() to prove it was written
 * as the file says.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

use OptimumLift\Plans\Content\LibraryKeys;
use OptimumLift\Plans\Content\PostTypes;
use OptimumLift\Plans\Library\LibraryFile;
use WP_Post;

final class PlanFileExporter
{
    /** @var array<string, true>|null Keys of the Exercise library bundled with the plugin. */
    private ?array $bundledKeys = null;

    /**
     * The Plan's content, with the problems that make it impossible to write
     * as a file and warnings about Exercises another site may not have.
     */
    public function read(int $planId): PlanFileExport
    {
        $post = get_post($planId);

        if (!$post instanceof WP_Post || $post->post_type !== PostTypes::PLAN) {
            /* translators: %d: post ID */
            return new PlanFileExport(null, '', [sprintf(__('There is no Training Plan with ID %d.', 'optimum-lift-plans'), $planId)], []);
        }

        if ($post->post_status === 'trash') {
            /* translators: %d: post ID */
            return new PlanFileExport(null, '', [sprintf(__('Training Plan %d is in the trash.', 'optimum-lift-plans'), $planId)], []);
        }

        $problems  = [];
        $exercises = [];
        $places    = [];
        $phases    = [];
        $weeks     = [];

        foreach ($this->rows(get_field('phases', $planId)) as $row) {
            $phases[] = new PhaseRow(
                sanitize_text_field($this->text($row['name'] ?? '')),
                (int) ($row['first_week'] ?? 0),
                (int) ($row['last_week'] ?? 0),
            );
        }

        foreach ($this->rows(get_field('weeks', $planId)) as $w => $weekRow) {
            $workouts = [];

            foreach ($this->rows($weekRow['workouts'] ?? null) as $o => $workoutRow) {
                $name          = sanitize_text_field($this->text($workoutRow['name'] ?? ''));
                $prescriptions = [];

                foreach ($this->rows($workoutRow['prescriptions'] ?? null) as $p => $row) {
                    $place      = Places::prescription($w + 1, $o + 1, $name, $p + 1);
                    $exerciseId = (int) ($row['exercise'] ?? 0);

                    if (!array_key_exists($exerciseId, $exercises)) {
                        $exercises[$exerciseId] = $this->exercise($exerciseId);
                    }

                    $exercise = $exercises[$exerciseId];

                    if ($exercise === null) {
                        $problems[] = Places::at($place, __('the Exercise no longer exists. Choose another one in wp-admin.', 'optimum-lift-plans'));
                        continue;
                    }

                    if ($exercise['key'] === '') {
                        /* translators: %s: Exercise name */
                        $problems[] = Places::at($place, sprintf(__('the Exercise "%s" has no library key. Save that Exercise once in wp-admin to give it one.', 'optimum-lift-plans'), $exercise['title']));
                        continue;
                    }

                    $places[$exerciseId][] = $place;

                    $rest = $row['rest_seconds'] ?? null;

                    $prescriptions[] = new PrescriptionRow(
                        $exercise['key'],
                        (int) ($row['sets'] ?? 0),
                        ($row['target_type'] ?? '') === 'seconds' ? 'seconds' : 'reps',
                        sanitize_text_field($this->text($row['target'] ?? '')),
                        sanitize_text_field($this->text($row['intensity'] ?? '')),
                        is_numeric($rest) ? (int) $rest : null,
                        PlanFile::multiline($this->text($row['notes'] ?? '')),
                    );
                }

                $workouts[] = new WorkoutRow($name, $prescriptions);
            }

            $weeks[] = new WeekRow($workouts);
        }

        if ($problems !== []) {
            return new PlanFileExport(null, '', $problems, []);
        }

        $content = new PlanContent(
            sanitize_text_field($post->post_title),
            PlanFile::multiline($this->text(get_field('summary', $planId))),
            sanitize_text_field($this->text(get_field('goal', $planId))),
            sanitize_text_field($this->text(get_field('target_audience', $planId))),
            $this->text(get_field('difficulty', $planId)),
            $phases,
            $weeks,
        );

        return new PlanFileExport($content, '', [], $this->warnings(array_filter($exercises), $places));
    }

    /**
     * The Plan file's bytes, unless a problem stops the export.
     */
    public function export(int $planId): PlanFileExport
    {
        $read = $this->read($planId);

        if ($read->content === null) {
            return $read;
        }

        $json  = PlanFile::encode($read->content);
        $check = PlanFile::parse($json);

        // Stored values the file format does not allow, e.g. a Plan built in
        // code with an empty Week. The problems say where, in author terms.
        if ($check->content === null) {
            return new PlanFileExport(null, '', $check->problems, $read->warnings);
        }

        if (PlanFile::encode($check->content) !== $json) {
            throw new \LogicException(sprintf('The Plan file exported from Plan %d does not read back the same.', $planId));
        }

        return new PlanFileExport($read->content, $json, [], $read->warnings);
    }

    /**
     * A file name for the Plan: its slug, or one made from its title while it
     * is a draft without a slug.
     */
    public function fileName(int $planId): string
    {
        $post = get_post($planId);
        $slug = $post instanceof WP_Post ? ($post->post_name !== '' ? $post->post_name : sanitize_title($post->post_title)) : '';

        return sanitize_file_name(($slug !== '' ? $slug : 'training-plan-' . $planId) . '.json');
    }

    /**
     * @return array{key: string, title: string, status: string}|null
     */
    private function exercise(int $exerciseId): ?array
    {
        $post = $exerciseId > 0 ? get_post($exerciseId) : null;

        if (!$post instanceof WP_Post || $post->post_type !== PostTypes::EXERCISE) {
            return null;
        }

        return [
            'key'    => $this->text(get_post_meta($exerciseId, LibraryKeys::META, true)),
            'title'  => $post->post_title,
            'status' => $post->post_status,
        ];
    }

    /**
     * One warning per Exercise the importing site may not have, naming every
     * place it is used.
     *
     * @param array<int, array{key: string, title: string, status: string}> $exercises
     * @param array<int, list<string>>                                     $places    Exercise ID => places it is used.
     * @return list<string>
     */
    private function warnings(array $exercises, array $places): array
    {
        $warnings = [];

        foreach ($exercises as $exerciseId => $exercise) {
            $published = $exercise['status'] === 'publish';
            $inLibrary = isset($this->bundledKeys()[$exercise['key']]);

            if ($published && $inLibrary) {
                continue;
            }

            $warnings[] = sprintf(
                match (true) {
                    /* translators: 1: Exercise name, 2: library key, 3: places in the Plan */
                    !$published && !$inLibrary => __('"%1$s" (%2$s) is not published and not in the Exercise library, so the site you import into must have it, published. Used in %3$s.', 'optimum-lift-plans'),
                    /* translators: 1: Exercise name, 2: library key, 3: places in the Plan */
                    !$published => __('"%1$s" (%2$s) is not published, so the Portal and the PDF leave it out, and the site you import into must have it published. Used in %3$s.', 'optimum-lift-plans'),
                    /* translators: 1: Exercise name, 2: library key, 3: places in the Plan */
                    default => __('"%1$s" (%2$s) is not in the Exercise library, so the site you import into must have it too. Used in %3$s.', 'optimum-lift-plans'),
                },
                $exercise['title'],
                $exercise['key'],
                Places::list($places[$exerciseId] ?? [])
            );
        }

        return $warnings;
    }

    /**
     * @return array<string, true>
     */
    private function bundledKeys(): array
    {
        if ($this->bundledKeys === null) {
            $this->bundledKeys = [];

            foreach (LibraryFile::bundled()->entries as $entry) {
                $this->bundledKeys[$entry->key] = true;
            }
        }

        return $this->bundledKeys;
    }

    /**
     * ACF returns false for an empty repeater.
     *
     * @return list<array<string, mixed>>
     */
    private function rows(mixed $rows): array
    {
        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    private function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
