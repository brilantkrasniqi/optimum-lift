# Importer and `wp ol-plans import-plan`

Type: task
Status: ready-for-agent
Blocked by: 01, 02

## What to build

`src/PlanFile/PlanFileImporter.php`, `src/PlanFile/PlanFileReport.php`, and the import half of `PlanFileCommand`.

- `run(PlanFile $file, bool $dryRun, string $status = 'draft'): PlanFileReport`. Resolve Exercises (ticket 01) first; any problem means nothing is written.
- Write path, the same as `SeedCommand`: `wp_insert_post()` (`ol_training_plan`, given status, title) with `true` for `WP_Error`, then `update_field()` with **field keys** for summary, goal, target audience, difficulty, then `phases`, then `weeks` (nested rows keyed by `field_ol_week_workouts`, `field_ol_workout_name`, `field_ol_workout_prescriptions`, `field_ol_prescription_*`). Do not write uids; `PlanFields` assigns them.
- If `wp_insert_post()` returns an error or any `update_field()` returns false for a non-empty value, delete the post with `wp_delete_post($id, true)` and report the failure.
- Report: new Plan ID, edit URL, counts (Weeks, Workouts, Prescriptions, distinct Exercises), notes (a published or draft Plan with the same title already exists: give its ID), problems.
- CLI: `wp ol-plans import-plan <file> [--dry-run] [--status=<draft|publish>]`. Print problems one per line then `WP_CLI::error()`; on success `WP_CLI::success('Plan 123 created as a draft: 10 Weeks, 30 Workouts, 180 Prescriptions, 24 Exercises.')` and the edit URL. The path is read inside the container, and `content/` is not mounted there today (checked `docker-compose.yml`). Add a read-only bind mount `./content/plans:/plans:ro` to the `wpcli` service, so `docker compose --profile cli run --rm wpcli ol-plans import-plan /plans/x.json` works. Also accept `-` for stdin (`... run --rm -T wpcli ol-plans import-plan - < file.json`) for files kept elsewhere. Export goes the other way: `... run --rm wpcli ol-plans export-plan 123 > content/plans/x.json` (stdout, so no writable mount is needed).

## Acceptance criteria

- [ ] Round trip: export the demo Plan, import it, export the new Plan; `diff` is empty after removing the `exported_at` and `exported_from` lines.
- [ ] The imported Plan is a draft; every Workout and Prescription has a UUID uid, no uid repeats within the Plan, and none equals a uid of the source Plan.
- [ ] Opening the imported Plan in wp-admin and pressing Update without changes keeps every row and every uid.
- [ ] After publishing, the Portal (as a user with Access) and the PDF preview show it the same as the source Plan.
- [ ] `--dry-run` reports the same counts and writes nothing (`wp post list --post_type=ol_training_plan --post_status=any --format=count` unchanged).
- [ ] The broken file from ticket 01 imports nothing and prints every problem.
- [ ] Importing the same file twice gives two independent Plans and a "same title" note the second time.
- [ ] `npm run lint:php` and `npm run analyse:php` pass.

## Comments
