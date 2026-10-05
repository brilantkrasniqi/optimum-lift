<?php

declare(strict_types=1);

namespace OptimumLift\Plans\Library;

/**
 * How an import treats Exercises that already exist. A new Exercise always
 * gets every field; `fields` limits what is filled in or overwritten on
 * existing ones.
 */
final readonly class ImportOptions
{
    /**
     * Fields the library file owns, named as in the file.
     */
    public const FIELDS = [
        'name',
        'primary_muscle',
        'secondary_muscles',
        'equipment',
        'difficulty',
        'pattern',
        'settings',
        'instructions',
        'image',
        'animation',
    ];

    /**
     * @param list<string> $fields a subset of FIELDS
     */
    public function __construct(
        public bool $dryRun = false,
        public bool $update = false,
        public array $fields = self::FIELDS,
    ) {
    }
}
