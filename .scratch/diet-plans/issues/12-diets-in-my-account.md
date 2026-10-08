# Diets in My Account

Type: task
Status: claimed
Blocked by: 11

## What to build

Brilant's feedback from ticket 11 (2026-10-08): a bought diet is only reachable through My Account › Downloads, while the Training Plans have their own Plans tab and sit on the dashboard. Give the diets the same treatment.

- A **Diets** tab ("Dietat") in the My Account menu, straight after Plans, listing every diet the Customer can download: the diet's name, its Size, and a Download PDF button. A diet bought in a bundle is listed under the diet's own name, with "Part of <bundle>".
- The dashboard lists them too, under "Your Plans", when there are any.
- It uses the Plans Portal's list markup, so it looks like the Plans tab. WooCommerce's Downloads tab stays.

## Acceptance criteria

- [ ] With orders 857, 858, 860 and 862 on the local site, the customer's Diets tab lists each diet file once, with its Size, and each button downloads that file.
- [ ] The bundle's files read "Plani Ushqimor 12-Javor" and "Dieta Mesdhetare" (or the diet each file is), with "Part of Transformimi Total".
- [ ] The dashboard shows the diets under the Plans. A customer with no diets sees no diets section there, and an empty-state line with a shop link on the Diets tab.
- [ ] The tab sits after Plans, says "Dietat" in Albanian, and looks like the Plans tab on desktop and at 390 px.
- [ ] `npm run build`, `npm run lint:php` and `npm run analyse:php` pass.

## Comments

### 2026-10-08 (Claude)

Built in `inc/shop/account-diets.php`: the `diets` endpoint (rewrite rules are flushed once, keyed by the `optimum_lift_rewrite` option), the menu item at priority 20 after the plugin's, the dashboard list at priority 20, and the Plans Portal's stylesheet on the tab. The source is `wc_get_customer_available_downloads()`, filtered to the diet and bundle categories. A bundle file's diet is found by its plan prefix against each component's file for the same Size. Five new Albanian strings were added.
