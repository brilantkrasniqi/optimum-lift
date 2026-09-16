# Workout logging

Type: task
Status: resolved
Blocked by: 06

## What to build

The `ol/v1` REST routes (spec › REST API) and `WorkoutLogRepository`. The Workout screen gains inputs: load and reps (or seconds) for each set, prefilled with the last values the Customer logged for that Exercise. Each change autosaves, with an offline retry queue. Finishing the Workout marks the log completed and saves notes.

## Acceptance criteria

- [ ] Every route rejects requests from a user without Access to the log's Plan, and for another user's log.
- [ ] Re-saving the same set updates it; it never adds a duplicate row.
- [ ] Opening a Workout with an in-progress log resumes it.
- [ ] Losing the network, entering sets, then reconnecting saves them.

## Comments

**2026-09-16 (implemented):** Verified in headless Chromium at 390px: autosave, comma decimals ("102,5"), a load without reps is not saved, clearing a set deletes it, an offline set is queued in localStorage and saved on reconnect, Finish marks the log completed. A second customer gets 404 on every route for another customer's log. Loads are written only once reps (or seconds) are entered.
