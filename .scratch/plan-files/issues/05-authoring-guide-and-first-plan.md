# Authoring guide for Claude, and the first real Plan

Type: task
Status: ready-for-agent
Blocked by: 03

## What to build

What a Claude session reads when the owner says "create a Plan like this", and one real Plan written with it to prove the loop.

- `docs/plan-files.md`:
  - The format (link to, or copy, the spec's table; one example).
  - **Choosing Exercises:** read `plugins/optimum-lift-plans/data/exercises/exercises.json`; use only its keys; filter by `settings` (a home Plan uses only Exercises whose `settings` include `home`), `equipment` (respect what the owner says is available), and `difficulty` (no `advanced` Exercises in a beginner Plan unless asked). Balance with `pattern` (as many pulls as pushes across a Week; a hinge and a squat pattern in lower-body work). A short `jq` one-liner to list candidates by setting and pattern.
  - **Programming conventions** for this shop, to be confirmed by the owner in review: Weeks written out in full; Phases over 3 to 6 Weeks; the last Week of a Phase lighter; targets as ranges for hypertrophy (`8-12`), lower for strength; `seconds` for holds, carries and conditioning; intensity as RPE; rest 60 to 180 s by Exercise type; notes only where they change how the Exercise is done.
  - **Where and how:** save to `content/plans/<slug>.json`; run `docker compose --profile cli run --rm wpcli ol-plans import-plan /plans/<slug>.json --dry-run`, fix every problem, import as a draft, then tell the owner the edit link and what to check (wp-admin, Portal, PDF preview). Production: the owner exports from local wp-admin or uploads the repo file directly on **Training › Import Plan**.
  - The rule that a published Plan with Customers is fixed in place in wp-admin, never replaced by a re-import (ADR-0003).
- `CLAUDE.md`: two or three lines under the Plans paragraph pointing at `docs/plan-files.md` and `.scratch/plan-files/`.
- `content/plans/README.md`: one paragraph on what the folder is.
- **The first Plan:** ask the owner for the first real Plan brief (goal, Weeks, Workouts per Week, setting, level). Write it, import it locally, round-trip it, and leave it as a draft for the owner to review. If no brief is given, convert the seeded demo Plan into `content/plans/demo-body-recomposition.json` as the worked example instead.

## Acceptance criteria

- [ ] A fresh Claude session given only "write a 6-week beginner home Plan, 3 Workouts a week" and the repo produces a file that passes `--dry-run` first time or with only typo-level fixes.
- [ ] The first Plan is in `content/plans/`, imports cleanly, round-trips, and is a draft on the local site.
- [ ] `CLAUDE.md` points at the guide.

## Comments
