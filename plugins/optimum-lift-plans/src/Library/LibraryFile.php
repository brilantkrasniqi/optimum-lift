<?php

/**
 * The Exercise library data file (format version 1, written by
 * tools/build-exercise-library.mjs), read and checked as a whole. Nothing may
 * be imported from a file with problems, so a broken file never half-imports.
 *
 * Problems are developer-facing (the shipped file is broken), so they are not
 * translated.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Library;

use OptimumLift\Plans\Content\Choices;
use OptimumLift\Plans\Content\LibraryKeys;

use const OptimumLift\Plans\DIR;

final class LibraryFile
{
    public const VERSION = 1;

    /**
     * @param list<LibraryEntry> $entries
     * @param list<string>       $problems
     */
    private function __construct(
        public readonly string $dir,
        public readonly array $entries,
        public readonly array $problems,
    ) {
    }

    public static function bundled(): self
    {
        return self::read(DIR . '/data/exercises/exercises.json');
    }

    public static function read(string $path): self
    {
        $dir = dirname($path);

        if (!is_file($path)) {
            return new self($dir, [], [sprintf('%s does not exist.', $path)]);
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new self($dir, [], [sprintf('%s is not valid JSON: %s', $path, $e->getMessage())]);
        }

        if (!is_array($data) || ($data['version'] ?? null) !== self::VERSION) {
            return new self($dir, [], [sprintf('%s is not a version %d library file.', $path, self::VERSION)]);
        }

        if (!is_array($data['exercises'] ?? null) || !array_is_list($data['exercises'])) {
            return new self($dir, [], ['"exercises" must be a list.']);
        }

        $keys     = new LibraryKeys();
        $entries  = [];
        $problems = [];
        $seenKeys = [];
        $seenName = [];

        foreach ($data['exercises'] as $index => $row) {
            $where = sprintf('Entry %d', $index + 1);

            if (!is_array($row)) {
                $problems[] = $where . ' is not an object.';
                continue;
            }

            $found = [];
            $key   = self::text($row, 'key', $found);
            $where = $key !== '' ? sprintf('%s (%s)', $where, $key) : $where;

            $entry = new LibraryEntry(
                $key,
                self::text($row, 'source_id', $found),
                self::text($row, 'name', $found),
                self::choice($row, 'primary_muscle', Choices::muscles(), $found),
                self::choices($row, 'secondary_muscles', Choices::muscles(), $found),
                self::choices($row, 'equipment', Choices::equipment(), $found),
                self::choice($row, 'difficulty', Choices::difficulty(), $found),
                self::choice($row, 'pattern', Choices::patterns(), $found),
                self::choices($row, 'settings', Choices::settings(), $found),
                self::text($row, 'instructions', $found),
                self::media($row, 'image', $dir, ['jpg', 'jpeg', 'png'], $found),
                self::media($row, 'animation', $dir, ['gif', 'webp'], $found),
            );

            if ($key !== '' && !$keys->isValid($key)) {
                $found[] = sprintf('key "%s" is not lowercase letters and digits joined by hyphens', $key);
            }

            if ($key !== '' && isset($seenKeys[$key])) {
                $found[] = sprintf('key also used by entry %d', $seenKeys[$key]);
            }

            $name = mb_strtolower($entry->name);

            if ($name !== '' && isset($seenName[$name])) {
                $found[] = sprintf('name also used by entry %d', $seenName[$name]);
            }

            if ($found !== []) {
                $problems[] = $where . ': ' . implode('; ', $found) . '.';
                continue;
            }

            $seenKeys[$key]  = $index + 1;
            $seenName[$name] = $index + 1;
            $entries[]       = $entry;
        }

        return new self($dir, $problems === [] ? $entries : [], $problems);
    }

    /**
     * @param array<mixed> $row
     * @param list<string> $found
     */
    private static function text(array $row, string $field, array &$found): string
    {
        $value = $row[$field] ?? null;

        if (!is_string($value) || trim($value) === '') {
            $found[] = sprintf('"%s" is missing or empty', $field);

            return '';
        }

        return trim($value);
    }

    /**
     * @param array<mixed>          $row
     * @param array<string, string> $choices
     * @param list<string>          $found
     */
    private static function choice(array $row, string $field, array $choices, array &$found): string
    {
        $value = $row[$field] ?? null;

        if (!is_string($value) || !isset($choices[$value])) {
            $found[] = sprintf('"%s" is not one of the plugin\'s choices (%s)', $field, is_scalar($value) ? (string) $value : gettype($value));

            return '';
        }

        return $value;
    }

    /**
     * @param array<mixed>          $row
     * @param array<string, string> $choices
     * @param list<string>          $found
     * @return list<string>
     */
    private static function choices(array $row, string $field, array $choices, array &$found): array
    {
        $values = $row[$field] ?? null;

        if (!is_array($values) || !array_is_list($values)) {
            $found[] = sprintf('"%s" must be a list', $field);

            return [];
        }

        $unknown = array_filter($values, static fn (mixed $v): bool => !is_string($v) || !isset($choices[$v]));

        if ($unknown !== []) {
            $found[] = sprintf('"%s" has values that are not the plugin\'s choices (%s)', $field, implode(', ', array_map('strval', array_filter($unknown, 'is_scalar'))));

            return [];
        }

        /** @var list<string> $values */
        return array_values(array_unique($values));
    }

    /**
     * @param array<mixed> $row
     * @param list<string> $extensions
     * @param list<string> $found
     */
    private static function media(array $row, string $field, string $dir, array $extensions, array &$found): string
    {
        $value = $row[$field] ?? null;

        if (!is_string($value) || preg_match('#^media/[A-Za-z0-9][A-Za-z0-9._-]*$#', $value) !== 1) {
            $found[] = sprintf('"%s" must be a file name under media/', $field);

            return '';
        }

        if (!in_array(strtolower(pathinfo($value, PATHINFO_EXTENSION)), $extensions, true)) {
            $found[] = sprintf('"%s" must be one of: %s', $field, implode(', ', $extensions));

            return '';
        }

        if (!is_file($dir . '/' . $value)) {
            $found[] = sprintf('%s does not exist', $value);

            return '';
        }

        return $value;
    }
}
