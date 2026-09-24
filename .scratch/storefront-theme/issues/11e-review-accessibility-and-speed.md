# Review and fix: accessibility and speed

Type: task
Status: ready-for-agent
Wave: 3
Parent: 11
Blocked by: 11d

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Run the 11 sub-tickets one at a time. This is the last one; resolve parent 11 when you are done.

## What to do

- **Keyboard paths** (Playwright, keyboard only):
  - skip link;
  - header nav, then the mobile menu: open, trap, Escape, restore focus;
  - the drawer, with the same checks as the menu;
  - the goal tabs (arrows, Home/End);
  - the FAQ accordion (no focus inside a collapsed answer);
  - the exit modal (trap, Escape);
  - gallery thumbnails;
  - checkout.

  Focus must stay visible everywhere (the acid outline).
- **Screen-reader semantics:**
  - dialog roles and labels (menu, drawer, exit modal);
  - `aria-expanded` and `aria-controls`;
  - `inert` behind open dialogs;
  - live region for drawer notices;
  - headings in order on every page.
- **Contrast on dark:** body text, muted text, buttons (especially white on accent), acid on dark, links, form errors, and the Portal.
- **Layout shift:**
  - fonts: Anton preloaded, `font-display`;
  - the urgency bar and the sticky bars;
  - images with width and height;
  - measure CLS with Playwright `PerformanceObserver` on `/`, 60 and `/shop/` at 390px.
- **Weight:**
  - `assets/dist/main.css` and `main.js` sizes after `npm run build`;
  - no WooCommerce stylesheet or jQuery on non-store pages;
  - no Google Fonts or other third-party request;
  - Splide only where it's used.

Verify each finding before fixing it.

## Acceptance criteria

- [ ] The Answer has a findings table and the measured numbers: CLS per page, CSS/JS bytes (raw and gzip), and requests per page type.
- [ ] Every verified finding is fixed or deferred with a reason.
- [ ] Build, lint and PHPStan pass.
- [ ] Parent 11 is resolved.
