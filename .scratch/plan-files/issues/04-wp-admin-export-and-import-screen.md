# wp-admin: Export as JSON and Training › Import Plan

Type: task
Status: ready-for-agent
Blocked by: 02, 03

## What to build

Production has no WP-CLI, so both directions need wp-admin.

**Export** (`src/PlanFile/ExportAction.php`):
- Row action **Export as JSON** on the Training Plans list (`post_row_actions`, only for `ol_training_plan`, not in the trash), and a small side meta box on the Plan edit screen with the same link. Hide both on a never-saved Plan.
- Both link to `admin-post.php?action=ol_export_plan&plan=<id>&_wpnonce=…`. The handler checks the nonce and `current_user_can('edit_post', $id)`, runs the exporter, and on success sends `Content-Type: application/json; charset=utf-8`, `Content-Disposition: attachment; filename="<post_name or sanitize_title(title)>.json"`, `nocache_headers()`, and the file. On problems it redirects back with the problems in a transient-backed admin notice. Warnings are not shown on download (the browser only gets a file); mention in the meta box that the CLI shows them, or show warnings in the meta box itself by running the exporter's checks on render. Pick the meta box one if it stays cheap (one query for the Plan's Exercises).
- Unsaved changes in the edit form are not in the export; the meta box says "Exports the last saved version."

**Import** (`src/PlanFile/ImportPlanScreen.php`, `templates/admin/import-plan.php`):
- Submenu under Training (`PostTypes::MENU`): page title "Import Plan", menu label "Import Plan". The existing Exercise screen keeps its label; rename its menu label to "Import Exercises" so the two are distinct (update the `.po`).
- A form posting to `admin-post.php` (`multipart/form-data`): file input (`accept=".json,application/json"`), checkbox "Check only, don't import", nonce, submit. `manage_options` only.
- Read the upload from `$_FILES['tmp_name']` with `is_uploaded_file()`; never move it into uploads. Check upload errors and size (2 MB) first.
- Problems: redirect back and list them all on the screen (store the report in a short-lived user-scoped transient, like `PlanFields` does for its notice). Check only: show the counts and "Ready to import". Success: redirect to the new draft's edit screen with a notice "Imported as a draft: N Weeks, … Publish it, then add it to a Product."
- An **Import Plan** button next to "Add New" on the Training Plans list screen, linking to the page.

## Acceptance criteria

- [ ] Export from the row action and the meta box downloads a file identical (apart from `exported_at`) to `wp ol-plans export-plan` for the same Plan.
- [ ] An Editor without `manage_options` can export a Plan they can edit but cannot see the Import Plan page.
- [ ] Bad or missing nonce on either handler: no file, no Plan, a "link expired" message.
- [ ] Uploading the broken file from ticket 01 shows every problem and creates nothing; "Check only" with a good file shows counts and creates nothing; a real import lands on the new draft's edit screen with the notice.
- [ ] Not a JSON file, a 3 MB file, and no file each give one clear message.
- [ ] Checked end to end in a headless browser on the local site (Chromium is installed; see CLAUDE.md for the stack).
- [ ] Strings are translatable; `npm run lint:php` and `npm run analyse:php` pass.

## Comments
