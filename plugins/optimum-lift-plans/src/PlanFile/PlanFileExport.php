<?php

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

/**
 * What exporting a Plan gave: its content and the file's bytes, or the
 * problems that stopped the export. Warnings never stop it.
 */
final readonly class PlanFileExport
{
    /**
     * @param list<string> $problems
     * @param list<string> $warnings
     */
    public function __construct(
        public ?PlanContent $content,
        public string $json,
        public array $problems,
        public array $warnings,
    ) {
    }
}
