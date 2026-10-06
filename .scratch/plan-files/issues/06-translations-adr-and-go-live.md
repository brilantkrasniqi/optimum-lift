# Translations, ADR and go-live check

Type: task
Status: ready-for-agent
Blocked by: 04, 05

## What to build

- Albanian strings for everything tickets 01 to 04 added (problems, notices, screen, row action, meta box), with the plugin steps from `CLAUDE.md` (`wp i18n make-pot`, `update-po`, `make-mo`, `make-php` with `--domain=optimum-lift-plans --exclude=vendor,assets,languages,data`). Problem messages are shown to the owner only, but keep them translated like the Exercise import's.
- `docs/adr/0011-plans-move-between-sites-as-json-files.md`: the decisions in the spec (one Plan per file, keys not IDs, no uids, always a new draft, strict validation, files kept in `content/plans/`), with the options considered: copying the database (ADR-0010 option 3, rejected for the same reasons), WordPress's built-in WXR export (carries post IDs in the nested ACF meta, so Exercises would point at the wrong posts), and update-in-place import (rejected: orphans Workout Logs).
- Go-live check on production, once the plugin version with this feature is deployed: import one Plan file through **Training › Import Plan**, open the draft, preview the PDF, then trash it (or keep it if it is a real Product's Plan). Record the result here.

## Acceptance criteria

- [ ] wp-admin in Albanian shows translated Import Plan and Export as JSON strings.
- [ ] ADR-0011 exists and the spec links to it.
- [ ] One Plan file imported on production and its draft checked.

## Comments
