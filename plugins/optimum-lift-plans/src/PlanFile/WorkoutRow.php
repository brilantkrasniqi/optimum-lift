<?php

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

final readonly class WorkoutRow
{
    /**
     * @param list<PrescriptionRow> $prescriptions
     */
    public function __construct(
        public string $name,
        public array $prescriptions,
    ) {
    }

    /**
     * @return array{name: string, prescriptions: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'name'          => $this->name,
            'prescriptions' => array_map(static fn (PrescriptionRow $row): array => $row->toArray(), $this->prescriptions),
        ];
    }
}
