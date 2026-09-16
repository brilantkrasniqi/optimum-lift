# ADR-0005: Downloads are rendered from the Plan with Dompdf

**Status:** Accepted (2026-09-16)

Supersedes the launch map's "delivery is a stock WooCommerce downloadable
product" and issue 05's "author the first PDF by hand".

## Context

Once Plans are structured data (ADR-0003), a PDF made by hand becomes a second
copy of the Plan that can drift from it. Hosting is still undecided (issue 08),
and managed WordPress hosting is the leading option.

Options considered:

1. **Upload a hand-made PDF as a WooCommerce downloadable file.** No code, but
   the PDF can disagree with the Portal.
2. **Headless Chromium** (Gotenberg, Browsershot). Best CSS support, but it
   needs a separate service or a Chrome binary that managed WordPress hosts do
   not provide.
3. **mPDF.** Pure PHP, good table support, but a much larger install because of
   its bundled fonts.
4. **Dompdf.** Pure PHP, runs on any host, and handles simple print CSS.

## Decision

Option 4. The plugin renders a Plan to PDF from a PHP template, in the background
after the Plan is saved, and again on request if the cached file is stale. The
file is cached under `uploads/ol-plans/`, keyed by a hash of the rendered HTML, so
any change to the Plan, an Exercise or the template shows up. It is served
through a permission-checked endpoint.
Products linked to Plans are *virtual* and not *downloadable*. The plugin adds
the Download to My Account › Downloads and to the order emails itself.

## Consequences

- The PDF template is plain HTML with print CSS. Dompdf does not support flexbox
  or grid, so the template uses tables and blocks, not Tailwind.
- Exercise images in the PDF are read from the attachment file on disk (the
  static image, never the animation).
- Stamping the buyer's name in the footer, which the launch map left for later,
  would mean one PDF per Customer instead of one per Plan. It is not done yet.
- Dompdf is a runtime Composer dependency of the plugin. Like `assets/dist/`,
  whether `vendor/` is built on deploy or committed is left to issue 08.
