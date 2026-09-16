# The Download: render a Plan to PDF

Type: task
Status: resolved
Blocked by: 04

## What to build

A Dompdf renderer with a print template (spec › Download). It caches files under `uploads/ol-plans/`, which denies web access. Serve the file from `/my-account/plans/{id}/download/` behind an Access check. Add an admin preview link. Put Download entries in My Account › Downloads, on the order-received page and in the processing/completed emails.

## Acceptance criteria

- [ ] A Customer with Access downloads the PDF. A logged-out visitor, or a Customer without Access, gets a 403 or is redirected to log in.
- [ ] Albanian characters (ë, ç) render.
- [ ] Editing the Plan changes the next PDF (the cache is invalidated).

## Comments

**2026-09-16 (implemented):** The cache key is a hash of the rendered HTML instead of the Plan's modified time. Exercise edits made in code do not touch the Plan's modified time, so a date key served a stale PDF. Rendering is ~5.5 s for a 5-Week Plan, so a save schedules a WP-Cron pre-render. Direct HTTP access to `uploads/ol-plans/` returns 403 under Apache.
