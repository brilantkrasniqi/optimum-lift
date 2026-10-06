# Spec: the Exercise library

Status: implemented (2026-10-05), tickets 01 to 06. Open: 07 (verify on a clean site and go live) and 08 (Albanian instructions).

Read `CONTEXT.md` first. This spec uses its words: **Exercise**, **Prescription**, **Plan**, **Portal**, **Download**.

## Why this exists

Optimum Lift has 200 curated Exercises with a still image and an animation each, in `C:\Users\Work\Desktop\Projects\exercise-list` (outside this repo). A fresh install of the Plans plugin has no Exercises at all: they are database rows, and only `wp ol-plans seed` creates a few demo ones. The goal is that installing the plugin and pressing one button gives the full library, on any site.

The library is also what Plans are written against. A later effort (Plan import and export, AI-authored Plans) needs every Exercise to have a stable key that is the same on every site. Post IDs differ per site, so a key is that identity.

## Source data

`curated-exercises-with-instructions.json` is an array of 200 objects, all with the same ten keys. Checked 2026-10-05:

- `id` (4-digit string), `name` and `media_id` are unique. Every `image` and `gif_url` path exists; no file is unreferenced. Files are named `{id}-{media_id}.jpg` and `{id}-{media_id}.gif`.
- `difficulty`: beginner 85, intermediate 74, advanced 41. The plugin uses the same three values.
- `pattern`: 35 movement patterns (`horizontal_push`, `hinge`, `squat`, ...). `settings`: any of `home`, `gym`, `crossfit`. `reason`: one line on why the Exercise is in the set.
- `instructions`: one English paragraph (235 to 865 characters), no HTML, no newlines. **None is Albanian.**
- `name`: lowercase English, some with source leftovers: `(male)`, `v. 3`, `(side pov)`, `(equipment)`, and one `°`.
- Images and GIFs are all **180×180**; GIFs have 12 to 54 frames. Together 19.9 MB (GIFs average 97 KB), so no conversion is needed.
- **No muscle and no equipment** (the plugin's `primary_muscle` is required). Equipment is visible in the names (`dumbbell`, `lever`, `cable`, `band`, ...).

## Decisions

1. **The library ships inside the plugin as data**: `plugins/optimum-lift-plans/data/exercises/exercises.json` plus `data/exercises/media/`. It is not imported on activation (400 files in one request can time out); an empty library shows an admin notice offering the import.
2. **An Exercise's identity is its library key**, a slug of its cleaned name (`incline-push-up`), stored once and never changed. Hand-made Exercises get one generated from the title on save. The source `id` is kept as provenance.
3. **Import is additive.** It creates missing Exercises and never overwrites an edited one. `--update` is the explicit overwrite, limited with `--fields=`.
4. **Instructions stay English for now** (user, 2026-10-05). Albanian is ticket 08, delivered later through `--update --fields=instructions`.
5. **Add `movement_pattern` and `settings` to the Exercise; do not import `reason`.** Both help build Plans (balance push against pull; a home-only Plan uses only home Exercises). Neither is shown to Customers.
6. **Media lives inside the plugin** (user, 2026-10-05). The importer copies each file into the Media Library; only the `thumbnail` size is generated, since the Portal uses it and everything else falls back to the 180px original.
7. **Muscles and equipment are derived, then reviewed.** The build script derives them from `pattern` and the name. About 60 Exercises (conditioning, Olympic lifts, hinges, lunges, carries) are not decided by the pattern; those are judged from the name and marked in a review sheet the user checks before import. Secondary muscles are left empty.

## Data file format (version 1)

```json
{
  "version": 1,
  "exercises": [
    {
      "key": "incline-push-up",
      "source_id": "0493",
      "name": "Incline push-up",
      "primary_muscle": "chest",
      "secondary_muscles": [],
      "equipment": ["bodyweight"],
      "difficulty": "beginner",
      "pattern": "horizontal_push",
      "settings": ["home", "gym"],
      "instructions": "Place your hands on an elevated surface...",
      "image": "media/0493-B1EVP9F.jpg",
      "animation": "media/0493-B1EVP9F.gif"
    }
  ]
}
```

`instructions` is plain text; the importer stores it wrapped in `<p>`. Muscles, equipment, pattern and settings are `Choices` keys. Every value is validated against `Choices` before anything is written.

## Importer behaviour

For each entry, in order:

1. An `ol_exercise` post with the same `library_key` exists: **fill in** its empty fields, or with `--update` **overwrite** the fields that differ (limited by `--fields`). Nothing to do counts as **skipped**. Filling in is what lets a run that failed part-way be finished by running it again; the cost is that a field cleared on purpose is filled again by the next import. A trashed one counts as existing and is left alone. That includes an Exercise of the same name trashed by hand, because trashing saves it and so gives it a key.
2. Else an `ol_exercise` post without a key, outside the trash, whose title matches the name (case-insensitive) or makes the same key: **adopt** it. Set the key, then treat it as in 1, so it keeps every field it already has.
3. Else **create** it, status `publish` (the Prescription picker only offers published Exercises).

After the last entry, every Exercise still without a key (the trash included) gets one from its title.

Media is copied into the Media Library once. Each attachment carries `_ol_library_file` and `_ol_library_hash`, and a later import (after Exercises were deleted, or with `--update`) reuses the attachment for the same file and hash. Attachments get the Exercise name as title and alt text. `--dry-run` writes nothing and prints what would be created, adopted, filled in, updated, skipped and left in the trash, plus keys and media. Re-running the import changes nothing.

A media field that points at a deleted attachment counts as empty, so the next import repairs it. Only one writing run happens at a time, from either surface: a lock row (inserted with `INSERT IGNORE`) refuses a second run, which would otherwise create the same Exercise twice. Its value is `{time}:{token}`, so a run releases only its own lock, and a shutdown function releases it even when a time or memory limit kills the request; a lock nobody released expires after 5 minutes.

Surfaces: `wp ol-plans import-exercises [--dry-run] [--update] [--fields=<list>]`, and a **Training → Import** screen that runs the same code in batches of ten Exercises with a progress bar, so it works under a 30-second time limit.

## Existing code this touches

- `src/Content/ExerciseFields.php`, `src/Content/Choices.php`: new fields and choices.
- `src/Plan/Exercise.php`, `src/Plan/PlanRepository.php`: the value object carries key, pattern and settings.
- `templates/portal/workout.php`: the Portal only ever shows a 64px `thumbnail` of the animation, which WordPress makes as a still, so the GIFs never play. The animation moves into the "How to" panel at full size.
- `src/Cli/SeedCommand.php`: its demo Exercises ("Plank", "Seated cable row") are not in the library. Seed uses library keys instead.
- `templates/pdf/plan.php`: shows the still in a 165px cell. 180px source images are fine on screen and may look soft printed; check with real data.

## Out of scope

- Plan import and export, and writing Plans with AI. That is the next effort and depends on the library keys.
- Secondary muscles.
- Higher-resolution media.
- Albanian translation of Exercise names (they stay English).

## Risks

- **Licence.** The 180px size looks like a free tier of a public exercise API (a guess from the file size and naming). The user says the data is licensed or purchased; confirm the licence covers commercial use and embedding in the Download.
- **Muscle mapping** is judgement for about 60 Exercises. The review sheet is the control.
- **Import time on cheap hosting.** Batching is the control; thumbnails are limited to one size.

## How it is verified

The repo has no PHP test suite, only PHPCS and PHPStan. Verification is `--dry-run` output, counts from `wp post list` and `wp media list`, a second run that changes nothing, a headless-browser pass over the Import screen, the Portal and the PDF preview, and `npm run lint:php` and `npm run analyse:php`.

## Tickets

See `issues/`. The order is the build order.
