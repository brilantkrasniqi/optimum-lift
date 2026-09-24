# Review and fix: code (escaping, CSRF, dead code, contract drift)

Type: task
Status: ready-for-agent
Wave: 3
Parent: 11
Blocked by: 11c

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Run the 11 sub-tickets one at a time.

## What to do

Review the whole theme (`git diff f87b422 -- themes/optimum-lift`) along with the storefront's hooks into the Plans plugin:

- **Escaping.** Escape at output: `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` for editor HTML. Token output from `optimum_lift_replace_tokens()` is already HTML: check it is never double-escaped, and never printed without having gone through that function.
- **State changes.** Check nonces and CSRF on everything that changes state: `ol_remove_from_cart`, `ol_swap_to_bundle`, the admin notices, and the `?ol_buy_now` / `?ol_coupon` GET handlers. For the GET handlers, confirm the risk is acceptable and write down why.
- **Input.** Sanitise and cast all input: `absint` and `wc_clean`, never raw `$_GET`/`$_POST`.
- **Dead code.** Remove leftover stubs, unused helpers, CSS classes nothing uses, and old BEM styles.
- **Contract drift.** Compare the code with the spec's "Helper API", "Markup contracts" and "JavaScript" sections. Update the code, or record the deviation for ticket 13.
- **Tooling.** PHPCS and PHPStan clean, `declare(strict_types=1)` in `inc/`, and no string-built Tailwind classes.
- **Colour classes.** `grep -rn '\-ink\b' themes/optimum-lift --include=*.php` finds no Tailwind `ink` colour classes.

Verify each finding before fixing it.

## Acceptance criteria

- [ ] The Answer has a findings table (file:line, finding, verified, fix).
- [ ] Every verified security finding is fixed.
- [ ] Build, lint and PHPStan pass.
