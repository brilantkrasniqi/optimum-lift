# wp-admin: Export as JSON and Training › Import Plan

Type: task
Status: claimed
Blocked by: 02, 03

## What to build

Production has no WP-CLI, so both directions need wp-admin. Copy `ImportScreen`'s patterns (menu, capability check, nonce, template included with variables).

**Export** (`src/PlanFile/ExportAction.php`):
- **Export as JSON** row action on the Training Plans list (`post_row_actions`, only `ol_training_plan`, not in the trash, only if `current_user_can('edit_post', $id)`).
- A side meta box "Plan file" on the Plan edit screen, hidden on a Plan never saved (`auto-draft`): the **Export as JSON** link, the line "Exports the last saved version.", and the export's warnings, if any, as a short list. Run the exporter's checks on render; they are a few queries.
- Both link to `admin-post.php?action=ol_export_plan&plan=<id>` with a nonce (`wp_nonce_url`). The handler checks the nonce and `edit_post`, runs the exporter, and on success: `nocache_headers()`, `Content-Type: application/json; charset=utf-8`, `Content-Disposition: attachment; filename="<name>.json"` where `<name>` is `post_name`, or `sanitize_title(title)` for a draft that has none, then the bytes and `exit`. On problems: store them in a user-scoped transient (like `PlanFields::noticeKey()`), redirect back to the referring screen, and show them as an error notice.

**Import** (`src/PlanFile/ImportPlanScreen.php`, `templates/admin/import-plan.php`):
- Submenu under `PostTypes::MENU`: page title and menu label "Import Plan", `manage_options`. Change the Exercise screen's menu label (not its page slug) to "Import Exercises".
- An **Import Plan** button next to "Add New" on the Training Plans list (the `views_edit-ol_training_plan` filter or a small inline script, whichever is cleaner), shown only to `manage_options`.
- The form posts `multipart/form-data` to `admin-post.php?action=ol_import_plan`: file input (`accept=".json,application/json"`), checkbox "Check only, don't import", nonce, submit. A few lines of inline JS disable the submit button on submit, so a double click does not import twice.
- Handler: nonce and capability; `$_FILES` upload error codes (no file, too big for PHP's `upload_max_filesize`); `is_uploaded_file()`; size ≤ 2 MB; read the temp file directly, never move it into uploads. Then `PlanFile` and the importer.
- Results: problems and "Check only" results go into a user-scoped transient and redirect back to the screen, which lists them (all problems, counts, notes). Success redirects to the new draft's edit screen with a notice: "Imported as a draft: 10 Weeks, 30 Workouts, 180 Prescriptions. Review it, publish it, then add it to a Product." plus any notes.
- A short help text on the screen: what a Plan file is, that import always creates a new draft, and that every Exercise in the file must already exist on this site (link to Import Exercises).

## Acceptance criteria

- [ ] The row action and the meta box download a file byte-identical to `wp ol-plans export-plan` for the same Plan.
- [ ] A Plan with a non-library Exercise shows the warning in the meta box.
- [ ] An Editor (no `manage_options`) can export a Plan but sees neither the Import Plan menu item nor the button, and gets a 403 from the handler URL.
- [ ] A bad or missing nonce on either handler: no download, no Plan, a "This link has expired" message.
- [ ] Upload `broken.json`: every problem listed, nothing created. "Check only" with `full.json`: counts shown, nothing created. Real import: lands on the new draft with the notice.
- [ ] No file, a non-JSON file, and a 3 MB file each give one clear message.
- [ ] Checked end to end in a browser on the local site, in English and with the site language set to Albanian (strings may still be English until ticket 06).
- [ ] `npm run lint:php` and `npm run analyse:php` pass.

## Comments
