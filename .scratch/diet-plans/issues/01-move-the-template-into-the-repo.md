# Move the diet template into the repo

Type: task
Status: resolved
Blocked by: none

## What to build

Read the spec's "For the agent building this" section first. This ticket changes no output. It moves the template and adds two options that later tickets need.

**Copy into `content/diets/`** from `/mnt/project-files/diet-plans/template/`:
- `render.js`, `style.css`, `foods.json`, `sizes.json` and `README.md`.
- `plans/djegie-yndyre.json` and `plans/djegie-e-shpejte.json`.
- An empty `photos/` folder with a `.gitkeep`.

Do not copy `fonts/` or `out/`.

**Fonts from the theme.** `render.js` reads `anton-latin-400-normal.woff2` and `inter-latin-wght-normal.woff2` from `themes/optimum-lift/assets/fonts/`, resolved from `__dirname`, so the PDFs and the site share one copy.

**`content/diets/package.json`:**
- `"private": true` and `"type": "commonjs"`. The root `package.json` is `"type": "module"`, and `render.js` uses `require()`.
- `playwright` as a devDependency, pinned to the version of the global copy (`/opt/node-tools/node_modules/playwright/package.json`).
- Scripts `render` (`node render.js`) and, from ticket 04, `catalogue`.
- Commit its `package-lock.json`. Keep `render.js`'s fallback to the global Playwright, so a cloud session can render without `npm install`.

**`.gitignore`:** add `/content/diets/out/` and `/content/diets/node_modules/`.

**New options in `render.js`:**
- `--out <dir>`: where PDFs go. Default `content/diets/out`. Output is `<dir>/<slug>/<file>.pdf`, as today. In project threads, Claude passes `--out /mnt/project-files/diet-plans/out`.
- `--dump`: writes each Size's computed plan to `<dir>/<slug>/<file>.json` and **skips Playwright and the PDFs**, so it runs anywhere Node does. The dump holds:
  - per day: `title`, `kcal_target`, and the day totals;
  - per meal: `slot`, `time`, `name`;
  - per ingredient: `food`, `grams`, `count` (when it has a unit), `label`, `note`, `household`;
  - the meal totals and the scaled `swap` text.
  - Format: numbers rounded to 0.1, keys in a fixed order, 2-space JSON, LF line endings and a trailing newline. Two runs on the same input give the same bytes.
- Paths in messages are relative to `content/diets/`.

**Baseline for ticket 03.** Run `--dump` on both plans and commit the result under `content/diets/tests/baseline/<slug>/`. Ticket 03 compares against it and then deletes it.

**README:** update the usage block and the files table for the new location, the options and the fonts. Leave "Instructions for Claude filling a diet" for ticket 04.

## Acceptance criteria

- [x] `node content/diets/render.js content/diets/plans/djegie-yndyre.json` (from the repo root) and the same command from inside `content/diets/` both write 10 PDFs to `content/diets/out/djegie-yndyre/`.
- [x] The calorie table printed for each plan is identical to the shared-folder copy's output (diff the two console outputs).
- [x] The PDFs embed Anton and Inter (`pdffonts`, or a `--png` page that clearly shows both fonts).
- [x] `--out` writes elsewhere. `--dump` writes 10 (or 5) JSON files and no PDF, and runs twice with byte-identical results.
- [x] `--dump` works with Playwright uninstalled (rename the global copy temporarily, or run it in a container without it).
- [x] After a render, `git status` shows nothing under `content/diets/out/`.
- [x] The baseline dumps are committed: 10 files for `djegie-yndyre`, 5 for `djegie-e-shpejte`.

## Comments

### 2026-10-07 (Claude, cloud session)

**Changed:** `content/diets/` (`render.js`, `style.css`, `foods.json`, `sizes.json`, `README.md`, `plans/*.json`, `photos/.gitkeep`, `package.json`, `package-lock.json`, `tests/baseline/`), `.gitignore`.

- `render.js` reads the fonts from `themes/optimum-lift/assets/fonts/` (same bytes as the template's copies: md5 `912c3c9f…` and `260c81a4…`).
- New `--out <dir>` and `--dump`. A `.json` that follows `--out`/`--only` is never taken for the plan. Paths in messages are relative to `content/diets/` (absolute when `--out` is outside it).
- The dump also carries each meal's `minutes` and `steps`, so ticket 03 proves those survived the move too.
- `package.json` pins `playwright` 1.56.1 (the global copy's version). `render.js` keeps its fallback to the global copy.

**Verified:**
- From the repo root and from `content/diets/`: 10 PDFs in `content/diets/out/djegie-yndyre/` and 5 in `out/djegie-e-shpejte/`. The console output of both plans is byte-identical to the shared-folder copy's (`diff` empty), and neither prints a WARNING.
- `pdffonts` on `djegie-yndyre-femer-60-70kg.pdf` lists `Anton-Regular` and `Inter-Regular`, embedded.
- `--out <scratch>/o1 --only f-50-60` wrote one PDF there. `--dump` twice into two folders: `diff -r` empty, 15 JSON files, no PDF.
- With both `node_modules/` and the global `playwright` moved away (`require('playwright')` fails), `--dump` gives the same files.
- `git status` after a render shows nothing under `content/diets/out/`.
- Baseline committed: `tests/baseline/djegie-yndyre/` (10 files), `tests/baseline/djegie-e-shpejte/` (5).
