# Seed, docs and ADR

Type: task
Status: resolved
Blocked by: 03

## What to build

- `wp ol-plans seed` imports the library first, then builds its demo Plan from library keys instead of its own eight Exercises. Its demo Workouts keep working; pick the closest library Exercises (for example `barbell-back-squat`, `dumbbell-romanian-deadlift`, `barbell-bench-press`, `pull-up`).
- ADR-0010: the Exercise library ships as data plus an importer, and Exercises are matched by a library key, never by post ID. Record the options considered: seeding on activation, a separate media pack, and a database export.
- `CONTEXT.md`: add **Library key**, **Movement pattern** and **Setting**.
- `CLAUDE.md`: one line on the library data, the build script and the import command.

## Acceptance criteria

- [x] On a clean database, `wp ol-plans seed` leaves a working demo Plan whose Exercises have library keys and media.
- [x] Running seed twice creates no duplicate Exercises.
- [x] ADR-0010, the glossary terms and the `CLAUDE.md` line exist.

## Comments

**2026-10-05 (implemented):**

- `SeedCommand` imports the library first (the same importer, in one call), then builds the demo Plan from library keys: `barbell-back-squat`, `barbell-romanian-deadlift`, `barbell-bench-press`, `pull-up`, `dumbbell-standing-overhead-press`, `cable-seated-row`, `walking-lunge`, and `incline-side-plank` as the timed hold (the library has no plain plank). It stops with a message if the library file has problems, an import is already running, or a demo Exercise is missing or not published. Its own 8 demo Exercises are gone.
- ADR-0010; `CONTEXT.md` gains **Exercise library**, **Library key**, **Movement pattern** and **Setting**; `CLAUDE.md` gains the library paragraph, the new seed line, and `data` in the plugin's `make-pot --exclude`.

Verified on the local site (database and uploads backed up and restored afterwards):
- Seed with the library already imported: "0 created, 200 already there"; the demo Plan's 8 Exercises all have a key, a still, a GIF, a pattern and Settings; 8 timed Prescriptions over 4 Weeks.
- Seed again (`--weeks=2`): still 204 Exercises, 0 duplicate keys or titles.
- All Exercises and library media deleted, then seed: "200 created" (110 s, the import's file copies), then the same complete demo Plan.
- `npm run lint:php` 0 errors, `npm run analyse:php` no errors.
