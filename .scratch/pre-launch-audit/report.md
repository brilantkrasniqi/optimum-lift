# Pre-launch audit

Date: 2026-10-04 · Site: local stack (WordPress 7.1.2, WooCommerce 11.1.0, theme 0.1.0, Plans 0.1.0)

**Method.** Every URL in the sitemap plus cart, checkout, My Account, search and 404, crawled at 390px with Edge: status, head tags, H1s, images without alt, console errors, horizontal overflow, and every internal link. Then by hand, signed out and as a Customer, at 390px and 1440px:

- the guest purchase funnel (Product → Buy Now → checkout → order received);
- the account flows (register, log in, log out, lost password, orders, downloads);
- the Portal and workout logging;
- the PDF;
- the order and new-account emails, rendered;
- the cart drawer;
- the store settings;
- the public attack surface.

## Fixed in this pass

| What | Where |
| --- | --- |
| No account entry point anywhere in the navigation | Header button + mobile-menu group; dashboard leads with the Customer's Plans (storefront ticket 14) |
| Registration was off, so "create an account" was impossible | Turned on, with a password field; Albanian privacy text |
| With two forms on My Account, login and register were squeezed side by side on phones: Tailwind generated `col-1`/`col-2` utilities that WooCommerce's markup also uses | `main.css` excludes them |
| A Workout could be finished with nothing logged, or after one set | Server rule + confirmation dialog (Plans ticket 11) |
| Two quick edits of one set could reach the server out of order, keeping a set the screen showed as cleared | `portal.js` sends edits of a set in order |
| The whole Portal, the PDF, the thank-you "Your Plans" box and the email block were English | Plans plugin translated to Albanian (166 strings) |
| WooCommerce pages titled "Shop", "Cart", "Checkout", "My account" (browser tab, page heading) | Dyqani, Shporta, Pagesa, Llogaria ime |
| Dates printed "4 Tetor, 2026"; site timezone UTC, so offers ended 2 h off Kosovo time | `j F Y`, Europe/Belgrade |
| Home page `<title>` was only "Optimum Lift" | Tagline set: "Programe stërvitjeje dhe plane ushqimore në shqip" |
| The admin login name was public: `/wp-json/wp/v2/users`, `/?author=1` → `/author/admin/`, users sitemap | `mu-plugins/ol-hardening.php`: all three 404; XML-RPC login methods and pingbacks off |
| Plan progress bar rendered dark green on grey | Drawn in the theme's acid |

## Launch blockers: do not take money until these are done

