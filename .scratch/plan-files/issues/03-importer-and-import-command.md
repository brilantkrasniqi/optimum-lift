# Importer and `wp ol-plans import-plan`

Type: task
Status: resolved
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

- [x] Round trip: export the demo Plan to `/plans/rt-a.json`, import it, export the new Plan to `/plans/rt-b.json`; the files are byte-identical. Same for `full.json` imported then exported (compare with the fixture). Delete the `rt-*` files afterwards.
- [x] The imported Plan is a draft; every Workout and Prescription has a UUID uid, no uid repeats within the Plan, and none equals a uid of the source Plan (`wp db query` on `postmeta` for `%_uid` keys of both posts).
- [x] Opening the imported Plan in wp-admin and pressing Update without changes keeps every row and every uid (compare the uid list before and after).
- [x] After `--status=publish` and giving a test user Access (link it to a Product and complete an order, or insert an Access row as the plans-plugin tickets did), the Portal and the PDF preview show the same Weeks, Workouts and Prescriptions as the source Plan.
- [x] `--dry-run` on `full.json` reports the right counts and writes nothing (`wp post list --post_type=ol_training_plan --post_status=any --format=count` unchanged).
- [x] `/plan-fixtures/broken.json` prints exactly `broken.expected.txt` and imports nothing.
- [x] Importing the same file twice gives two independent Plans and the "same title" note the second time.
- [x] Forcing a read-back difference (temporarily, e.g. a filter that alters one `update_field` value) deletes the draft and reports the failure; remove the hack afterwards.
- [x] The 16-week timing is recorded.
- [x] `npm run lint:php` and `npm run analyse:php` pass.

## Comments

### 2026-10-07 (Claude)

Built `PlanFileImporter`, `PlanFileReport` and `wp ol-plans import-plan`. Checked on the owner's PC:

- Round trips byte-identical: demo, `full.json`, and a 16-week seeded Plan. The copy is a draft; uids 60/60 valid, distinct, none shared with the source.
- Unchanged save: `full.json` imported, its edit form re-submitted unchanged over HTTP; the 37 uids are identical, only `_edit_last`/`_edit_lock` were added, and the export still equals the file.
- Portal and PDF: the published `full.json` Plan, as a Customer with Access, shows every Week, Workout and Exercise in order; preview and download are valid PDFs with the same items.
- Dry runs write nothing; `broken.json` imports nothing; importing twice gives two Plans and the same-title note; a forced read-back difference deletes the draft (first difference reported).
- **Timing:** first measured 15.8 s for 16 Weeks (10,287 queries). ACF clears the Plan's meta cache with every row, so each `update_metadata()` lookup reloaded all of its meta. Fixed in `bd032ed` by adding a new Plan's meta directly: 4.0 s and 3,545 queries (measured after WordPress loads; WP-CLI start-up adds about 6 s), no duplicate meta keys, round trip still identical.
- `lint:php` and `analyse:php` pass.
