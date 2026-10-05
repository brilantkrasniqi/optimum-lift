# Albanian instructions

Type: task
Status: needs-triage
Blocked by: 03

## What to build

Deferred by the user on 2026-10-05: instructions are imported in English first.

Translate the 200 instructions into Albanian, with a review sheet for the user or a native speaker. Put the reviewed text into `exercises.json` and roll it out with `wp ol-plans import-exercises --update --fields=instructions`. Exercise names stay English.

Open questions before starting: who reviews the Albanian, and whether customers should also see the English (a per-language field) or only Albanian.

## Acceptance criteria

- [ ] All 200 instructions are Albanian and reviewed.
- [ ] The Portal's "How to" panel and the PDF's Exercise appendix show them correctly, including ë and ç.
- [ ] Exercises whose instructions were edited by hand on production are not overwritten without the user choosing that.

**2026-10-05:** The rollout above assumes WP-CLI on production. The **Training › Import** screen (ticket 04) only fills in missing fields; it has no `--update`. If the host has no WP-CLI, this ticket also needs an "update instructions" option on that screen.
