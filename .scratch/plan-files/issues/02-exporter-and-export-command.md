# Exporter and `wp ol-plans export-plan`

Type: task
Status: ready-for-agent
Blocked by: 01

## What to build

`src/PlanFile/PlanFileExporter.php` and the export half of `src/Cli/PlanFileCommand.php`.

- Input: a Plan post ID, any status except trash. Read `summary`, `goal`, `target_audience`, `difficulty`, and the raw `phases` and `weeks` rows with `get_field()`. Do **not** use `PlanRepository`: it drops Prescriptions with unpublished Exercises.
- Each Prescription's Exercise ID becomes its library key (`LibraryKeys::META`). Exercise missing, or without a key: a problem naming the row; refuse the export and list every such row.
- Warnings (file still written): the Exercise is not published; its key is not in `LibraryFile::bundled()` (so production will only have it if someone made it there by hand). One warning per Exercise, listing where it is used.
- Write every property, defaults included, in the spec's order; `exported_at` as UTC ISO 8601, `exported_from` as `home_url()`. Encode with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` plus a trailing newline. Empty `rest_seconds` is `null`; numbers are integers, not strings (ACF returns strings).
- Self-check: before returning, parse the output with `PlanFile` (format checks only) and treat any problem as an exporter bug.
- CLI: `wp ol-plans export-plan <plan-id> [--file=<path>]`. Stdout by default; warnings go to stderr via `WP_CLI::warning()` so `> file.json` stays clean. Unknown ID or wrong post type is `WP_CLI::error()`.

## Acceptance criteria

- [ ] `wp ol-plans export-plan <demo-plan-id>` on a freshly seeded site prints a file that `PlanFile` accepts, with no warnings (the demo uses library keys only).
- [ ] Two exports of the same unchanged Plan differ only in `exported_at`.
- [ ] A Plan with a Prescription pointing at a hand-made, non-library Exercise exports with one warning naming that Exercise.
- [ ] A Plan whose Exercise was deleted refuses to export and names the Week, Workout and Prescription.
- [ ] Phases, empty intensity, empty rest and multi-line notes survive as the spec says.
- [ ] `npm run lint:php` and `npm run analyse:php` pass.

## Comments
