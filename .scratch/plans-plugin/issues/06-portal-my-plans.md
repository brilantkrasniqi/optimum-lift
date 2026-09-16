# Portal: My Plans

Type: task
Status: resolved
Blocked by: 04

## What to build

A My Account endpoint `plans` with three views: the list of Plans with Access, one Plan (its Weeks, Workouts, Phases and completion ticks), and the Workout screen (read-only in this ticket). Templates can be overridden from the theme. The plugin's CSS uses only theme.json preset variables, so it follows any block theme.

## Acceptance criteria

- [ ] A Customer sees only Plans they have Access to. A direct URL to any other Plan returns 404.
- [ ] The layout works at 360px wide.
