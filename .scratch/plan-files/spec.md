# Spec: Plan files (export and import Training Plans as JSON)

Status: planned (2026-10-06, revised the same day). Tickets 01 to 07 in `issues/`, in build order. Tickets 01 to 06 are for an agent; 07 is the owner's check on production.

Read `CONTEXT.md` first. This spec uses its words: **Plan**, **Training Plan**, **Week**, **Phase**, **Workout**, **Prescription**, **Exercise**, **Library key**, **Product**, **Workout Log**. A **Plan file** is one Training Plan written as JSON, naming Exercises by library key (ticket 06 adds the term to `CONTEXT.md`).

## Why this exists

Authoring a Training Plan in wp-admin means picking every Exercise and filling every field of every Prescription by hand: a 12-week Plan with four Workouts of six Prescriptions is 288 rows. The owner wants to describe a Plan in words ("a 10-week home hypertrophy Plan for beginners, 3 Workouts a week"), have Claude write it as a file on the local Docker site, review it there in wp-admin, the Portal and the PDF, then press **Export as JSON**, and on production press **Import Plan** and hand over the file. That gives the shop many Products early without weeks of data entry.

ADR-0010 prepared for this: every Exercise has a library key that is the same on every site, and `LibraryKeys` already says Plan files name Exercises by key, never by post ID.

## For the agent building this

**Read first, in this order:** `CONTEXT.md`; `docs/adr/0003-*.md` (uids, max_input_vars) and `docs/adr/0010-*.md` (library keys); this spec; then the code it copies from: `src/Cli/SeedCommand.php` (the write path), `src/Library/LibraryFile.php` and `src/Library/ExerciseImporter.php` (validate everything first, report every problem), `src/Library/ImportScreen.php` (admin screen, capability, nonce), `src/Content/PlanFields.php` (fields, uid filters, Phase validation), `src/Content/LibraryKeys.php`. The Exercise library's tickets in `.scratch/exercise-library/issues/` show the level of verification expected.

**Code conventions** (match the plugin): `declare(strict_types=1)`, `final` classes, `final readonly` value objects, dependencies through constructors, everything wired in `Plugin::boot()` (nothing else calls `add_action()` at file load), PSR-12 via PHPCS, PHPStan level 6, text domain `optimum-lift-plans`, English source strings. New code goes in `src/PlanFile/` (namespace `OptimumLift\Plans\PlanFile`) and `src/Cli/PlanFileCommand.php`.

**Where the work can run.** Checked from a cloud session on 2026-10-06:
- The cloud container has PHP 8.3 and Composer, but **no Docker daemon**, **no ACF Pro** (it is licensed and gitignored in `plugins/`), and Composer could not download the dev tools (GitHub archive downloads were refused: "Could not authenticate against github.com"). So a cloud session can write the code but cannot run WordPress, PHPCS or PHPStan.
- The owner's PC has everything: `C:\Users\Work\Desktop\Projects\optimum-lift`, Windows, Docker Desktop, the running stack, ACF Pro. **Run every check there**, through a Remote Control session in that folder if the session can start one, or by giving the owner exact commands. Push the branch first and `git pull` it there.
- The owner's shell is **PowerShell**. Its `>` writes UTF-16 and it has no `<` redirect, so commands in this spec read and write files through container mounts (`--file=`), never through shell redirection.

**Working the tickets:** one ticket at a time, in order. Set `Status: claimed` before starting and `Status: resolved` when every acceptance box is ticked, then append a dated `## Comments` entry: what changed (files), how each criterion was verified (commands and results), and anything worth knowing. One commit per ticket. `npm run lint:php` and `npm run analyse:php` must pass before a ticket is resolved. Ask the owner before merging to `main`.

## What exists today (checked 2026-10-06 on `main` at `f8e9da5`)

