# ADR-0015: The live database is built by a script, never copied from local

**Status:** Accepted (2026-10-09)

How-to: `docs/going-live.md`.

## Context

The site goes live on a netcup VPS behind Cloudflare. The code
depends on a lot of database state that is not content: WooCommerce settings
(price format, registration, no terms checkbox), the classic cart and checkout
pages (ADR-0007), the Size attributes `pa_gjinia` and `pa_pesha` with their
terms in order (ADR-0013), the legal pages the withdrawal waiver links to
(ADR-0009), offline payment methods being off. Locally, `wp ol-shop seed`
sets all of it, mixed in with demo Products, fake reviews and sales counts.

Options considered:

1. **Copy the local database up.** Rejected: it carries the demo data, test
   orders and users, the `admin`/`admin` login and `localhost` URLs, and once
   the shop is live a second copy would overwrite real orders.
2. **Set everything by hand in wp-admin from a checklist.** Every setting is
   one more thing to forget, and nothing keeps the list in step with the code
   as it changes.
3. **A command that builds it, sharing its code with the local seed.**

## Decision

Option 3. `SiteSetup` (`themes/optimum-lift/inc/site-setup.php`) holds the
database state the code depends on. `wp ol-shop seed` uses it locally and adds
the demo data; `wp ol-shop setup` uses it on a fresh live site and adds what
only live needs (offline payments off, sample content deleted, legal pages as
drafts so placeholder text never goes public). WordPress itself, and its
admin, come from WordPress's own installer; the admin login is never `admin`.

The setup is idempotent: settings are set again, pages and terms are only
created when missing, and it creates no Products, Plans or reviews. A change
that adds database state the code depends on goes into `SiteSetup`, so both
sites get it.

## Consequences

- Production runs WP-CLI (a VPS, not managed hosting), unlike what ADR-0011
  assumed; the Plan import screen still works for the owner without it.
- Re-running the setup puts the listed settings back, so a setting the owner
  changes on purpose in wp-admin either moves out of `SiteSetup::settings()` or
  is not re-run over.
- Content still never moves between the sites except as files: Plan files
  (ADR-0011) and the Exercise library (ADR-0010).
