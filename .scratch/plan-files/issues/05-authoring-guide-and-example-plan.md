# Authoring guide for Claude, and a worked example

Type: task
Status: claimed
Blocked by: 03

## What to build

What a Claude session reads when the owner says "create a Plan like this".

**`docs/plan-files.md`**:
- What a Plan file is and the workflow: write in `content/plans/`, dry-run, import as a draft locally, the owner reviews (wp-admin, Portal, PDF preview), then exports from local wp-admin or uploads the repo file on production's **Training › Import Plan**.
- The format: the spec's table and example (copy them; the guide must stand alone).
- **Choosing Exercises**: only keys from `plugins/optimum-lift-plans/data/exercises/exercises.json`. Filter by `settings` (a home Plan uses only Exercises whose `settings` include `home`), `equipment` (only what the owner says is available), and `difficulty` (no `advanced` Exercises in a beginner Plan unless asked). Balance with `pattern` across each Week (pushes against pulls; a squat and a hinge pattern in lower-body work; some core). Include `jq` one-liners to list candidates by setting, equipment and pattern, and the PowerShell equivalent (`ConvertFrom-Json` + `Where-Object`).
- **Programming defaults**, marked "the owner's to change": Weeks written out in full; Phases of 3 to 6 Weeks; the last Week of a Phase lighter (fewer sets or lower RPE); progression across Weeks through reps, sets or RPE; ranges for hypertrophy (`8-12`), lower reps for strength; `seconds` for holds, carries and conditioning; intensity as RPE; rest 60 to 180 s by Exercise type; Workout names that say what the Workout is ("Upper body A"); notes only where they change how the Exercise is done; the same Workout layout from Week to Week within a Phase, so Customers recognise it.
- **Commands**, PowerShell-safe, through the mounts:
  `docker compose --profile cli run --rm wpcli ol-plans import-plan /plans/<slug>.json --dry-run`, then without `--dry-run`; `export-plan <id> --file=/plans/<slug>.json` after the owner edits a draft in wp-admin, so the repo file matches.
- **Rules**: a published Plan that Customers use is fixed in place in wp-admin, never replaced by a re-import (ADR-0003); a Plan file never carries IDs or uids; the file name is the Plan's slug.
- **What to tell the owner** after importing: the edit link, a one-paragraph summary of the Plan (structure, Phases, progression, equipment), and anything assumed.

**`CLAUDE.md`**: two or three lines in the Plans paragraph: "To write a Training Plan, follow `docs/plan-files.md`; Plan files live in `content/plans/`; spec and tickets in `.scratch/plan-files/`."

**Worked example**: `content/plans/demo-body-recomposition.json`, exported from the seeded 4-week demo Plan, as the reference file the guide points to.

**Try the guide**: start a subagent with only `docs/plan-files.md`, the repo, and the brief "a 6-week beginner home Plan with dumbbells, 3 Workouts a week". Its file goes to `content/plans/` and must pass `--dry-run`. Fix the guide where the subagent went wrong, then delete that file unless the owner wants to keep it.

## Acceptance criteria

- [ ] The subagent's file passes `--dry-run` on the first try, or after fixes the guide now prevents; the guide changes are listed in the ticket.
- [ ] `demo-body-recomposition.json` is in `content/plans/` and round-trips.
- [ ] Every command in the guide was run on the owner's PC and works in PowerShell.
- [ ] `CLAUDE.md` points at the guide.

## Comments