1. **There is no real payment method, and Cash on delivery gives Plans away.** COD is enabled with "enable for virtual orders". A COD order for a virtual Product goes straight to *processing*, and the Plans plugin grants Access on *processing*: anyone gets any Plan without paying. Orders #91–#93 today did exactly this. Turn COD off and connect the card gateway (launch tickets 02, 11).
2. **Three of five Products deliver nothing.** Only *Programi i Stërvitjes 12-Javor* (60) and *Transformimi Total* (64) grant a Plan, and both grant the same demo Plan 31.
   - *Forcë & Masë*, *Plani Ushqimor 12-Javor* and *Dieta Mesdhetare* grant none. Buying one shows "Plani yt është gati" with nothing to open (verified with order #100).
   - The bundle promises every program and every diet.
   - Nutrition Plans have no structure in the plugin yet. Every Product on sale must have its Plans set (Product › Training Plans), or come off sale.
3. **Plan content is a demo.** Plan 31's Workouts and Exercises are English placeholders ("Full body", "Barbell back squat"). Launch ticket 05.
4. **Legal pages are placeholders, and the seller is anonymous.**
   - Terms, privacy and refund pages open with "[Tekst shembull: zëvendësoje …]".
   - The store address is empty.
   - Selling to the EU diaspora requires the trader's identity and geographic address, and the no-refund waiver (ADR-0009) depends on the terms being real. Launch tickets 10, 11.
5. **Email is the only way into the account after a guest checkout.** The account is created with a set-password link sent by email, and lost password is email too. Production needs a transactional mail service with SPF/DKIM on the domain. The sender is still `shop@localhost.local` (WooCommerce › Settings › Emails), and the emails tell customers to reply to it.
6. **Never migrate the local database.** It holds seeded demo reviews ("4,8 · 45 vlerësime") and sales counts ("159 të shitura"). ADR-0008 says proof comes from real data; shipping these would be fake reviews.

## Before running ads

7. **Link previews and favicon.** No page has a meta description or Open Graph tags, and there is no site icon. A Product link shared on WhatsApp or Facebook shows no image or description. Either install an SEO plugin through `plugins.txt` (ADR-0001), or add description and `og:` tags in the theme. Set a Site Icon (your untracked `assets/brand/optimum-lift-mark.svg` would do as a PNG).
8. **Purchase tracking and consent.** Conversion tracking is release-1 scope (launch ticket 09) and not built. WooCommerce's order attribution already sets `sbjs_*` cookies, and the Meta Pixel will add more. EU visitors need a consent banner before those.
9. **VAT.** Taxes are off (`woocommerce_calc_taxes = no`). Digital sales to EU consumers owe VAT at the buyer's rate (launch ticket 07).
10. **WordPress defaults still public:** "Hello world!" (with comments open), "Sample Page", the "Uncategorized" category. Delete them and set comments off by default.

## Production configuration

- `WP_DEBUG_LOG` off, or the log moved outside the web root. Locally `/wp-content/debug.log` is downloadable.
- Block `xmlrpc.php` at the web server. `ol-hardening.php` turns off its login methods, but the endpoint still answers.
- Use an admin account not named `admin`; 2FA for administrators.
- HTTPS, daily off-site backups (files + database), a staging site set to "discourage search engines".
- Deploy `mu-plugins/ol-hardening.php` with the theme and plugin (not `ol-dynamic-host.php`, which is local-only).
- WooCommerce has an update available. Update and re-test checkout before launch, not after.
- Registration is open with no CAPTCHA. Watch for bot accounts; add Cloudflare Turnstile if they appear.

### Settings `wp ol-shop seed` sets locally, which production needs by hand

The seed refuses to run outside `local`.

| Setting | Value |
| --- | --- |
| Settings › General › Tagline | Programe stërvitjeje dhe plane ushqimore në shqip |
| Timezone | Europe/Belgrade |
| Date format | `j F Y` |
| Site language | Shqip |
| WooCommerce › Accounts: allow customers to create an account on My Account | on |
| WooCommerce › Accounts: send password setup link | off (registration asks for a password; guest orders still get the link) |
| Registration / checkout privacy text | the Albanian texts in `inc/cli.php` `configure()` |
| Page titles | Dyqani, Shporta, Pagesa, Llogaria ime |
| Cart / Checkout pages | the classic shortcodes (ADR-0007) |
| Terms checkbox text | empty (the waiver is the checkbox) |
| Price format | `.` thousands, `,` decimals, 2 decimals, symbol right with space |
| Coming soon | off |
| Cash on delivery | **off** |

## Polish (not blocking)

- WooCommerce's own Albanian uses the formal *ju* ("Hyni", "Harruat fjalëkalimin tuaj?", the dashboard text), while the theme and Portal say *ti*. It reads mixed on My Account; a few `gettext` overrides would align it.
- A Customer who registers without a name is greeted by the generated username ("qa.new1791…").
- The homepage before/after cards are placeholder silhouettes (real content follow-up).

## Verified working

- **Funnel:** Buy Now → checkout → order received. The four fields and the required withdrawal waiver validate in Albanian. The thank-you page links to the Plan and the PDF.
- **Signed-out access:** the PDF (38 KB, about 0.7 s) is behind login; signed out you get the login form.
- **Crawl:** every page 200 (404 where expected), one H1 each, `lang="sq"`, no console errors, no broken internal links, no overflow at 390px. Cart, checkout and account pages are noindex.
- **Page weight:** about 130 KB and 7 requests on home, shop and Product pages.
- **Cart drawer:** add opens it with the badge at 1; remove empties it.
- **Workout logging:** autosave, offline queue, resume, Personal Records, and the new finish rules (39 checks).

## Test data left in the local database

- **Users:** 13 `qa.lifter` (password `QaLift-2026!`, has Plan 31 and workout logs), 14–15 and 17 (registration tests), 16 `arta.berisha` (guest order #99).
- **Orders:** #94 (completed, for user 13), #99 and #100 (COD, guest).

Remove them with `wp user delete 13 14 15 16 17 --reassign=1` and `wp wc shop_order delete <id> --force=true --user=1` when you are done looking.
