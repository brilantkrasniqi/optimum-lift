# ADR-0002: Tailwind CLI and esbuild, not Vite; theme.json owns the design tokens

**Status:** Accepted (2026-09-16)

## Context

The theme is styled with Tailwind v4 and uses Splide for sliders, so it needs a
build step. It is a classic PHP theme: WordPress enqueues fixed asset URLs from
`inc/assets.php`, and the block editor reads colours and fonts from `theme.json`.

Two build setups were considered:

1. **Vite.** Hot reload, but WordPress then has to read Vite's manifest and switch
   between the dev server and built files. That is extra PHP to maintain for a
   theme with one stylesheet and two scripts.
2. **Tailwind CLI + esbuild, run from npm scripts.** Each turns one input into one
   output at a fixed path, so `inc/assets.php` enqueues the same URLs in dev and
   production, and cache-busting stays mtime/version based.

## Decision

Option 2. Sources live in `assets/src/`, output in `assets/dist/` (not committed).

Tailwind colour and font tokens are declared with `@theme inline` and point at
WordPress preset variables (`--color-accent: var(--wp--preset--color--accent)`),
so `theme.json` is the single source of truth and the editor and front end cannot
drift.

Splide's JS is a separate bundle, registered but not enqueued; templates that
render a slider enqueue it. Its core CSS is small and bundled into `main.css`,
because a stylesheet enqueued mid-template would print in the footer.

## Consequences

- No hot reload: saving triggers a rebuild and the browser needs a refresh.
- A new colour is added to `theme.json` first, then mapped in `main.css`.
- Tailwind's preflight resets headings, lists and links, so editor-authored
  content needs the `prose` class (`@tailwindcss/typography`).
- WooCommerce's stylesheets are not in a cascade layer, so they beat Tailwind
  utilities regardless of specificity. Styling WooCommerce markup with utilities
  may mean dequeuing its stylesheets on those pages. Not decided yet.
- Whether `assets/dist/` is built on deploy or committed is left to the hosting
  decision (issue 08).
