<?php

declare(strict_types=1);

namespace OptimumLift\Plans\Plan;

final readonly class Exercise
{
    /**
     * @param list<string> $secondaryMuscles
     * @param list<string> $equipment
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $primaryMuscle,
        public array $secondaryMuscles,
        public array $equipment,
        public string $difficulty,
        public int $imageId,
        public int $animationId,
        public string $videoUrl,
        public string $instructions,
        public string $modifiedGmt,
    ) {
    }
}
