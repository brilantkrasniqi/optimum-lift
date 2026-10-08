# Translations, ADR and glossary

Type: task
Status: resolved
Blocked by: 04, 05

## What to build

- **Albanian** for every translatable string tickets 01 to 04 added: problems, notes, the screen, the row action, the meta box, notices and the "Import Exercises" label. CLI-only messages may stay English. Follow `CLAUDE.md`: `wp i18n make-pot`, `update-po`, `make-mo`, `make-php` with `--domain=optimum-lift-plans --exclude=vendor,assets,languages,data`. Keep the plugin's existing terms (look up how `.po` already translates Plan, Week, Workout, Prescription, Exercise, Phase) and leave no new msgid untranslated or fuzzy.
- **`docs/adr/0011-plans-move-between-sites-as-json-files.md`**, in the format of ADR-0010: the decisions in the spec (one Plan per file, keys not IDs, no uids, always a new draft, strict validation, read-back after writing, files kept in `content/plans/`), and the options considered: copying the database (ADR-0010 option 3, rejected for the same reasons); WordPress's WXR export/import (carries post IDs inside the nested ACF meta, so Exercises would point at the wrong posts, and it imports uids as-is); update-in-place import (orphans Workout Logs). Link it from the spec.
- **`CONTEXT.md`**: add **Plan file** under the Training Plan structure terms: "one Training Plan written as a JSON file, naming Exercises by library key, used to move a Plan between sites. Importing one always creates a new Plan." Language only, as the file's header asks.

## Acceptance criteria

- [x] With the site in Albanian, the Import Plan screen, the meta box, the row action, and every problem in `broken.expected.txt` (via the screen) are in Albanian.
- [x] `msgfmt --statistics` (or `wp i18n` output) shows no untranslated or fuzzy plugin strings.
- [x] ADR-0011 exists and the spec links to it; `CONTEXT.md` has the term.
- [x] `npm run lint:php` and `npm run analyse:php` pass.

## Comments

### 2026-10-07 (Claude)

Albanian for every new string; `.pot`, `.po`, `.mo`, `.l10n.php` regenerated with `wp i18n` (325 strings, 0 untranslated, 0 fuzzy). With the admin in Albanian, the Import Plan screen, meta box, row action and every `broken.json` problem through the screen are Albanian (only quoted file values stay English). WP-CLI success lines stay English, like the plugin's other commands.

ADR-0011 written and linked from the spec; `CONTEXT.md` has **Plan file**. `lint:php` and `analyse:php` pass.

Also: `.gitattributes` keeps PHP and Plan files LF, because `core.autocrlf` on Windows made every PHP file fail PSR-12.
