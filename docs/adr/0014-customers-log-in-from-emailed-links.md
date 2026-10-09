# ADR-0014: Customers log in from emailed links

**Status:** Accepted (2026-10-09)

## Context

Most Customers arrive from an ad, buy as guests, and get an account made for
them (ADR-0004). WooCommerce then emails a link to set a password, which most
never do, so they come back to a login form asking for a password they don't
have. Registering on My Account asked for a password too, and logged the new
account in at once without checking the email belonged to whoever typed it.

Downloads are deliberately not started at checkout: Customers fetch their
PDFs from their account, so the account is the front door.

## Decision

Customers log in from a link we email them (`inc/shop/login-link.php`).

- My Account's login form has "Email me a login link". The reply is the same
  whether or not an account uses that email, and an account gets at most one
  link a minute.
- The new-account email carries a login link instead of WooCommerce's
  set-password link (`woocommerce/emails/customer-new-account.php`).
- A link works once: 15 minutes when asked for, 24 hours in the new-account
  email. Only a keyed hash is stored, in user meta; a new link replaces the
  old one.
- Opening a link only shows a "Log in" button; the button's POST logs in.
  Mail scanners open links before people do, and would otherwise use them up.
- Registering asks for an email only and does not log in; the emailed link
  does. Otherwise anyone could register someone else's email, stay logged in,
  and receive the Plans that person later buys as a guest.
- Only customers. Anyone who can edit content logs in with a password.

We rejected a plugin (Magic Login and similar): its emails and screens would
need restyling and translating, and its WooCommerce integration is paid.

## Consequences

- Logging in depends on email arriving, so the live site needs a real sending
  service with SPF, DKIM and DMARC set up.
- Passwords still work. A Customer who wants one uses "Lost your password?";
  changing it under Account details asks for the current one, which link-only
  Customers never had.
- Login links do not stop a downloaded PDF from being shared; they only make
  an account itself harder to share, since it is tied to an inbox.
