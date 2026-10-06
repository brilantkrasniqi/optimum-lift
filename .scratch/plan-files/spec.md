# Spec: Plan files (export and import Training Plans as JSON)

Status: planned (2026-10-06). Tickets 01 to 06 in `issues/`, in build order.

Read `CONTEXT.md` first. This spec uses its words: **Plan**, **Training Plan**, **Week**, **Phase**, **Workout**, **Prescription**, **Exercise**, **Library key**, **Product**, **Workout Log**. A **Plan file** is one Training Plan written as JSON, naming Exercises by library key.

## Why this exists

Authoring a Training Plan in wp-admin means picking every Exercise and filling every field of every Prescription by hand: a 12-week Plan with four Workouts of six Prescriptions is 288 rows. The owner wants to describe a Plan in words ("a 10-week home hypertrophy Plan for beginners, 3 Workouts a week"), have Claude write it on the local Docker site, review it there in wp-admin, the Portal and the PDF, then press **Export as JSON**, and on production press **Import** and hand over the file. That gives the shop many Products early without weeks of data entry.

ADR-0010 prepared for this: every Exercise has a library key that is the same on every site, and `LibraryKeys` already says Plan files name Exercises by key, never by post ID.

## What exists today (checked 2026-10-06 on `main` at `f8e9da5`)

- No Plan import or export. The only importer is the Exercise library one (`src/Library/`, `wp ol-plans import-exercises`, **Training › Import**).
- `wp ol-plans seed` (`src/Cli/SeedCommand.php`) already builds a Plan in code with `wp_insert_post()` and `update_field()` on field keys, resolving Exercises with `LibraryKeys::find()`. The importer follows the same write path.
- `PlanFields` assigns a `uid` to every Workout and Prescription on save, also for rows written by `update_field()` (`ensureUidInRows`, `assignUid`). The importer never writes uids itself.
- `PlanRepository` drops Prescriptions whose Exercise is unpublished or deleted, so it is the wrong source for an export: an export would silently lose rows. The exporter reads the raw ACF rows.
- Workout Logs key on `plan_id` + `workout_uid` (`Schema.php`), so a new Plan with fresh uids can never collide with anyone's logs.

## Decisions

1. **One Plan per file, JSON, format version 1.** Exercises by library key. No post IDs and no uids anywhere in the file.
2. **Import always creates a new Plan, as a draft.** It never updates or replaces an existing Plan. The owner reviews the draft and publishes it, then links it to a Product by hand (price, page and sales sections are Product work, not Plan content). Why not update in place: once Customers log Workouts against a Plan, ADR-0003 says fix it in place in wp-admin; replacing its rows from a file would re-issue uids and orphan their logs. Updating from a file is out of scope (see below).
3. **uids are not exported.** The importing site generates them, through the existing `PlanFields` filters. Plan files written by hand or by Claude never need them, and importing the same file twice gives two independent Plans.
4. **Validate the whole file before writing anything, and report every problem**, as the Exercise importer does. A file with any problem imports nothing. The same validation runs for `--dry-run`, the wp-admin check, and the real import.
5. **Strict keys.** An unknown property anywhere is a problem, so a typo such as `"rest": 90` instead of `"rest_seconds": 90` is caught instead of silently dropped. This matters most for files Claude writes.
6. **Every Exercise must exist and be published on the importing site.** A key that is missing, in the trash or not published is a problem naming the key and where it is used (Week 3, Workout 2, Prescription 4). Hand-made Exercises that exist only locally are therefore caught at import, and the exporter warns about them up front (ticket 02).
7. **Not carried in the file:** the featured image, the post slug, the author, linked Products, Access, Workout Logs. The importing site's draft has none of these.
8. **Surfaces mirror the Exercise import:** WP-CLI commands for local work (and for Claude), and wp-admin for production, which has no WP-CLI: an **Export as JSON** action on the Plan, and a **Training › Import Plan** screen with a file upload.
9. **Plan files Claude writes are kept in the repo** under `content/plans/<slug>.json`, so every Product's Plan has history and can be re-imported on a fresh site. Exports downloaded from wp-admin can be dropped there too.

## Plan file format (version 1)

