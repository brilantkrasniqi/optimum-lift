# ADR-0003: Plans live in a plugin, authored with ACF field groups registered in code

**Status:** Accepted (2026-09-16)

## Context

Plans need one source of truth that the Portal, the Download and the shop all
read from. The launch map already ruled that the theme must never own Plan
content, so it can change without losing it.

A Training Plan is deep: Weeks hold Workouts, and Workouts hold Prescriptions.
A 12-week Plan with four Workouts a week and six Prescriptions each has 288
Prescriptions. Customers' Workout Logs point back into that structure.

Options considered:

1. **Weeks, Workouts and Prescriptions as their own posts**, linked by parent
   IDs. Every row gets a real ID, but authoring one Plan means hundreds of
   separate edit screens.
2. **One Plan post, with ACF nested repeaters, with field groups built in the ACF
   UI (or ACF JSON).** One edit screen per Plan. But anyone with admin access
   can rename or delete a field, and Workout Logs depend on that structure
   staying the same.
3. **One Plan post, with ACF nested repeaters, with field groups registered in
   PHP in the plugin.** Same single edit screen, and the structure is reviewed
   code.

## Decision

Option 3. The `optimum-lift-plans` plugin registers the `ol_exercise` and
`ol_training_plan` post types and their ACF field groups with
`acf_add_local_field_group()`.

ACF repeater rows only have a position, and positions shift when an author
inserts, reorders or duplicates a row. So every Workout and every Prescription
also gets a hidden `uid` that is generated once and never changes. Workout Logs
and Logged Sets store that `uid`, never a row index.

## Consequences

- Changing a field means a code change. The ACF UI lists these groups but
  cannot edit them.
- ACF Pro (for repeaters) is now load-bearing. It is a premium plugin, and it is
  currently in `plugins/` by hand, which breaks ADR-0001. It has to move to
  Composer before deploy.
- A large Plan sends thousands of form fields in one save. When PHP's
  `max_input_vars` limit is hit, PHP silently drops the fields past the limit,
  and a truncated save would delete the rest of the Plan. The plugin puts a
  sentinel field at the very end of the form and refuses to save without it.
  Local `max_input_vars` is raised to 10000, and the host has to allow the same
  (issue 08).
- Duplicating a row in the ACF UI copies its `uid`. On save, the plugin gives
  every repeated `uid` after the first a new one.
- A Customer always sees the current version of a Plan. Once people have logged
  Workouts against a Plan, fix it in place rather than rebuilding it. A seasonal
  edition that changes content is a new Plan.
