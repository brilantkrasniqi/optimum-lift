# ADR-0010: The Exercise library ships in the plugin as data, imported on demand, and Exercises are matched by library key

**Status:** Accepted (2026-10-05)

Spec and tickets: `.scratch/exercise-library/`.

## Context

Optimum Lift has 200 curated Exercises (a JSON file, a still JPEG and a GIF
each, 20 MB in all, licensed). Exercises are posts, so a fresh install of the
Plans plugin has none, and production must get the same library as local
development.

Plans will also be written as files (by hand or with AI) and moved between
sites. A Prescription stores its Exercise's post ID, and Logged Sets store it
too, but post IDs differ from site to site: a Plan file that carried them would
point at the wrong Exercises.

Options considered for getting the library onto a site:

1. **Create it on plugin activation.** "Install and it is there", but 400 file
   copies in one request can time out on shared hosting, a failure leaves half a
   library, and activation is the wrong moment to surprise a live site.
2. **A separate media pack** uploaded after the plugin. A smaller plugin, but one
   more thing to remember at go-live, and the data and its media can drift
   apart.
3. **Copy the local database to production.** Moves Plans and Exercises
   together, but overwrites orders, customers and Workout Logs once the shop is
   live, and still leaves IDs that differ between later copies.
4. **Data and media inside the plugin, imported on demand** by a WP-CLI command
   or a wp-admin screen that works in small batches.

## Decision

Option 4. `plugins/optimum-lift-plans/data/exercises/` holds `exercises.json`
(format version 1) and the 400 media files. `tools/build-exercise-library.mjs`
makes them from the curated source folder; corrections go in its `OVERRIDES`,
never in the output. The import (`wp ol-plans import-exercises`, or
**Training › Import**) creates missing Exercises, takes over an existing one of
the same name, and fills in empty fields; it overwrites only with `--update`.

Every Exercise has a **library key**, a slug such as `incline-push-up`, set once
and never changed. Anything stored outside the database (the library file, Plan
files) names Exercises by key, and the importing site turns keys into its own
post IDs.

## Consequences

- The repository grows by about 21 MB of images that never change, and the
  plugin zip is about 21 MB. A host with a small upload limit needs the plugin
  uploaded by SFTP.
- Keys are permanent. Renaming an Exercise keeps its key; a key typed into a
  Plan file must exist on the importing site. Two Exercises can never share one.
- Import never overwrites a field someone filled in, so editing on production is
  safe. The cost: a field cleared on purpose is filled in again by the next
  import, and a trashed Exercise with a library name blocks that library
  Exercise until the trash is emptied.
- Only one import runs at a time (a lock row in `wp_options`), because two
  overlapping runs would create the same Exercise twice. A request that dies
  mid-batch leaves the lock for up to 5 minutes.
- The source has no muscles or equipment; they are derived from the movement
  pattern and the name, and were reviewed by the owner. Secondary muscles are
  empty. Instructions are English until the Albanian ones are imported with
  `--update --fields=instructions`.
- The media is 180 px: fine on screen, slightly soft in a printed Download.
  Sharper files would replace these through the same build and `--update`.
