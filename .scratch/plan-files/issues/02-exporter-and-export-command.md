# Exporter and `wp ol-plans export-plan`

Type: task
Status: claimed
Blocked by: 01

## What to build

**`src/PlanFile/PlanFileExporter.php`**:
- `read(int $planId)`: the Plan's content in the normalised structure from ticket 01, read from raw ACF rows with `get_field()` (`summary`, `goal`, `target_audience`, `difficulty`, `phases`, `weeks`). **Not** `PlanRepository`, which drops Prescriptions with unpublished Exercises. Exercise IDs become library keys (`LibraryKeys::META`); numbers become `int`; empty rest becomes `null`; an empty difficulty (ACF may return `null` or `false`) becomes `""`. Ticket 03's read-back uses this same method.
- `export(int $planId)`: a result with the JSON string, `problems` and `warnings`.
  - Problems (no file): the post is missing, trashed or not an `ol_training_plan`; a Prescription's Exercise post no longer exists or has no key. List every such row by Week, Workout and Prescription.
  - Warnings (file still written): an Exercise is not published, or its key is not in `LibraryFile::bundled()`. One warning per Exercise, with every place it is used: `"Seated cable row" (seated-cable-row) is not in the Exercise library, so the site you import into must have it too. Used in Week 1, Workout 2, Prescription 4; …`
  - Encoding as the spec says (property order of the table, pretty print, LF, trailing newline). No timestamps or URLs.
  - Self-check: parse the output with `PlanFile` (format checks only) before returning; a problem there is an exporter bug, so throw.

**`src/Cli/PlanFileCommand.php`**, export half, registered in `Plugin::boot()` as `ol-plans export-plan` (follow `ImportExercisesCommand`'s shape and docblock style, with `## OPTIONS` and `## EXAMPLES` using the `/plans` path):
- `wp ol-plans export-plan <plan-id> [--file=<path>]`. Without `--file`, write the bytes to stdout with `fwrite(STDOUT, …)`, not `WP_CLI::line()`, so nothing is added. With `--file`, write the file and `WP_CLI::success('Plan 123 exported to /plans/x.json.')`. Warnings via `WP_CLI::warning()` (stderr). Problems: list them, then `WP_CLI::error()`.
- If a WP-CLI method is missing from `phpstan-wp-cli-stub.php`, add it there.

## Acceptance criteria

- [ ] `export-plan <demo-plan-id> --file=/plans/demo.json` on a freshly seeded site writes a file `PlanFile` accepts, with no warnings (the demo uses library keys only).
- [ ] Exporting the same unchanged Plan twice gives byte-identical files (`Get-FileHash` / `sha256sum`).
- [ ] A Plan with one Prescription pointing at a hand-made, non-library Exercise exports, with one warning naming that Exercise and its place.
- [ ] A Plan whose Exercise was deleted (`wp post delete <id> --force`) refuses to export and names the Week, Workout and Prescription.
- [ ] Phases, empty intensity, empty rest, non-ASCII text and multi-line notes come out as the spec says.
- [ ] `npm run lint:php` and `npm run analyse:php` pass.

## Comments
