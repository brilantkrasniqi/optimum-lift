<?php

declare(strict_types=1);

namespace OptimumLift\Plans\Library;

/**
 * One Exercise as the library data file describes it. Values are Choices keys;
 * `image` and `animation` are paths relative to the file's folder.
 */
final readonly class LibraryEntry
{
    /**
     * @param list<string> $secondaryMuscles
     * @param list<string> $equipment
     * @param list<string> $settings
     */
    public function __construct(
        public string $key,
        public string $sourceId,
        public string $name,
        public string $primaryMuscle,
        public array $secondaryMuscles,
        public array $equipment,
        public string $difficulty,
        public string $pattern,
        public array $settings,
        public string $instructions,
        public string $image,
        public string $animation,
    ) {
    }
}