- No Plan import or export. The only importer is the Exercise library one (`src/Library/`, `wp ol-plans import-exercises`, **Training › Import**).
- `wp ol-plans seed` already builds a Plan in code with `wp_insert_post()` and `update_field()` on field keys, resolving Exercises with `LibraryKeys::find()`. The importer follows the same write path.
- `PlanFields` assigns a `uid` to every Workout and Prescription on save, also for rows written by `update_field()` (`ensureUidInRows`, `assignUid`). The importer never writes uids itself.
- `PlanFields::validate()` (the Phase rules) runs only on the wp-admin form, not on `update_field()`. The importer must check Phases itself.
- `PlanRepository` drops Prescriptions whose Exercise is unpublished or deleted, so it is the wrong source for an export: an export would silently lose rows. The exporter reads the raw ACF rows.
- Workout Logs key on `plan_id` + `workout_uid` (`Schema.php`), so a new Plan with fresh uids can never collide with anyone's logs.
- The `wpcli` service mounts the theme, the plugin, `mu-plugins` and `uploads`, but nothing else from the repo, and runs as user `33:33`.

## Decisions

1. **One Plan per file, JSON, format version 1.** Exercises by library key. No post IDs, no uids, no timestamps or site URLs anywhere in the file, so exporting an unchanged Plan always gives the same bytes and files diff cleanly in git.
2. **Import always creates a new Plan, as a draft.** It never updates or replaces an existing Plan. The owner reviews the draft, publishes it, and links it to a Product by hand (price, page and sales sections are Product work, not Plan content). Why: once Customers log Workouts against a Plan, ADR-0003 says fix it in place in wp-admin; replacing its rows from a file would re-issue uids and orphan their logs.
3. **uids are not in the file.** The importing site generates them through the existing `PlanFields` filters. Importing the same file twice gives two independent Plans.
4. **Validate the whole file before writing anything, and report every problem**, like the Exercise importer. A file with any problem imports nothing. The same validation runs for `--dry-run`, the wp-admin "Check only", and the real import.
5. **Strict properties.** An unknown property anywhere is a problem, so `"rest": 90` instead of `"rest_seconds": 90` is caught, not silently dropped. This matters most for files Claude writes.
6. **Every Exercise must exist and be published on the importing site.** A missing, trashed or unpublished one is a problem naming the key and where it is used. The exporter warns up front about Exercises that production may not have (not in the bundled library).
7. **After writing, the importer reads the Plan back and compares it with the file.** Any difference deletes the new draft and is reported as a failure. This catches a silent ACF write problem at the moment it happens instead of in a Customer's Portal.
8. **Not carried in the file:** the featured image, slug, author, linked Products, Access, Workout Logs, and Exercise content. A Plan file references Exercises; it does not carry them. An Exercise edited locally (new instructions, say) is not changed on production by importing a Plan; that travels through the library import (`--update`) or an edit on production.
9. **Surfaces mirror the Exercise import:** WP-CLI commands for local work and for Claude, and wp-admin for production, which has no WP-CLI.
10. **Plan files Claude writes are kept in the repo** under `content/plans/<slug>.json`, so every Product's Plan has history and can be imported on a fresh site. The `wpcli` service mounts that folder read-write at `/plans`.

## Plan file format (version 1)

```json
{
  "format": "optimum-lift-plan",
  "version": 1,
  "plan": {
    "title": "10-week home hypertrophy",
    "summary": "Three full-body Workouts a week with dumbbells and a bench.",
    "goal": "Muscle gain",
    "target_audience": "Beginners training at home",
    "difficulty": "beginner",
    "phases": [
      { "name": "Foundation", "first_week": 1, "last_week": 4 },
      { "name": "Build", "first_week": 5, "last_week": 10 }
    ],
    "weeks": [
      {
        "workouts": [
          {
            "name": "Full body A",
            "prescriptions": [
              {
                "exercise": "dumbbell-goblet-squat",
                "sets": 3,
                "target_type": "reps",
                "target": "10-12",
                "intensity": "RPE 7",
                "rest_seconds": 90,
                "notes": "Pause 2 seconds at the bottom."
              }
            ]
          }
        ]
      }
    ]
  }
}
```

The example Exercise key is illustrative; real keys come from `plugins/optimum-lift-plans/data/exercises/exercises.json`.

