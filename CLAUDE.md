# optimum-lift

## Agent skills

### Issue tracker

Issues live as markdown files under `.scratch/<feature-slug>/` in this repo. See `docs/agents/issue-tracker.md`.

### Triage labels

Default five-role vocabulary, applied as the `Status:` line in each issue file. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: `CONTEXT.md` and `docs/adr/` at the repo root. See `docs/agents/domain.md`.

## Development

The WordPress stack runs in Docker (`docker compose up -d`, site on http://localhost:8080). Front-end tooling runs on the host with Node; PHP tooling runs in the `composer` container, so no PHP is needed locally.

Only first-party code (the theme, `plugins/optimum-lift-plans`, `mu-plugins`) and `uploads` are bind-mounted. Third-party plugins live in the `wp` volume: on Docker Desktop every file check through a bind mount costs ~2 ms, and with WooCommerce mounted every page took 8 s. `docker/setup.sh` installs `plugins.txt` there and copies in any plugin dropped into `./plugins/` by hand (ACF Pro) when it is missing or its version changed, so after updating one there, run `docker compose up -d`. To read WooCommerce's source, use the container (`docker compose exec wordpress ...`), not `./plugins/woocommerce`, which may be stale.

- `npm install` then `npm run dev`: watches `themes/optimum-lift/assets/src/` and builds into `assets/dist/`. `npm run build` does a minified build. `assets/dist/` is gitignored, so the site is unstyled until one of these has run.
- `npm run composer -- install` once, then `npm run lint:php` (PHP_CodeSniffer, PSR-12) and `npm run analyse:php` (PHPStan). Both must pass before handing work back. `npm run composer -- fix` auto-fixes style. The install also installs the Plans plugin's runtime dependencies (Dompdf) into its own `vendor/`.
- The Exercise library and a demo Training Plan with a linked Product: `docker compose --profile cli run --rm wpcli ol-plans seed` (it imports the library first).
- Demo storefront (Products 60–64, the front page, coupon, legal pages, site settings; sales end 3 days after seeding): `docker compose --profile cli run --rm wpcli ol-shop seed`. The two diets and the bundle are variable Products sold in Sizes (Gjinia × Pesha, or Pesha alone for Dieta Mesdhetare), each variation with a placeholder PDF in `uploads/woocommerce_uploads/ol-demo/`.

Plans, Exercises, Access, the Download and the Portal live in `plugins/optimum-lift-plans/`, a tracked first-party plugin; the theme only styles and may override its templates (ADR-0003). Spec and tickets: `.scratch/plans-plugin/`. Plan structure changes touch the ACF field groups in `src/Content/`, and Workout Logs depend on the Workout and Prescription `uid`s staying stable.

To write a Training Plan, follow `docs/plan-files.md`. Plan files (one Plan as JSON, Exercises by library key) live in `content/plans/`, mounted at `/plans` in the `wpcli` container; `wp ol-plans import-plan` and `export-plan` move them in and out, and **Training › Import Plan** imports them on production. Spec and tickets: `.scratch/plan-files/`.

The Exercise library (200 Exercises, a still and a GIF each) ships in the plugin's `data/exercises/` (ADR-0010; spec and tickets: `.scratch/exercise-library/`). Rebuild it from the curated source folder with `node tools/build-exercise-library.mjs <folder>`, putting corrections in the script's `OVERRIDES`, never in the output. Import it with `wp ol-plans import-exercises` (`--dry-run`, `--update`, `--fields=`) or **Training › Import**. Outside the database, Exercises are named by their library key, never their post ID.

The storefront lives in the theme (ADR-0006, ADR-0007, ADR-0008; spec and tickets: `.scratch/storefront-theme/`):
- **Sections:** the Products' and the front page's sections are the ACF Flexible Content `ol_blocks` (`inc/shop/fields.php`), rendered one template per layout from `template-parts/blocks/`.
- **Cart, Buy Now and bundles:** `inc/shop/{cart,buy-now,bundle,upsell}.php` and `modules/cart.js`.
- **Checkout:** `inc/shop/checkout.php`, with the template overrides listed in `woocommerce/README.md`.
- **Urgency and proof:** only from real data (`inc/shop/{proof,offer}.php`).
- **Consent and the email list:** the Pixel and order attribution wait for the cookie banner (`inc/consent.php`, `modules/consent.js`), which shows only once a Meta Pixel ID is set; offer emails need the unticked checkout opt-in (`inc/shop/marketing.php`, ADR-0012).
- **Guarantee and refunds:** no refunds on digital Products; checkout takes a withdrawal waiver (`inc/shop/withdrawal.php`), and the guarantee is the Customizer's `guarantee` promise, never money back (ADR-0009).
- **Strings:** source strings are English. Albanian lives in `languages/sq.po`; after changing strings run `wp i18n make-pot`, `update-po`, `make-mo` and `make-php` (see ticket 12). The Plans plugin's is `plugins/optimum-lift-plans/languages/optimum-lift-plans-sq.po`, same steps with `--domain=optimum-lift-plans --exclude=vendor,assets,languages,data`.

Diets (Nutrition Plans) are written as data in `content/diets/`: the renderer, the Foods table, one Recipe per file and the plans, with its own README for writing one (spec and tickets: `.scratch/diet-plans/`). A plan renders one PDF per Size, sold as a WooCommerce variable Product (ADR-0013): the helpers and buy paths are in `inc/shop/sizes.php`, the Size PDFs box in `inc/shop/size-files.php`, the picker in `template-parts/product/size-picker.php` and `modules/size-picker.js`. Every Size of a Product costs the same. Customers download their diets from My Account › Diets (`inc/shop/account-diets.php`), also listed on the dashboard under their Plans.

Styling is Tailwind v4, configured in CSS (`assets/src/css/main.css`); there is no `tailwind.config.js`. Colours and fonts come from `theme.json`; see ADR-0002. Sliders use Splide; see `assets/src/js/slider.js` for the markup and how to enqueue it.
