# Training Plan builder

Type: task
Status: resolved
Blocked by: 02

## What to build

- The `ol_training_plan` post type and its nested field group (spec › Content).
- Hidden `uid` fields on Workouts and Prescriptions: generated when empty, and regenerated when a duplicated row repeats one.
- The truncation sentinel that refuses a save which hit `max_input_vars`.
- Phase validation.
- `PlanRepository` and its value objects.
- A `wp ol-plans seed` WP-CLI command that creates demo Exercises, a demo Plan and a linked demo Product for local development.

## Acceptance criteria

- [ ] Duplicating a Week in the UI and saving leaves every uid unique.
- [ ] Reordering Workouts keeps each Workout's uid.
- [ ] A save that drops the sentinel changes nothing and shows an error.
- [ ] `PlanRepository::find()` returns the full tree, with Exercises resolved.

## Comments

**2026-09-16 (implemented):** Verified in a headless browser against the real edit screen:
- Shift-duplicating Week 1 and saving gave 6 Weeks with 90 unique uids, and an existing Workout Log still resolved to its original Week 1 Workout.
- Reordering Workouts keeps each Workout's uid.
- Removing the sentinel is caught twice: ACF's JS validation blocks the submit, and with JS validation bypassed the server refuses the save and shows a notice. Plan data was unchanged afterwards.

Found while testing: `update_field()` rows that leave out `uid` were never assigned one. `PlanFields::ensureUidInRows()` now adds the key before the repeater saves.
