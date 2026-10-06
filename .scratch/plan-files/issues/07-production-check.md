# Production check

Type: task
Status: ready-for-human
Blocked by: 06, exercise-library ticket 07 (the Exercise library imported on production)

## What to do

For the owner, once the plugin version with Plan files is deployed:

1. On local, export a Plan you want to sell (or use a file from `content/plans/`).
2. On production, **Training › Import Plan**: run "Check only" first. Every Exercise must be found; if one is missing, import the Exercise library there first.
3. Import it. Open the draft, check it, and open the PDF preview.
4. Publish it and add it to a Product, or move it to the trash if it was only a test.

Note the plugin version and anything surprising below.

## Acceptance criteria

- [ ] One Plan file imported on production and its draft checked.

## Comments
