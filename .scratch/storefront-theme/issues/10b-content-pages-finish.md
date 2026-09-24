# Content pages: translations and the editor stylesheet

Type: task
Status: ready-for-agent
Wave: 2
Parent: 10
Blocked by: 05a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`.

## What to build

The page, blog, search, 404 and comments templates and `pages.css` landed in `a5fa5db`. Two things are left.

- **Translations.** Write `languages/src/10b.json` for the 28 strings not yet defined. They come from `page.php`, `index.php`, `single.php`, `archive.php`, `search.php`, `searchform.php`, `404.php`, `comments.php` and `template-parts/content*.php`.
  - Examples: "Last updated %s", "Blog", "Search articles, programs, plans…", "Browse the shop", "Comments are closed.", "Older comments".
  - Plurals:
    - `single.php:30` "%d min read" (same text in both forms)
    - `search.php:22` "%s result"
    - `comments.php:24` "%s comment"
  - Already defined, so don't redefine: "Page" (02).
  - "Previous" and "Next" also appear in 08a's shop pagination. Whichever of the two runs second copies the first one's translation exactly.
- **`assets/css/editor.css`** is enqueued with `add_editor_style()` (`inc/setup.php:33`) and hasn't changed since the initial commit.
  - The problem: `theme.json` gives the whole site a dark background (`paper`) and light text (`ink`), and the editor inherits both. `editor.css` only sets `color:#14181c`, so the editor shows dark text on a dark background.
  - The fix: keep the editor **light**. Set an explicit light background and dark text on the editor canvas (`.editor-styles-wrapper`, which is where `add_editor_style` scopes rules), keep Inter for body text, and use Anton (`font-display`) for headings. The fonts are declared in `theme.json` and load in the editor too.

## Files you own

- `languages/src/10b.json`
- `assets/css/editor.css`

## Acceptance criteria

- [ ] Log in to wp-admin (admin/admin) and screenshot the block editor on a page such as "Politika e kthimit" and on a post: readable dark-on-light text, Inter body and Anton headings.
- [ ] Nothing changes on the front end. Screenshot `/?s=plan`, `/nonexistent/` and a legal page before and after.
- [ ] `10b.json` parses.
