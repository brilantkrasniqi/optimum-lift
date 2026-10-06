# Importer and WP-CLI command

Type: task
Status: resolved
Blocked by: 01, 02

## What to build

An importer in the plugin (`src/Library/`) that reads `data/exercises/exercises.json` and applies the rules in the spec (skip, adopt by title, create; `--update` and `--fields`), plus `wp ol-plans import-exercises [--dry-run] [--update] [--fields=<list>]`.

- Validate the whole file before writing anything: version, unique keys, every value in `Choices`, every media file present. Report all problems, not only the first.
- Media: copy each file to a temporary file and sideload it into the Media Library. Title and alt text are the Exercise name. Generate only the `thumbnail` size during the import, then restore the filter.
- A trashed Exercise with the key counts as existing and is reported.
- The core takes an offset and a limit, so the admin screen in ticket 04 can run it in batches.
- Create Exercises as `publish`.
- Ticket 01 gave Exercises a key only when they are saved, so any Exercise that already exists (the 8 demo ones locally, or hand-made ones on a live site) has none. The import also gives every keyless Exercise a key via `LibraryKeys::unique()`, whether or not it matches the library, and reports how many. Plan files need every Exercise to have one.
- Use `LibraryKeys` for the rules: `isValid()` for data-file keys, `find()` to match by key (it sees the trash), and set a new Exercise's key with `meta_input` at insert so `ensure()` does not make a different one from the title.

## Acceptance criteria

- [x] `--dry-run` prints create, adopt, skip and update counts and writes nothing.
- [x] On an empty library the import creates 200 Exercises, 200 stills and 200 animations, each Exercise with its key.
- [x] A second run reports 200 skipped and changes nothing.
- [x] An existing Exercise titled like a library entry, with no key, is adopted: it keeps its edited fields and gets the key.
- [x] Editing an Exercise in wp-admin and re-running without `--update` leaves the edit alone.
- [x] `--update --fields=instructions` rewrites only instructions.
- [x] A broken data file (unknown muscle, missing media file) stops before the first write and lists every problem.
- [x] After a run, no Exercise is without a key, including the 8 demo ones that match nothing in the library.
- [x] `npm run lint:php` and `npm run analyse:php` pass.

## Comments

**2026-10-05 (implemented):**

What changed:
- `src/Library/`: `LibraryFile` (reads and checks the whole file; any problem means no entries), `LibraryEntry`, `ImportOptions`, `ImportReport`, `ExerciseImporter`.
- `src/Cli/ImportExercisesCommand.php`, registered as `wp ol-plans import-exercises [--dry-run] [--update] [--fields=<list>] [--file=<path>]`. `--file` reads another library file; it is how the broken-file case was tested. The CLI runs in batches of 25, the same path the admin screen will use.
- `ExerciseFields` now names its field keys as constants. `LibraryKeys::slug()` is public, for matching by key.
- `phpstan-wp-cli-stub.php`: `WP_CLI::log()` and `warning()`.

**One rule differs from the original spec**, which the spec now says: an existing library Exercise has its empty fields filled in rather than being skipped outright, so a run that failed part-way finishes by running again. Edited fields are never touched without `--update`.

Verified on the local site, with the database backed up first and restored afterwards (uploads put back to the same file list):
- Dry run: "196 created, 4 adopted, 4 keys, 400 media", exactly as worked out by hand. A fingerprint of every Exercise and attachment row was unchanged.
- Real run (108 s in the CLI, about 0.27 s per file): 204 Exercises, all published, all with a key, no duplicate keys. Every library Exercise matches the file on title, muscle, pattern, difficulty, equipment, settings, instructions and status, except the 4 adopted demo Exercises, which kept their own instructions (and Walking lunge its muscle and equipment), as intended. 400 attachments with the right MIME type, file on disk, `_ol_library_file` and hash, the Exercise name as title and alt text; only the `thumbnail` size generated.
- Second run: 200 skipped, fingerprint identical.
- Push-up edited (muscle, instructions): a plain re-run left both; `--update --fields=instructions` restored the instructions only (5 updated: Push-up plus the 4 adopted demo Exercises); a full `--update` dry run then found the 2 remaining differences.
- Broken file (unknown muscle, missing GIF, duplicate key, unknown equipment, `media/../../../wp-config.php`, no instructions): all six listed, nothing imported, fingerprint unchanged.
- A trashed library Exercise is left alone and not re-created.
- All Exercises deleted, then imported: re-created with 398 media reused and 0 added (no duplicate uploads). A keyless "Barbell Deadlift!!" was adopted by key match and kept its title. A keyless Exercise in the trash was not adopted and was given `burpee-2`.
- `npm run lint:php` 0 errors, `npm run analyse:php` no errors.

Left on the local site: the library, imported once for real. Plan #31 still loads with all 8 of its Exercises. The 4 that are not in the library (Romanian deadlift, Dumbbell shoulder press, Seated cable row, Plank) have keys but no media; ticket 06 moves seed onto library Exercises.

Worth knowing:
- Trashing an Exercise saves it, so it gets a key then. An Exercise you trashed whose name matches a library one therefore blocks that library Exercise ("in the trash and was left alone"). Empty the trash, or restore it, to change that.
- The CLI took 108 s for 400 files. The admin screen's batches of 10 Exercises (20 files) take about 5 s each.
