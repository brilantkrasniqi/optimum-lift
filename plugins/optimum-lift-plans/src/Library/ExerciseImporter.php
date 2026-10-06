<?php

/**
 * Imports the Exercise library data file into Exercises.
 *
 * For each entry, in order:
 * - An Exercise holds the entry's key: fill in its empty fields, or with
 *   `update` overwrite the fields that differ. One in the trash is left alone.
 * - Else an Exercise without a key has the entry's name (or a title that makes
 *   the same key): adopt it. It gets the key, then is treated as above.
 * - Else create it, published, with every field.
 *
 * The batch that reaches the end of the file also gives every Exercise still
 * without a key one made from its title, so Plan files can name any Exercise.
 *
 * Media is copied into the Media Library once: each attachment remembers the
 * library file it came from and that file's hash, and later imports reuse it.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Library;

use OptimumLift\Plans\Content\ExerciseFields;
use OptimumLift\Plans\Content\LibraryKeys;
use OptimumLift\Plans\Content\PostTypes;

final class ExerciseImporter
{
    private const SOURCE_ID  = '_ol_source_id';
    private const MEDIA_FILE = '_ol_library_file';
    private const MEDIA_HASH = '_ol_library_hash';
    private const LOCK       = 'ol_exercise_import_lock';
    /** Far longer than a batch takes; a batch's own time limit is 120 s. */
    private const LOCK_SECONDS = 300;

    private const ACF = [
        'primary_muscle'    => ExerciseFields::PRIMARY_MUSCLE,
        'secondary_muscles' => ExerciseFields::SECONDARY_MUSCLES,
        'equipment'         => ExerciseFields::EQUIPMENT,
        'difficulty'        => ExerciseFields::DIFFICULTY,
        'pattern'           => ExerciseFields::MOVEMENT_PATTERN,
        'settings'          => ExerciseFields::SETTINGS,
        'instructions'      => ExerciseFields::INSTRUCTIONS,
        'image'             => ExerciseFields::IMAGE,
        'animation'         => ExerciseFields::ANIMATION,
    ];

    /** @var array<int, true> keyless Exercises adopted in this run */
    private array $adopted = [];

    /** @var array<string, string> */
    private array $hashes = [];

    /** The value of the lock row this run holds, if any. */
    private ?string $lockToken = null;

    public function __construct(private readonly LibraryKeys $keys)
    {
    }

    /**
     * Imports `$limit` entries from `$offset` (all when null). A dry run should
     * cover the whole file in one call: nothing it decides is kept between
     * calls.
     */
    public function run(LibraryFile $file, ImportOptions $options, int $offset = 0, ?int $limit = null): ImportReport
    {
        $report = new ImportReport($options->dryRun);

        if ($file->problems !== []) {
            $report->errors = $file->problems;

            return $report;
        }

        if (!$options->dryRun) {
            if (!$this->lock()) {
                $report->locked   = true;
                $report->errors[] = __('Another import is running. Try again in a minute.', 'optimum-lift-plans');

                return $report;
            }

            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $batch         = array_slice($file->entries, $offset, $limit);
        $this->adopted = [];

        // The images are 180px: the Portal's thumbnail is the only size worth
        // making, and every other size falls back to the original.
        $onlyThumbnail = static fn (array $sizes): array => array_intersect_key($sizes, ['thumbnail' => true]);
        add_filter('intermediate_image_sizes_advanced', $onlyThumbnail);

        try {
            // Read inside the lock: another run may have just written them.
            $keyed   = $this->keyedExercises();
            $keyless = $this->keylessExercises();

            foreach ($batch as $entry) {
                $this->importEntry($entry, $file, $options, $keyed, $keyless, $report);
            }

            if ($offset + count($batch) >= count($file->entries)) {
                $this->addMissingKeys($options, $report);
            }
        } finally {
            remove_filter('intermediate_image_sizes_advanced', $onlyThumbnail);

            if (!$options->dryRun) {
                $this->unlock();
            }
        }

        return $report;
    }

    /**
     * One writing run at a time, from the import screen or WP-CLI: two runs
     * over the same entries would both create the same Exercise. The lock is a
     * row inserted with INSERT IGNORE, which only one request can win. Its
     * value, "{time}:{token}", lets a lock left by a dead request expire after
     * LOCK_SECONDS, and lets a run release only its own lock.
     */
    private function lock(): bool
    {
        global $wpdb;

        $now = time();

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name = %s AND CAST(option_value AS UNSIGNED) < %d",
            self::LOCK,
            $now - self::LOCK_SECONDS
        ));

        $token = $now . ':' . wp_generate_uuid4();
        $won   = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
            self::LOCK,
            $token
        )) === 1;

        if ($won) {
            $this->lockToken = $token;
            // A time or memory limit ends the request without running
            // `finally`; shutdown functions still run, so the lock goes too.
            register_shutdown_function(static function () use ($token): void {
                global $wpdb;

                $wpdb->delete($wpdb->options, ['option_name' => self::LOCK, 'option_value' => $token]);
            });
        }

        return $won;
    }

    private function unlock(): void
    {
        global $wpdb;

        if ($this->lockToken !== null) {
            $wpdb->delete($wpdb->options, ['option_name' => self::LOCK, 'option_value' => $this->lockToken]);
            $this->lockToken = null;
        }
    }

    /**
     * @param array<string, array{id: int, status: string}>       $keyed
     * @param list<array{id: int, title: string, status: string}> $keyless
     */
    private function importEntry(
        LibraryEntry $entry,
        LibraryFile $file,
        ImportOptions $options,
        array $keyed,
        array $keyless,
        ImportReport $report,
    ): void {
        $existing = $keyed[$entry->key] ?? null;

        if ($existing !== null && $existing['status'] === 'trash') {
            $report->add('in_trash');
            /* translators: 1: Exercise name, 2: library key */
            $report->notes[] = sprintf(__('"%1$s" (%2$s) is in the trash and was left alone.', 'optimum-lift-plans'), $entry->name, $entry->key);

            return;
        }

        if ($existing !== null) {
            $changed = $this->complete($existing['id'], $entry, $file, $options, $report);
            $report->add($changed === [] ? 'skipped' : ($options->update ? 'updated' : 'filled'));

            return;
        }

        $match = $this->match($entry, $keyless);

        if ($match !== null) {
            $this->adopted[$match['id']] = true;

            if (!$options->dryRun) {
                update_field(LibraryKeys::FIELD, $entry->key, $match['id']);
                update_post_meta($match['id'], self::SOURCE_ID, $entry->sourceId);

                if (get_post_meta($match['id'], LibraryKeys::META, true) !== $entry->key) {
                    /* translators: 1: post ID, 2: Exercise title, 3: library key */
                    $report->errors[] = sprintf(__('Could not give Exercise %1$d "%2$s" the key %3$s.', 'optimum-lift-plans'), $match['id'], $match['title'], $entry->key);

                    return;
                }
            }

            $this->complete($match['id'], $entry, $file, $options, $report);
            $report->add('adopted');
            /* translators: 1: post ID, 2: Exercise title, 3: library key */
            $report->notes[] = sprintf(__('Took over Exercise %1$d "%2$s" as %3$s.', 'optimum-lift-plans'), $match['id'], $match['title'], $entry->key);

            return;
        }

        $this->create($entry, $file, $options, $report);
    }

    private function create(LibraryEntry $entry, LibraryFile $file, ImportOptions $options, ImportReport $report): void
    {
        if ($options->dryRun) {
            $report->add('created');
            $this->attach($file, $entry->image, 0, $entry->name, true, $report);
            $this->attach($file, $entry->animation, 0, $entry->name, true, $report);

            return;
        }

        $postId = wp_insert_post([
            'post_type'   => PostTypes::EXERCISE,
            'post_status' => 'publish',
            'post_title'  => wp_slash($entry->name),
            // Stored before save_post runs, so LibraryKeys::ensure() keeps this
            // key rather than making one from the title.
            'meta_input'  => [LibraryKeys::META => $entry->key, self::SOURCE_ID => $entry->sourceId],
        ], true);

        if (is_wp_error($postId)) {
            /* translators: 1: Exercise name, 2: error message */
            $report->errors[] = sprintf(__('Could not create "%1$s": %2$s', 'optimum-lift-plans'), $entry->name, $postId->get_error_message());

            return;
        }

        // Writes ACF's field reference too; LibraryKeys::protect() keeps the key.
        update_field(LibraryKeys::FIELD, $entry->key, $postId);

        foreach (ImportOptions::FIELDS as $field) {
            if ($field !== 'name') {
                $this->write($postId, $field, $entry, $file, false, $report);
            }
        }

        $report->add('created');
    }

    /**
     * Fill in the empty fields of an existing Exercise, or with `update`
     * overwrite those that differ from the file.
     *
     * @return list<string> the fields changed
     */
    private function complete(int $postId, LibraryEntry $entry, LibraryFile $file, ImportOptions $options, ImportReport $report): array
    {
        $changed = [];

        foreach ($options->fields as $field) {
            $current = $field === 'name' ? get_post_field('post_title', $postId) : get_field(self::ACF[$field], $postId, false);

            // A media field pointing at an attachment that was deleted shows
            // nothing, so it counts as empty.
            if (($field === 'image' || $field === 'animation') && (!is_numeric($current) || get_post_type((int) $current) !== 'attachment')) {
                $current = null;
            }

            $due = $options->update
                ? !$this->matches($field, $current, $entry, $file)
                : $this->isEmpty($current) && !$this->isEmpty($this->wanted($field, $entry));

            if ($due) {
                $changed[] = $field;
                $this->write($postId, $field, $entry, $file, $options->dryRun, $report);
            }
        }

        return $changed;
    }

    private function write(int $postId, string $field, LibraryEntry $entry, LibraryFile $file, bool $dryRun, ImportReport $report): void
    {
        if ($field === 'image' || $field === 'animation') {
            $attachment = $this->attach($file, $this->media($field, $entry), $postId, $entry->name, $dryRun, $report);

            if (!$dryRun && $attachment > 0) {
                update_field(self::ACF[$field], $attachment, $postId);
            }

            return;
        }

        if ($dryRun) {
            return;
        }

        if ($field === 'name') {
            wp_update_post(['ID' => $postId, 'post_title' => wp_slash($entry->name)]);

            return;
        }

        update_field(self::ACF[$field], $this->wanted($field, $entry), $postId);
    }

    /**
     * The value a field should hold. Media fields hold the file's path here;
     * write() turns it into an attachment.
     *
     * @return string|list<string>
     */
    private function wanted(string $field, LibraryEntry $entry): string|array
    {
        return match ($field) {
            'name'              => $entry->name,
            'primary_muscle'    => $entry->primaryMuscle,
            'secondary_muscles' => $entry->secondaryMuscles,
            'equipment'         => $entry->equipment,
            'difficulty'        => $entry->difficulty,
            'pattern'           => $entry->pattern,
            'settings'          => $entry->settings,
            'instructions'      => '<p>' . esc_html($entry->instructions) . '</p>',
            default             => $this->media($field, $entry),
        };
    }

    private function matches(string $field, mixed $current, LibraryEntry $entry, LibraryFile $file): bool
    {
        if ($field === 'image' || $field === 'animation') {
            $attachment = is_numeric($current) ? (int) $current : 0;

            return $attachment > 0
                && get_post_meta($attachment, self::MEDIA_HASH, true) === $this->hash($file->dir . '/' . $this->media($field, $entry));
        }

        $wanted = $this->wanted($field, $entry);

        if (is_array($wanted)) {
            $have = is_array($current) ? array_map('strval', array_values($current)) : [];
            sort($have);
            sort($wanted);

            return $have === $wanted;
        }

        return is_scalar($current) && trim((string) $current) === $wanted;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === false || $value === '' || $value === [] || $value === 0 || $value === '0';
    }

    private function media(string $field, LibraryEntry $entry): string
    {
        return $field === 'image' ? $entry->image : $entry->animation;
    }

    /**
     * The attachment for a library file: one an earlier import made from the
     * same file, else a new one. 0 in a dry run or on failure.
     */
    private function attach(LibraryFile $file, string $relative, int $postId, string $title, bool $dryRun, ImportReport $report): int
    {
        $path  = $file->dir . '/' . $relative;
        $hash  = $this->hash($path);
        $reuse = get_posts([
            'post_type'        => 'attachment',
            'post_status'      => 'inherit',
            'posts_per_page'   => 1,
            'fields'           => 'ids',
            'orderby'          => 'ID',
            'order'            => 'ASC',
            'no_found_rows'    => true,
            'suppress_filters' => true,
            'meta_query'       => [ // phpcs:ignore -- a one-off lookup during import.
                ['key' => self::MEDIA_FILE, 'value' => $relative],
                ['key' => self::MEDIA_HASH, 'value' => $hash],
            ],
        ]);

        if ($reuse !== []) {
            $report->add('media_reused');

            return (int) $reuse[0];
        }

        if ($dryRun) {
            $report->add('media_added');

            return 0;
        }

        $temp = wp_tempnam(basename($relative));

        if (!copy($path, $temp)) {
            /* translators: %s: media file name */
            $report->errors[] = sprintf(__('Could not copy %s to a temporary file.', 'optimum-lift-plans'), $relative);

            return 0;
        }

        $attachment = media_handle_sideload(
            ['name' => basename($relative), 'tmp_name' => $temp],
            $postId,
            $title,
            ['post_title' => wp_slash($title)]
        );

        if (is_wp_error($attachment)) {
            wp_delete_file($temp);
            /* translators: 1: media file name, 2: error message */
            $report->errors[] = sprintf(__('Could not add %1$s to the Media Library: %2$s', 'optimum-lift-plans'), $relative, $attachment->get_error_message());

            return 0;
        }

        update_post_meta($attachment, '_wp_attachment_image_alt', wp_slash($title));
        update_post_meta($attachment, self::MEDIA_FILE, $relative);
        update_post_meta($attachment, self::MEDIA_HASH, $hash);
        $report->add('media_added');

        return $attachment;
    }

    private function hash(string $path): string
    {
        return $this->hashes[$path] ??= (string) sha1_file($path);
    }

    /**
     * @param list<array{id: int, title: string, status: string}> $keyless
     * @return array{id: int, title: string, status: string}|null
     */
    private function match(LibraryEntry $entry, array $keyless): ?array
    {
        $name = mb_strtolower($entry->name);

        foreach ($keyless as $row) {
            if (isset($this->adopted[$row['id']]) || $row['status'] === 'trash') {
                continue;
            }

            if (mb_strtolower(trim($row['title'])) === $name || $this->keys->slug($row['title']) === $entry->key) {
                return $row;
            }
        }

        return null;
    }

    private function addMissingKeys(ImportOptions $options, ImportReport $report): void
    {
        foreach ($this->keylessExercises() as $row) {
            if (isset($this->adopted[$row['id']])) {
                continue;
            }

            $key = $this->keys->unique($row['title'], $row['id']);

            if ($key === '') {
                /* translators: %d: post ID */
                $report->notes[] = sprintf(__('Exercise %d has no letters or digits in its title, so it still has no key.', 'optimum-lift-plans'), $row['id']);
                continue;
            }

            if (!$options->dryRun) {
                update_field(LibraryKeys::FIELD, $key, $row['id']);
            }

            $report->add('keys_added');
            /* translators: 1: post ID, 2: Exercise title, 3: library key */
            $report->notes[] = sprintf(__('Gave Exercise %1$d "%2$s" the key %3$s.', 'optimum-lift-plans'), $row['id'], $row['title'], $key);
        }
    }

    /**
     * Every keyed Exercise by key. If two share a key, one outside the trash
     * wins, then the oldest.
     *
     * @return array<string, array{id: int, status: string}>
     */
    private function keyedExercises(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT m.meta_value AS library_key, p.ID AS id, p.post_status AS status
             FROM {$wpdb->postmeta} m
             INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
             WHERE p.post_type = %s AND m.meta_key = %s AND m.meta_value <> ''
             ORDER BY p.ID",
            PostTypes::EXERCISE,
            LibraryKeys::META
        ), ARRAY_A);

        $keyed = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $key  = (string) $row['library_key'];
            $seen = $keyed[$key] ?? null;

            if ($seen === null || ($seen['status'] === 'trash' && $row['status'] !== 'trash')) {
                $keyed[$key] = ['id' => (int) $row['id'], 'status' => (string) $row['status']];
            }
        }

        return $keyed;
    }

    /**
     * Exercises without a key, oldest first, including the trash.
     *
     * @return list<array{id: int, title: string, status: string}>
     */
    private function keylessExercises(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID AS id, p.post_title AS title, p.post_status AS status
             FROM {$wpdb->posts} p
             WHERE p.post_type = %s AND p.post_status <> 'auto-draft'
             AND NOT EXISTS (
                 SELECT 1 FROM {$wpdb->postmeta} m
                 WHERE m.post_id = p.ID AND m.meta_key = %s AND m.meta_value <> ''
             )
             ORDER BY p.ID",
            PostTypes::EXERCISE,
            LibraryKeys::META
        ), ARRAY_A);

        return array_map(
            static fn (array $row): array => ['id' => (int) $row['id'], 'title' => (string) $row['title'], 'status' => (string) $row['status']],
            is_array($rows) ? array_values($rows) : []
        );
    }
}
