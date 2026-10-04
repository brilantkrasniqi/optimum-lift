# Finishing a Workout: nothing logged, or much left

Type: task
Status: resolved
Blocked by: 07

## What to build

Reported from a real test: a Workout could be finished after one set of one Exercise, and also with nothing logged at all (Workout Log 6 was completed with zero Logged Sets). The rule belongs on the server, not only in the button.

- Finishing is refused while nothing is performed.
- Finishing with much of the Workout left asks the Customer to confirm: *Are you sure? You still have a lot of this Workout left.* — go back, or finish anyway.
- The rule cannot be dodged: not through the REST API, and not by finishing first and clearing the sets afterwards.

## Acceptance criteria

- [x] 0 sets: refused, with a message; no dialog.
- [x] 1 set: the dialog shows what is left; Back and Escape keep the Workout open.
- [x] Partial (every Exercise started, under 75%) asks; 80% with every Exercise started does not.
- [x] Full Workout finishes without asking.
- [x] Reload and returning from the Plan resume the same log with its sets.
- [x] A completed log whose only set is cleared goes back to in progress, and cannot be finished empty.
- [x] Direct REST calls get the same answers (422 empty, 409 much left, 200 with `confirm`).
- [x] Offline sets still queue, block finishing until saved, and save on reconnect.

## Answer

Done 2026-10-04.

- **Rule:** `Logging/WorkoutProgress` counts performed sets (at least one rep or second, `LoggedSet::isPerformed()`, the same test the Personal Record uses) against the Workout as the Plan prescribes it now. "Much left" is a Prescription with no performed set, or under 75% of the prescribed sets (`CONFIRM_BELOW`).
- **REST:** `POST /workout-logs/{id}/complete` returns 422 `ol_nothing_logged`, or 409 `ol_workout_unfinished` with the progress and a translated message unless `confirm: true`. Saving notes on an already finished Workout never asks. `PUT`/`DELETE` on sets return `log_status`; clearing the last performed set of a completed log reopens it (`WorkoutLogRepository::reopen()`).
- **Screen:** a native `<dialog>` in `templates/portal/workout.php` ("Back to the Workout" focused, "Finish anyway"), opened by `portal.js` on the 409. A theme override without the dialog falls back to `window.confirm`.
- **Found while testing, fixed:** `portal.js` sent two quick edits of the same set at once (clearing kg, then reps), and the server could apply them out of order, keeping a set the screen showed as cleared. Edits of one set are now sent in order, and a superseded edit is skipped.
- **Evidence:** 39 Playwright checks at 390px as a Customer (0 / 1 / partial / 80% / full sets, Back, Escape, reload, resume, reopen, REST bypass, 0-rep set, offline), all passing, in English and again in Albanian. Lint and PHPStan pass.
