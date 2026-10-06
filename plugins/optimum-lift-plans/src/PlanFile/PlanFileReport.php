<?php

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

/**
 * What importing a Plan file did, or would do in a dry run. Problems mean
 * nothing was kept; notes never stop an import.
 */
final class PlanFileReport
{
    public ?int $planId = null;

    public string $editUrl = '';

    /** @var array{weeks: int, workouts: int, prescriptions: int, exercises: int} */
    public array $counts = ['weeks' => 0, 'workouts' => 0, 'prescriptions' => 0, 'exercises' => 0];

    /** @var list<string> */
    public array $problems = [];

    /** @var list<string> */
    public array $notes = [];

    public function __construct(
        public readonly bool $dryRun,
        public readonly string $status,
    ) {
    }

    public function succeeded(): bool
    {
        return $this->problems === [];
    }
}
