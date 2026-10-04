# An account entry point in the header

Type: task
Status: resolved

## What to build

Reported from a real test: nothing in the navigation leads to the account. A Customer coming back after buying had to know `/my-account/` existed. Visitors must be able to log in or create an account, and Customers to reach their Plans, orders and account details, without clutter.

## Acceptance criteria

- [x] A visible account control at every width, matching the header's buttons, with a signed-in state.
- [x] The mobile menu offers it in words: log in / create an account, or My account, Plans, orders, log out.
- [x] Logging in lands somewhere useful: the dashboard shows the Customer's Plans.
- [x] Register, log out, wrong and right password, lost password, orders, view order, downloads, addresses, account details all work at 390px and 1440px, with no horizontal overflow.
- [x] Checkout keeps its distraction-free header.

## Answer

Done 2026-10-04.

- **Header** (`template-parts/header/site-header.php`): a 44px account button before the cart, same style. Signed out it reads "Hyr" (Log in); signed in, "Llogaria ime" with an acid dot; marked current on My Account. To fit three buttons on a phone, the logo's tagline shows from 410px and the name from 360px (`logo.php`); no overflow from 320 to 1280px.
- **Mobile menu:** an "Account" group (`optimum_lift_account_menu_links()`).
- **Dashboard:** the Plans plugin renders the Plans list under "Planet e tua" (`Portal::renderDashboard()`), with the next Workout and the PDF.
- **Registration:** turned on, with a password field (`woocommerce_enable_myaccount_registration`, `woocommerce_registration_generate_password = no`). Guest checkouts still get a set-password email. On a phone the login form links down to the register form (`#ol-register`).
- **Bug found and fixed:** Tailwind v4 generated `col-1`/`col-2` utilities (`grid-column`) from the checkout override, and WooCommerce's login markup uses those class names, so with registration on, login and register were squeezed side by side at 390px. `main.css` now excludes them (`@source not inline`).
- **Content:** WooCommerce's pages titled in Albanian (Dyqani, Shporta, Pagesa, Llogaria ime) and Albanian privacy texts; set in `wp ol-shop seed` and locally.
- **Evidence:** 42 Playwright checks; the one failure is the test reaching `/checkout/` with an empty cart (redirects to the cart). The funnel test with a real cart confirms checkout has no account button.