| Path | Rule | Default when omitted |
| --- | --- | --- |
| `format` | exactly `"optimum-lift-plan"` | required |
| `version` | integer `1`. A higher number: "This file was made by a newer version of the plugin." | required |
| `plan.title` | non-empty after trimming | required |
| `plan.summary` | string, plain text, newlines allowed | `""` |
| `plan.goal`, `plan.target_audience` | string, one line | `""` |
| `plan.difficulty` | a `Choices::difficulty()` key, or `""` | `""` |
| `plan.phases` | array, may be empty. Each: `name` non-empty; `first_week` ≥ 1; `last_week` ≥ `first_week` and ≤ the number of Weeks; no two Phases share a Week. Same messages as `PlanFields::validate()` | `[]` |
| `plan.weeks` | at least 1. Week numbers are positions: the first entry is Week 1 | required |
| `weeks[].workouts` | at least 1 | required |
| `workouts[].name` | non-empty | required |
| `workouts[].prescriptions` | at least 1 | required |
| `prescriptions[].exercise` | a valid library key (`LibraryKeys::isValid()`) of a published Exercise on this site | required |
| `prescriptions[].sets` | JSON integer 1 to 20 (`"3"` is a problem) | required |
| `prescriptions[].target_type` | `"reps"` or `"seconds"` (`Choices::targetTypes()`) | `"reps"` |
| `prescriptions[].target` | non-empty string, at most 20 characters (`"8"`, `"8-10"`, `"AMRAP"`, `"30"`) | required |
| `prescriptions[].intensity` | string, at most 20 characters | `""` |
| `prescriptions[].rest_seconds` | JSON integer ≥ 0, or `null` | `null` |
| `prescriptions[].notes` | string, plain text | `""` |

Every limit in this table comes from `PlanFields`; ticket 01 moves them into public constants there, used by both the field definitions and the validator.

Encoding: UTF-8. A leading UTF-8 byte-order mark is stripped (Windows editors add one). A UTF-16 file gets one problem: "Save the file as UTF-8." Files over 2 MB, or not valid JSON, are refused with one problem before validation. A 12-week Plan is about 100 KB.

