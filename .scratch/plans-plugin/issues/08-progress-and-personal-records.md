# Progress and Personal Records

Type: task
Status: resolved
Blocked by: 07

## What to build

The Exercise history route and the Personal Record calculation. On the Workout screen, show the previous result and the Personal Record for each Prescription, plus a "New Personal Record" badge when a set beats it. On the Plan screen, show per-Week completion ("2 / 4 Workouts").

## Acceptance criteria

- [ ] Deleting the set that held the Personal Record falls back to the next best set.
- [ ] A bodyweight set (no load) never counts as a Personal Record.

## Comments

**2026-09-16 (implemented):** Deleting the 82.5 kg set moved the Personal Record back to 80 kg; a bodyweight set never counts. Re-saving the record set shows the badge again (the record is compared against every other set). The spec's per-Week completion ("1 / 3") is on the Plan screen.
