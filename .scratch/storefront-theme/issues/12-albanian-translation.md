# Albanian translation of the theme

Type: task
Status: ready-for-agent
Wave: 4
Blocked by: 11

## What to build

Merge `themes/optimum-lift/languages/src/*.json` into
`themes/optimum-lift/languages/sq.po` (header: `Language: sq`,
`Plural-Forms: nplurals=2; plural=(n != 1);`), check coverage against
`wp i18n make-pot` output for the theme, translate anything missing in the
mock's voice, then generate `sq.mo` and `sq.l10n.php` with `wp i18n make-mo`
and `wp i18n make-php` in the WP-CLI container. Delete `languages/src/` once
merged; `sq.po` is the source from then on.

## Acceptance criteria

- [ ] With the site language `sq`, no English theme string is visible on the homepage, shop, Product pages, drawer, checkout or thank-you page.
- [ ] 100% of `make-pot` strings are translated.

## Comments

2026-09-24: the sources are every file in `languages/src/`, including the sub-ticket files (`05.json`, `06a.json`, `06b.json`, `07.json`, `08.json`, `09a.json`, `10b.json`, …). A key defined in more than one file must carry the same translation; list any conflict in the Answer. `06a.json` held a raw 0x04 byte instead of the escape `\u0004` until 05a fixed it: check that no other file does.
