# Content pages: translations and the editor stylesheet

Type: task
Status: resolved
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

- [x] Log in to wp-admin (admin/admin) and screenshot the block editor on a page such as "Politika e kthimit" and on a post: readable dark-on-light text, Inter body and Anton headings.
- [x] Nothing changes on the front end. Screenshot `/?s=plan`, `/nonexistent/` and a legal page before and after.
- [x] `10b.json` parses.

## Answer

Built (2026-09-24):

- **`languages/src/10b.json`**: 26 new strings. "Previous"/"Next" reuse 08a's identical keys ("E mëparshme"/"Tjetra") and "Page" is 02's, which makes the 28 the ticket counts. The 3 plurals have plural objects. The account-login hint quotes WooCommerce's own Albanian "Harruat fjalëkalimin tuaj?", which is what the login form shows.
- **`assets/css/editor.css`**: a light canvas on `.editor-styles-wrapper`: white background, `#14181b` text (`#27272a` for paragraphs, lists and tables), Inter (`--wp--preset--font-family--sans`) for text, Anton uppercase for headings and the post title, plus accent-red links, a red quote rule, and light `hr`/`code` colours.
- **Fix outside my files: `inc/setup.php`** (ticket 01, resolved). `add_editor_style()` had never taken effect, because the theme didn't declare `add_theme_support('editor-styles')`; the editor iframe loaded no `editor.css` at all. I added that one line (with a comment) just before `add_editor_style()`. Without it this ticket couldn't meet its acceptance.

Evidence:

- **Block editor** (Playwright, admin, the canvas iframe): on "Politika e kthimit" (page 11) and "Hello world!" (post 1) the canvas is `rgb(255,255,255)` with text `rgb(20,24,27)`, paragraphs `rgb(39,39,42)`, body font Inter, and headings/title Anton in `rgb(7,8,10)`. Before the fix it was `rgb(7,8,10)` background with `rgb(228,228,231)` text, all in Inter. Screenshot: the page's title in Anton on white, readable dark body text.
- **Front end unchanged**: `/?s=plan`, `/nonexistent/` and `/politika-e-kthimit/`, fetched with and without the two changes. After stripping nonces, the only diff is the urgency bar countdown's seconds, which is the clock ticking. `editor.css` isn't referenced on the front end.
- `10b.json` parses; lint exit 0; PHPStan OK; `debug.log` unchanged.