```json
{
  "format": "optimum-lift-plan",
  "version": 1,
  "exported_at": "2026-10-06T06:30:00Z",
  "exported_from": "http://localhost:8080",
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

The example Exercise key is illustrative; real keys come from `data/exercises/exercises.json`.

| Path | Rule | Default when omitted |
| --- | --- | --- |
| `format` | exactly `"optimum-lift-plan"` | required |
| `version` | integer `1`; any other number is "made by a newer plugin" | required |
| `exported_at`, `exported_from` | strings, informational only, ignored on import | omitted in hand-written files |
| `plan.title` | non-empty after trimming | required |
| `plan.summary` | string, plain text, newlines allowed | `""` |
| `plan.goal`, `plan.target_audience` | string, one line | `""` |
| `plan.difficulty` | a `Choices::difficulty()` key, or `""` | `""` |
| `plan.phases` | array, may be empty. Each: `name` non-empty; `first_week` ≥ 1; `last_week` ≥ `first_week` and ≤ the number of Weeks; no two Phases share a Week. Same rules and messages as `PlanFields::validate()` | `[]` |
| `plan.weeks` | at least 1. Week numbers are positions: the first entry is Week 1 | required |
| `weeks[].workouts` | at least 1 | required |
| `workouts[].name` | non-empty | required |
| `workouts[].prescriptions` | at least 1 | required |
| `prescriptions[].exercise` | a valid library key (`LibraryKeys::isValid()`) of a published Exercise on this site | required |
| `prescriptions[].sets` | integer 1 to 20 | required |
| `prescriptions[].target_type` | `"reps"` or `"seconds"` (`Choices::targetTypes()`) | `"reps"` |
| `prescriptions[].target` | non-empty string, at most 20 characters (`"8"`, `"8-10"`, `"AMRAP"`, `"30"`) | required |
| `prescriptions[].intensity` | string, at most 20 characters | `""` |
| `prescriptions[].rest_seconds` | integer ≥ 0, or `null` | `null` |
| `prescriptions[].notes` | string, plain text | `""` |

Limits copy the ACF field definitions in `PlanFields`; if those change, the validator changes with them (one place: read the limits from constants shared with `PlanFields` where practical). The exporter always writes every property, defaults included, in the order above, pretty-printed with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` and a trailing newline, so files diff cleanly in git.

Sanitising on import: `sanitize_text_field()` for one-line strings, `sanitize_textarea_field()` for `summary` and `notes`. A file whose strings change under sanitising is not a problem; the stored value is the sanitised one.

Files larger than 2 MB, or not valid UTF-8 JSON, are refused before validation. A 12-week Plan is about 100 KB.

## Behaviour

**Export** (one Plan, any status except trash): read the Plan's raw `phases` and `weeks` rows with `get_field()`, turn each Prescription's Exercise ID into its library key, and write the file. A Prescription whose Exercise has no key or no longer exists is a problem and the export is refused, listing every such row. An Exercise that exists but is not published, or whose key is not in the bundled library (`LibraryFile::bundled()`), is a **warning**: the file is written, and the warning says the importing site needs that Exercise too.

**Import:** read, validate (all problems), resolve every key to a post ID, then `wp_insert_post()` a draft `ol_training_plan` and `update_field()` overview fields, `phases`, then `weeks`, using field keys exactly as `SeedCommand` does. If any write fails, delete the half-made draft (`wp_delete_post($id, true)`) and report the failure. On success report the new Plan ID, its edit link, and counts (Weeks, Workouts, Prescriptions, distinct Exercises). A Plan with the same title already on the site is a note, not a problem.

**Round trip:** export, import, export again gives the same file apart from `exported_at` and `exported_from`. This is the main acceptance test.

**Dry run** (`--dry-run` and the wp-admin "Check only"): everything but the writes, same report.

Capabilities: export needs `edit_post` on the Plan; import needs `manage_options`, like the Exercise import. Every wp-admin request carries a nonce.

## Surfaces

- `wp ol-plans export-plan <plan-id> [--file=<path>]` writes to stdout by default, or to a file. `--file=-` is stdout.
- `wp ol-plans import-plan <file> [--dry-run] [--status=draft|publish]`. Default `draft`; `publish` exists for scripted local review and is not offered in wp-admin.
- wp-admin, Training Plans list: an **Export as JSON** row action. Plan edit screen: an **Export as JSON** button in a small side meta box. Both hit one `admin-post.php` handler that streams `<post-slug>.json` as a download (or shows the problems as an admin notice and redirects back).
- wp-admin, **Training › Import Plan**: a file input, a "Check only, don't import" checkbox, and a submit button. Problems are listed in full; success redirects to the new draft's edit screen with a notice carrying the counts and any warnings. Also a "Import Plan" button next to "Add New" on the Training Plans list, linking to that screen.

