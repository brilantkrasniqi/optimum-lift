# ADR-0012: Nothing tracks before consent, and offer emails need an unticked opt-in

**Status:** Accepted (2026-10-08)

Settles the consent half of launch issue 09 and adds the email list the owner
asked for.

## Context

The owner wants to email buyers about future Products, and to run Meta ads,
which need the Pixel. Both touch EU visitors (the diaspora is a target
market), where consent rules apply: marketing email needs a freely given,
specific opt-in, and any cookie that isn't needed for the site to work needs
consent before it is set. A pre-ticked or hidden box is not consent, so a list
built that way could never be emailed.

Today nothing loads the Pixel or Google tags (track.js only reports to them
when present), but WooCommerce's order attribution already sets `sbjs_*`
cookies on every visit.

## Decision

- **An unticked opt-in at checkout** (`inc/shop/marketing.php`), optional,
  in its own box right under the withdrawal waiver above "Place order",
  where buyers are already reading and ticking. It is never part of the
  required box. The order stores when it was ticked and its exact
  wording, and the order screen shows it. *WooCommerce › Email list*
  downloads everyone who said yes as a CSV for the mail service, which keeps
  unsubscribes from then on.
- **Consent by code, not a plugin** (`inc/consent.php`,
  `modules/consent.js`). The only thing to hold back is the Pixel, and all
  tracking already goes through track.js. The Customizer's Meta Pixel ID
  turns the banner on; with no ID there is no banner, because nothing needs
  consent.
- **Order attribution waits for consent too.** It is off for everyone on the
  server and switched on in the browser after "Accept", so a cached page never
  carries another visitor's choice.
- **Declining is as easy as accepting**: two equal buttons, and "Cookie
  settings" in both footers reopens the banner.

## Consequences

- Until a Pixel ID is set, WooCommerce records no order source. That is the
  price of launching without a banner.
- Events a page sends before the visitor accepts are not replayed; Meta
  only sees what happens after "Accept".
- If more tools need consent (Google Ads, analytics, embedded video), a
  consent plugin with Google Consent Mode becomes worth its weight, and this
  module should go rather than grow.
- The privacy policy must keep naming what loads after consent and who the
  mail service is.
