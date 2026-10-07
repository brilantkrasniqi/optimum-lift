<?php

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

final readonly class PrescriptionRow
{
    /**
     * @param string $exercise The Exercise's library key.
     */
    public function __construct(
        public string $exercise,
        public int $sets,
        public string $targetType,
        public string $target,
        public string $intensity,
        public ?int $restSeconds,
        public string $notes,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'exercise'     => $this->exercise,
            'sets'         => $this->sets,
            'target_type'  => $this->targetType,
            'target'       => $this->target,
            'intensity'    => $this->intensity,
            'rest_seconds' => $this->restSeconds,
            'notes'        => $this->notes,
        ];
    }
}