## Claude authoring workflow (ticket 05)

`docs/plan-files.md` is the reference a Claude session follows when the owner says "create this kind of Plan": the format above, how to pick Exercises from `data/exercises/exercises.json` (filter by `settings`, `equipment`, `difficulty`; balance with `pattern`; use only keys from that file), the programming conventions to apply (Phases, a lighter last Week per Phase, progressive targets, `seconds` targets for holds and carries), where to save (`content/plans/<slug>.json`), and the commands: `wp ol-plans import-plan /plans/<slug>.json --dry-run` (the `wpcli` container sees `content/plans/` at `/plans`), then without `--dry-run`, then review in wp-admin, Portal and PDF preview. `CLAUDE.md` gets a short pointer to it.

## Existing code this touches

- New `src/PlanFile/`: `PlanFile` (parse and validate, holds `problems`, `warnings`), `PlanFileExporter`, `PlanFileImporter`, `PlanFileReport`, `ExportAction` (admin row action, meta box, admin-post handler), `ImportPlanScreen`.
- New `src/Cli/PlanFileCommand.php`, registered in `Plugin::boot()` as `ol-plans export-plan` and `ol-plans import-plan`.
- `src/Content/PlanFields.php`: expose the limits (sets 1 to 20, target and intensity 20 characters) and field keys the importer needs as public constants, so they live in one place. No change to fields or save behaviour.
- `templates/admin/import-plan.php`: the import screen.
- `languages/optimum-lift-plans-sq.po` and friends: new strings (see CLAUDE.md for the `wp i18n` steps).
- `docs/plan-files.md`, `CLAUDE.md`, `content/plans/` (new), and a read-only `./content/plans:/plans` mount on the `wpcli` service in `docker-compose.yml`.

`PlanRepository`, the Portal, the Download and Workout Logs do not change.

## Out of scope

- **Updating an existing Plan from a file.** Re-importing over a live Plan would orphan Workout Logs unless uids are matched row by row. If it is ever needed, add it as `--update=<plan-id>` that requires the file's uids; the exporter would then also have to write them. Not now.
- Several Plans per file, and a zip with the featured image.
- Creating or linking Products from the file.
- Nutrition Plans (they are PDFs for now; see project memory on diet direction).
- A "repeat Week" shorthand in the format. Plans are authored in full (CONTEXT.md: Week 3 is written out, not derived), and a script or Claude writes the repetition.
- A JSON Schema file. The PHP validator is the single source of truth; the doc describes it.

## Risks

- **Plan quality.** Claude can produce a structurally valid Plan that is poor training. The owner reviews every Plan before publishing; imports are drafts so nothing reaches Customers unreviewed.
- **Missing Exercises on production.** Plans that use hand-made local Exercises fail to import there. The export warning and the authoring guide's "library keys only" rule are the controls; the import error names every missing key.
- **Large Plans in wp-admin.** Importing bypasses `max_input_vars`, but later editing the imported Plan in wp-admin still posts the full form. The existing sentinel guard and warning cover it; production needs the raised limit (plans-plugin issue 08) before editing a big imported Plan there.
- **ACF write path.** `update_field()` with nested repeaters on a brand-new post only works with field keys, not names. `SeedCommand` proves the path; keep to it.

## How it is verified

No PHP test suite exists; verification is by commands on the local site, as in the Exercise library effort:

- Export the seeded demo Plan, import it, export the copy: identical apart from `exported_at`/`exported_from` (`diff` after stripping those two lines).
- A broken file (each problem in ticket 01's list) imports nothing and lists every problem; `wp post list --post_type=ol_training_plan` count unchanged.
- The imported draft opens in wp-admin and saves without changes to its rows; every Workout and Prescription has a uid, none duplicated; Portal and PDF preview render it after publishing.
- The wp-admin export and import screens work end to end in a headless browser.
- `npm run lint:php` and `npm run analyse:php` pass.
