<?php

declare(strict_types=1);

namespace OptimumLift\Plans\Library;

/**
 * What an import did, or would do in a dry run. Batches of one import are
 * merged into one report.
 */
final class ImportReport
{
    /**
     * created:      new Exercises.
     * adopted:      existing Exercises without a key, matched by name, that got the library key.
     * filled:       existing library Exercises that had empty fields filled in.
     * updated:      existing library Exercises overwritten by --update.
     * skipped:      existing library Exercises with nothing to do.
     * in_trash:     library Exercises in the trash, left alone.
     * keys_added:   Exercises outside the library that got a key from their title.
     * media_added:  files added to the Media Library.
     * media_reused: files already in the Media Library from an earlier import.
     *
     * @var array<string, int>
     */
    public array $counts = [
        'created'      => 0,
        'adopted'      => 0,
        'filled'       => 0,
        'updated'      => 0,
        'skipped'      => 0,
        'in_trash'     => 0,
        'keys_added'   => 0,
        'media_added'  => 0,
        'media_reused' => 0,
    ];

    /** @var list<string> */
    public array $notes = [];

    /** @var list<string> */
    public array $errors = [];

    /** Another import held the lock, so this run did nothing. */
    public bool $locked = false;

    public function __construct(public readonly bool $dryRun)
    {
    }

    public function add(string $count, int $by = 1): void
    {
        $this->counts[$count] = ($this->counts[$count] ?? 0) + $by;
    }

    public function merge(self $other): void
    {
        foreach ($other->counts as $count => $value) {
            $this->add($count, $value);
        }

        $this->notes  = [...$this->notes, ...$other->notes];
        $this->errors = [...$this->errors, ...$other->errors];
        $this->locked = $this->locked || $other->locked;
    }
}
