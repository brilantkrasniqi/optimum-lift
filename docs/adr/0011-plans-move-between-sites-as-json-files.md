# ADR-0011: Training Plans move between sites as JSON Plan files, and importing one always creates a new draft

**Status:** Accepted (2026-10-06)

Spec and tickets: `.scratch/plan-files/`. Authoring guide: `docs/plan-files.md`.

## Context

Authoring a Training Plan in wp-admin means filling in every Prescription by
hand: a 12-week Plan with four Workouts of six Prescriptions is 288 rows. The
owner wants Claude to write Plans on the local site from a description, review
them there, and then put them on production, which has no WP-CLI and whose
database must never be overwritten once the shop is live.

A Plan's rows point at Exercises by post ID, and post IDs differ between
sites. ADR-0010 gave every Exercise a library key, the same everywhere, for
exactly this. Workout Logs point at Workouts and Prescriptions by `uid`
(ADR-0003), so anything that rewrites a live Plan's rows can orphan them.

Options considered:

1. **Copy the database to production.** Rejected for the reasons in ADR-0010
   (option 3): it overwrites orders, Customers and Workout Logs once the shop is
   live, and IDs still drift between later copies.
2. **WordPress's own export and import (WXR).** It carries post meta as-is, so
   every Prescription's Exercise would point at whatever post has that ID on
   the importing site, and the `uid`s inside the nested ACF meta would be
   imported unchanged and shared between sites.
3. **An import that updates an existing Plan in place.** Replacing a live
   Plan's rows gives them new `uid`s and orphans the Workout Logs that point at
   the old ones, unless every row is matched by `uid`, which a hand- or
   AI-written file cannot be trusted to carry.
4. **One Plan per JSON file, Exercises by library key, imported as a new
   draft.**

## Decision

Option 4. A **Plan file** (format `optimum-lift-plan`, version 1) holds one
Training Plan: its overview fields, Phases, and Weeks of Workouts of
Prescriptions, with each Exercise named by its library key. It carries no post
IDs, no `uid`s, no timestamps and no site URLs, so an unchanged Plan always
exports to the same bytes and files diff cleanly in git.

- **Import always creates a new Plan, as a draft.** It never updates or
  replaces one. The importing site gives every Workout and Prescription a new
  `uid`; importing a file twice gives two independent Plans. A published Plan
  that Customers use is still fixed in place in wp-admin (ADR-0003).
- **The whole file is checked before anything is written, and every problem is
  listed**, named by Week, Workout and Prescription. Properties are strict: an
  unknown one is a problem, not ignored. Every Exercise must exist and be
  published on the importing site. A file with any problem imports nothing.
- **After writing, the importer reads the Plan back and compares it with the
  file.** Any difference deletes the new draft and is reported, so a silent
  write problem shows up at import time, not in a Customer's Portal.
- The featured image, slug, author, linked Products, Access, Workout Logs and
  Exercise content are not carried. A Plan file references Exercises; it does
  not carry them.
- WP-CLI (`wp ol-plans export-plan`, `import-plan`) for local work and for
  Claude; wp-admin (**Export as JSON**, **Training › Import Plan**) for
  production.
- Plan files Claude writes are kept in the repo in `content/plans/`, mounted at
  `/plans` in the `wpcli` container, so every Product's Plan has history and can
  be imported on a fresh site.

## Consequences

- A Plan cannot be updated from a file. Changing a live Plan is a wp-admin
  edit; if updating from files is ever needed, it needs `uid`s in the file and
  row-by-row matching (`--update=<plan-id>`), which is out of scope now.
- The Exercise library has to be imported on a site before any Plan. A Plan
  that uses a hand-made local Exercise fails to import elsewhere; the export
  warns about such Exercises and the import names every missing key.
- Importing bypasses `max_input_vars`, but saving the imported Plan from
  wp-admin does not. The import notes when a Plan is too big for the site's
  limit.
- The PHP validator is the only definition of the format; there is no JSON
  Schema. Test fixtures in `tests/plan-files/` and `--dry-run` are how it is
  tested.
- Claude can write a valid Plan that is poor training. Imports are drafts, and
  the owner reviews every Plan before publishing it.