Output: the exporter writes every property, defaults included, in the order of the table, with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`, LF line endings and a trailing newline.

Sanitising on import: `sanitize_text_field()` for one-line strings, `sanitize_textarea_field()` for `summary` and `notes`. A string that changes under sanitising is not a problem; the stored value is the sanitised one, and the read-back comparison (decision 7) compares against the sanitised file.

## Behaviour

**Export** (one Plan, any status except trash): read the Plan's overview fields and raw `phases` and `weeks` rows with `get_field()`, turn each Prescription's Exercise ID into its library key, cast numbers to integers (ACF returns strings), and build the file. A Prescription whose Exercise no longer exists or has no key is a problem and the export is refused, listing every such row. An Exercise that exists but is not published, or whose key is not in `LibraryFile::bundled()`, is a **warning** naming the Exercise and where it is used: the file is still written, and the warning says the importing site needs that Exercise too.

**Import:** read, validate (all problems), resolve every key to a post ID, then `wp_insert_post()` an `ol_training_plan` (draft by default) and `update_field()` the overview fields, `phases`, then `weeks`, using field keys exactly as `SeedCommand` does. Then read the Plan back through the exporter's reader and compare with the sanitised file (decision 7). A `WP_Error`, an exception, or a difference deletes the post (`wp_delete_post($id, true)`) and reports the failure. On success report the new Plan's ID and edit link, counts (Weeks, Workouts, Prescriptions, distinct Exercises), and notes:
- a Plan with the same title already exists (its ID);
- the Plan's edit form would send more inputs than this site's `max_input_vars` allows, so it can be imported but not saved from wp-admin until the limit is raised. Use the same estimate as `PlanFields::warnWhenNearInputLimit()` (move the estimate into a public method both use).

**Round trip:** export, import, export the copy: the two files are byte-identical. This is the main acceptance test.

**Dry run** (`--dry-run`, and "Check only" in wp-admin): everything except the writes and the read-back, same report with the counts it would create.

**Permissions:** export needs `edit_post` on the Plan; import needs `manage_options`, like the Exercise import. Every wp-admin request carries a nonce.

## Surfaces

- `wp ol-plans export-plan <plan-id> [--file=<path>]`. Without `--file`, the file goes to stdout (fine in bash, not in PowerShell). Warnings go to stderr.
- `wp ol-plans import-plan <file> [--dry-run] [--status=<draft|publish>]`. `<file>` is a path inside the container, or `-` for stdin. `--status=publish` exists for local review in the Portal and is not offered in wp-admin.
- Local use goes through the `/plans` mount:
  `docker compose --profile cli run --rm wpcli ol-plans export-plan 123 --file=/plans/home-hypertrophy.json`
  `docker compose --profile cli run --rm wpcli ol-plans import-plan /plans/home-hypertrophy.json --dry-run`
- wp-admin, Training Plans list: an **Export as JSON** row action, and an **Import Plan** button next to "Add New". Plan edit screen: a side meta box with **Export as JSON** and any export warnings.
- wp-admin, **Training › Import Plan**: file upload, "Check only, don't import", submit. The Exercise screen's menu label becomes **Import Exercises** so the two are distinct.

## Existing code this touches

- New `src/PlanFile/`: `PlanFile` (parse and validate), `PlanFileExporter`, `PlanFileImporter`, `PlanFileReport`, `ExportAction`, `ImportPlanScreen`. New `src/Cli/PlanFileCommand.php`. New `templates/admin/import-plan.php`.
- `src/Plugin.php`: wiring and the two WP-CLI commands.
- `src/Content/PlanFields.php`: public constants for the limits and field keys, and the input estimate as a public method. Field definitions and save behaviour do not change.
- `src/Library/ImportScreen.php`: menu label only.
- `docker-compose.yml`: `./content/plans:/plans` on the `wpcli` service (read-write), and `./tests/plan-files:/plan-fixtures:ro` for the test fixtures.
- New `content/plans/README.md`, `tests/plan-files/` (fixtures), `docs/plan-files.md`; `CLAUDE.md` and `CONTEXT.md` get short additions; `docs/adr/0011-*.md`.
- `languages/optimum-lift-plans-sq.po` and its compiled files.

`PlanRepository`, the Portal, the Download and Workout Logs do not change.

## Out of scope

- **Updating an existing Plan from a file.** Re-importing over a live Plan would orphan Workout Logs unless uids were matched row by row. If ever needed: `--update=<plan-id>`, with uids in the file. Not now.
- Several Plans per file; the featured image; creating or linking Products.
- Nutrition Plans (sold as hand-written PDFs for now).
- A "repeat this Week" shorthand. Weeks are authored in full (`CONTEXT.md`); whoever writes the file writes the repetition.
- A JSON Schema file and a PHP unit-test suite. The PHP validator is the one source of truth, and the fixtures in `tests/plan-files/` plus `--dry-run` are how it is tested.

## Risks

- **Plan quality.** Claude can write a valid Plan that is poor training. Imports are drafts, and the owner reviews every Plan before publishing.
- **Exercises missing on production.** The Exercise library has to be imported on production before any Plan (exercise-library ticket 07). Plans using hand-made local Exercises fail there; the export warning and the authoring guide's "library keys only" rule are the controls, and the import error names every missing key.
- **Large Plans in wp-admin.** Importing bypasses `max_input_vars`, but saving the imported Plan from wp-admin later does not. The import note, the existing sentinel guard, and raising the limit on the host (plans-plugin issue 08) cover it.
- **Import time.** The wp-admin import is one request. Ticket 03 measures a 16-week Plan; if it takes more than 10 seconds locally, say so in the ticket before building the screen.
- **ACF write path.** `update_field()` with nested repeaters on a brand-new post only works with field keys, not names. `SeedCommand` proves the path; the read-back catches anything else.

## How it is verified

On the owner's PC (see "Where the work can run"), with the stack running:
- Round trip of the seeded demo Plan and of a 16-week Plan (`wp ol-plans seed --weeks=16`): byte-identical files.
- `tests/plan-files/broken.json` imports nothing and prints exactly the problems in `broken.expected.txt`; the Plan count is unchanged.
- The imported draft opens in wp-admin and saves without changes to its rows or uids; after publishing, the Portal and the PDF preview show it.
- The wp-admin export and import screens work end to end in a browser.
- `npm run lint:php` and `npm run analyse:php` pass.
