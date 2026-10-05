# Import screen in wp-admin

Type: task
Status: resolved
Blocked by: 03

## What to build

A **Training → Import** screen for administrators that runs the importer from ticket 03.

- Shows what the library file contains and what a run would do (the dry-run counts).
- A button that imports in batches of ten Exercises, calling an admin endpoint per batch with a nonce and a `manage_options` capability check, with a progress bar and a final report. Each batch is idempotent, so a failed batch can be retried and a closed tab can be resumed.
- When no Exercise exists, a notice on the Exercises list offers the import.

## Acceptance criteria

- [x] On an empty library, the button imports all 200 and the progress bar finishes.
- [x] Closing the tab part-way and running again finishes the rest without duplicates.
- [x] Users without `manage_options` cannot see the screen or call the endpoint.
- [x] The notice shows only when the library is empty.
- [x] Strings are translatable and have Albanian entries.

## Comments

**2026-10-05 (implemented):**

What changed:
- `src/Library/ImportScreen.php`: the **Training › Import** submenu (`manage_options`), the `wp_ajax_ol_import_exercises` batch endpoint (capability checked first, then the nonce; 10 Exercises per batch), and the empty-library notice on the Exercises list.
- `templates/admin/import-exercises.php`: included straight from the plugin, not through `Templates::render()`, so a theme cannot override an admin screen. It shows what an import would do now (a dry run), or the library file's problems.
- `assets/admin-import.js`: plain ES2020 like `portal.js`. It runs batches in order, adds up the counts, keeps its place when a batch fails (the button becomes "Continue import"), warns before leaving mid-run, and builds the result with DOM methods because notes contain Exercise titles.
- Importer messages are now translatable, since this screen shows them. 39 new Albanian strings (2 plural); machine-written, not reviewed by a native speaker.

Two changes to the importer, found while testing this screen:
- **An import lock.** Closing the tab mid-batch leaves that batch running on the server, so starting again at once could run two batches over the same Exercises and create one twice. A writing run now takes a lock (a row inserted with `INSERT IGNORE`, which only one request can win; a stale one expires after 5 minutes). A refused batch answers 409, so the browser stays on it and Continue retries it; the CLI stops with the message.
- **A media field pointing at a deleted attachment counts as empty**, so the next import repairs it instead of treating it as filled.

Verified in Edge (Playwright) on the local site, from an empty library (no Exercises, no library media), with the database and the uploads file list backed up first and restored afterwards:
- Editor (temporary account, `edit_posts` but not `manage_options`): no Import menu item, no notice, the screen answers 403, the endpoint answers 403.
- Admin: the notice with "Import the library" shows on the empty Exercises list and is gone once Exercises exist; a bad nonce gets 403.
- Preview "New Exercises 200, images and animations 400". The run's 4th request was dropped on purpose: it stopped at 30/200 with the message and a Continue button; Continue went on from 30. The tab was then closed at 80/200; a new tab's preview showed the remainder, and the import finished at 200/200 with no errors. Database afterwards: 200 Exercises, 0 duplicate keys or titles, 400 library attachments, 0 duplicate files, every Exercise with an animation.
- Batches took 4.1 to 5.5 s each.
- Reloading afterwards: "fully imported, nothing to do", no button.
- Lock: a fresh lock stops the CLI and leaves the other run's lock in place; the screen stops at 0 with "Another import is running" and finishes after the lock is released; a 10-minute-old lock is cleared and ignored; two CLI imports started at once give one import and one refusal, with 200 Exercises and no duplicates.
- In Albanian (admin locale set to `sq` for the check): heading "Importo ushtrimet", menu "Importo", and the finished screen showing only the result (preview and button hidden, status kept for screen readers).
- `npm run lint:php` 0 errors, `npm run analyse:php` no errors.
