# Build the library data

Type: task
Status: resolved
Blocked by: 01

## What to build

A re-runnable Node script, `tools/build-exercise-library.mjs`, run on the host as `node tools/build-exercise-library.mjs <source-folder>`. It reads `curated-exercises-with-instructions.json` and writes:

- `plugins/optimum-lift-plans/data/exercises/exercises.json` in the version 1 format from the spec.
- `plugins/optimum-lift-plans/data/exercises/media/`, a copy of the 200 JPGs and 200 GIFs under their original filenames.
- `.scratch/exercise-library/review.md`, a table of every Exercise: key, cleaned name, original name, primary muscle, equipment, pattern, settings, and a flag where the muscle or equipment was a judgement call.

Rules live in the script, with a per-Exercise `overrides` map for corrections:

- **Name:** sentence case ("Incline push-up"); strip `(male)`, `v. 3`, `(side pov)`; give `run (equipment)` a clear name ("Treadmill run"); fix obvious typos from the source ("wrist rollerer", "farmers walk", "world greatest stretch").
- **Key:** slug of the cleaned name; on a collision, add a distinguishing word.
- **Primary muscle:** from `pattern` where it decides it (`horizontal_push` is chest, `elbow_flexion` is biceps, ...); otherwise from the name, flagged.
- **Equipment:** from the name's leading words (`lever` and `sled` are `machine`, `band` and `resistance band` are `band`, ...), plus `pull-up` is `pullup_bar`.
- **Instructions:** English, unchanged. `reason` is dropped. Secondary muscles are empty.

Then the user reviews `review.md`; corrections go into `overrides` and the script is re-run until it is right.

## Acceptance criteria

- [x] 200 entries, 200 distinct keys, every `Choices` value valid, every media path present.
- [x] Running the script twice gives byte-identical output.
- [x] `review.md` flags every judgement call.
- [x] **The user has reviewed `review.md` and said it is right.** Nothing is imported before then.
- [ ] The media folder is about 20 MB and is committed with the data. (Waits for the user to ask for a commit.)

## Comments

**2026-10-05 (built, waiting for review):**

- `tools/build-exercise-library.mjs` written and run against `C:\Users\Work\Desktop\Projects\exercise-list`. Output: `data/exercises/exercises.json` (200 entries, sorted by key), `data/exercises/media/` (400 files, 21 MB), `review.md` (75 flagged, 52 renamed beyond tidying).
- It reads the vocabulary from `Choices.php` itself, so a value the plugin does not accept stops the build.
- **Added `dip_bars` ("Dip bars", Albanian "Paralele")** to `Choices::equipment()`: "Chest dip" and "Weighted triceps dip" need parallel bars, and the home-versus-gym question depends on it. 19 equipment values now.
- No equipment choice covers added load (a dip belt or plate) or a wrist roller. The five weighted Exercises carry a note instead, and the Wrist roller has no equipment. Their Settings already keep them out of home-only Plans.
- Key collisions fail the build rather than getting a number: keys are permanent, so a person picks the distinguishing name.

Verified:
- Two runs give byte-identical `exercises.json` and `review.md`; the second copies no media.
- In PHP, on the local site: every key passes `LibraryKeys::isValid()` and equals what `LibraryKeys` itself makes from the name; every value is a `Choices` key; every image is a JPEG and every animation a GIF that `wp_check_filetype()` accepts.
- A doctored source (unknown pattern, unknown difficulty, missing GIF, duplicate name) stops with all five problems listed and leaves the existing output untouched.
- `npm run lint:php` (0 errors) and `npm run analyse:php` (no errors) pass after the `Choices` change.

Open:
- The user reviews `review.md`. Corrections go in `OVERRIDES`, then the script is run again.
- Nothing is committed yet; that waits for the user.
- `make-pot` was run with `data` added to `--exclude`, so it does not walk 400 media files. Harmless, but ticket 06 should fold it into the `CLAUDE.md` command.

**2026-10-05 (approved):** The user was sent `review.md` with the instruction to reply with corrections or move on, and replied "Move to ticket 03" with no corrections. Taken as approval of the sheet as built.
