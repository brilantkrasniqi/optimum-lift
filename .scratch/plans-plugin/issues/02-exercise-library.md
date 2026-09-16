# Exercise library

Type: task
Status: resolved
Blocked by: 01

## What to build

The `ol_exercise` post type, not public, under a "Training" admin menu. Its ACF field group is registered in PHP (spec › Content). Muscle, equipment and difficulty choices are defined once, in PHP, with translatable labels. The admin list shows columns for primary muscle and equipment.

## Acceptance criteria

- [ ] An Exercise can be created with all fields in wp-admin.
- [ ] Exercises are not reachable on the front end or through the unauthenticated REST API.
