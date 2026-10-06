# Importer and `wp ol-plans import-plan`

Type: task
Status: claimed
Blocked by: 01, 02

## What to build

**`src/PlanFile/PlanFileImporter.php`** and **`PlanFileReport.php`**:
- `run(PlanFile $file, bool $dryRun, string $status = 'draft'): PlanFileReport`. Resolve Exercises first (ticket 01); any problem means nothing is written.
- Write path, as `SeedCommand`: `wp_insert_post([...], true)` (`ol_training_plan`, status, title), then `update_field()` with **field keys** (the constants from ticket 01) for summary, goal, target audience, difficulty, then `phases`, then `weeks` with nested rows. Do not write uids; `PlanFields` assigns them when `weeks` is written.
- Read-back (spec decision 7): `PlanFileExporter::read($newId)` must equal the file's normalised structure. Wrap the writes and the read-back in `try`/`catch (\Throwable)`. On a `WP_Error`, an exception or a difference: `wp_delete_post($newId, true)` and report a failure that says which part differed (first differing path is enough).
- Report: new Plan ID, edit URL (`get_edit_post_link($id, 'raw')`), counts (Weeks, Workouts, Prescriptions, distinct Exercises), problems, and notes: same title already in use (give the ID); estimated inputs over this site's `max_input_vars` (use `PlanFields`' estimate from ticket 01, and say the Plan imports fine but cannot be saved from wp-admin until the limit is raised).
- Dry run: validation, resolution, counts and notes; no writes, no read-back.

**`PlanFileCommand`**, import half, registered as `ol-plans import-plan`:
- `wp ol-plans import-plan <file> [--dry-run] [--status=<status>]`, status `draft` (default) or `publish`. `<file>` is a container path, or `-` for stdin. A path that does not exist: `WP_CLI::error()` that mentions the `/plans` and `/plan-fixtures` mounts.
- Output: problems one per line then `WP_CLI::error('Nothing was imported: 7 problems.')`; notes as `WP_CLI::warning()`; success as `WP_CLI::success('Plan 123 created as a draft: 10 Weeks, 30 Workouts, 180 Prescriptions, 24 Exercises.')` followed by the edit URL. Dry run: `WP_CLI::success('Ready to import: …')`.

**Timing:** export a 16-week seeded Plan (`wp ol-plans seed --weeks=16`) and time its import (`Measure-Command` on the owner's PC). Record the number in the ticket. Over 10 seconds: stop and tell the owner before ticket 04, because wp-admin imports in one request.

## Acceptance criteria

- [ ] Round trip: export the demo Plan to `/plans/rt-a.json`, import it, export the new Plan to `/plans/rt-b.json`; the files are byte-identical. Same for `full.json` imported then exported (compare with the fixture). Delete the `rt-*` files afterwards.
- [ ] The imported Plan is a draft; every Workout and Prescription has a UUID uid, no uid repeats within the Plan, and none equals a uid of the source Plan (`wp db query` on `postmeta` for `%_uid` keys of both posts).
- [ ] Opening the imported Plan in wp-admin and pressing Update without changes keeps every row and every uid (compare the uid list before and after).
- [ ] After `--status=publish` and giving a test user Access (link it to a Product and complete an order, or insert an Access row as the plans-plugin tickets did), the Portal and the PDF preview show the same Weeks, Workouts and Prescriptions as the source Plan.
- [ ] `--dry-run` on `full.json` reports the right counts and writes nothing (`wp post list --post_type=ol_training_plan --post_status=any --format=count` unchanged).
- [ ] `/plan-fixtures/broken.json` prints exactly `broken.expected.txt` and imports nothing.
- [ ] Importing the same file twice gives two independent Plans and the "same title" note the second time.
- [ ] Forcing a read-back difference (temporarily, e.g. a filter that alters one `update_field` value) deletes the draft and reports the failure; remove the hack afterwards.
- [ ] The 16-week timing is recorded.
- [ ] `npm run lint:php` and `npm run analyse:php` pass.

## Comments
