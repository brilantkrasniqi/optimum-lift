# Spec: the Plans plugin

Status: implemented, awaiting review (2026-09-16). Ticket 10 (ACF Pro via Composer) is open.

Read `CONTEXT.md` first. This spec uses its words: **Plan**, not *Program*;
**Workout**, not *Day*; **Customer**, not *client*.

## Why this exists

The launch map ruled the fitness tracker and a Customer account area out of
scope, and left Plan-authoring and PDF tooling for later. On 2026-09-16 the user
reversed that and asked for the full system. This is the new effort the map's
*Out of scope* section called for.

The goal: **one source of truth for every Plan.** Optimum Lift authors a Plan
once. Customers who buy a Product that includes it get Access. The Download
(PDF) and the Portal are two views of the same Plan data. Workout Logs record
what Customers actually did and never change the Plan.

Decisions: ADR-0003 (plugin + ACF in code + stable uids), ADR-0004 (custom
tables for Access and performance), ADR-0005 (Dompdf Downloads).

## Where it deviates from the pasted blueprint, and why

| Blueprint | Here | Why |
| --- | --- | --- |
| `fitness_program`, "Program" | `ol_training_plan`, "Training Plan" | *Program* is a retired term in `CONTEXT.md`. |
| "Workout" meaning Monday/Wednesday | Workout = authored session, no weekday | *Day* is an avoided term; a Workout is not a calendar date. |
| `week_focus` text on each Week | Optional **Phases** repeater (name, first Week, last Week) | Phase is the glossary term. A one-Week Phase covers "Deload". |
| Prescription: sets, reps, rest, note | + target type (reps/seconds), intensity (RPE), `uid` | Matches the glossary Prescription; timed holds exist. |
| Logs reference `week_number` + `workout_index` | Logs reference `workout_uid`; Logged Sets reference `prescription_uid` | Row indexes shift when an author inserts or duplicates a row, which would re-point every past log. |
| `is_pr` column on each set | Personal Records computed at read time | A stored flag goes stale when a set is edited or deleted. |
| One Plan per Product (Post Object) | One or more Plans per Product | `CONTEXT.md` names bundles as a Product kind. |
| Access worked out from orders on every page load | `ol_access` rows written on payment | Access must not change when a Product is edited. |
| Muscles and equipment as free Select fields | Same, with the choices defined once in PHP | Adding one is a one-line diff and labels stay translatable. |
| Single image + video | Static image (PDF), animation (web), video URL | The PDF cannot show a GIF. |
| Build order ends with the PDF | The Download comes before the Portal | The Download is what release 1 sells; the Portal is additive. |

## Components

All code lives in `plugins/optimum-lift-plans/` (namespace
`OptimumLift\Plans`, text domain `optimum-lift-plans`). It needs WooCommerce
and ACF Pro; if either is missing it shows an admin notice and does nothing
else. The theme may override templates from `optimum-lift-plans/` inside the
theme, but it never stores Plan data.

### Content (ACF, registered in PHP)

**`ol_exercise`**: title = Exercise name, content unused.

| Field | Name | Type |
| --- | --- | --- |
| Primary muscle | `primary_muscle` | Select |
| Secondary muscles | `secondary_muscles` | Select, multiple |
| Equipment | `equipment` | Select, multiple |
| Difficulty | `difficulty` | Select |
| Image (static, used in PDF) | `image` | Image (ID) |
| Animation (GIF, web only) | `animation` | Image (ID) |
| Video | `video_url` | URL |
| Instructions | `instructions` | WYSIWYG |

**`ol_training_plan`**: title = Plan name, featured image = cover.

| Field | Name | Type |
| --- | --- | --- |
| Summary | `summary` | Textarea |
| Goal | `goal` | Text |
| Target audience | `target_audience` | Text |
| Difficulty | `difficulty` | Select |
| Phases | `phases` | Repeater: `name` Text, `first_week` Number, `last_week` Number |
| Weeks | `weeks` | Repeater (collapsed, one per Week, numbered by position) |
| ↳ Workouts | `workouts` | Repeater |
| ↳↳ uid | `uid` | Text, hidden, generated |
| ↳↳ Name | `name` | Text, e.g. "Upper body" |
| ↳↳ Prescriptions | `prescriptions` | Repeater |
| ↳↳↳ uid | `uid` | Text, hidden, generated |
| ↳↳↳ Exercise | `exercise` | Post Object → `ol_exercise` (ID) |
| ↳↳↳ Sets | `sets` | Number ≥ 1 |
| ↳↳↳ Target type | `target_type` | Button group: reps / seconds |
| ↳↳↳ Target | `target` | Text ("8", "8–10", "AMRAP", "30") |
| ↳↳↳ Intensity | `intensity` | Text ("RPE 8"), optional |
| ↳↳↳ Rest | `rest_seconds` | Number, optional |
| ↳↳↳ Notes | `notes` | Textarea, optional |

A Week's number is its position. Weeks carry no uid: a Customer's place in a
Plan is found through the Workout uid.

Validation on save: Phases must sit inside the Plan's Weeks and must not
overlap. The truncation sentinel must be present (ADR-0003).

**`product`**: `plans`, Post Object → `ol_training_plan`, multiple, IDs.

`PlanRepository::find(int $planId): ?Plan` reads ACF once and returns immutable
value objects (`Plan`, `Week`, `Workout`, `Prescription`, `Exercise`). The
Portal, the Download and the REST API use only these objects, never
`get_field()` directly.

### Data (custom tables, ADR-0004)

```sql
{prefix}ol_access (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NULL,          -- always set today; NULL reserved for manual grants
  product_id BIGINT UNSIGNED NULL,
  granted_at DATETIME NOT NULL,           -- UTC
  revoked_at DATETIME NULL,
  UNIQUE KEY order_plan (order_id, plan_id),
  KEY user_plan (user_id, plan_id)
)

{prefix}ol_workout_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NOT NULL,
  workout_uid CHAR(36) NOT NULL,
  week_number SMALLINT UNSIGNED NOT NULL,  -- snapshot for display only
  workout_name VARCHAR(191) NOT NULL,      -- snapshot for display only
  status VARCHAR(20) NOT NULL,             -- in_progress | completed
  notes TEXT NULL,
  started_at DATETIME NOT NULL,
  completed_at DATETIME NULL,
  updated_at DATETIME NOT NULL,
  KEY user_plan (user_id, plan_id),
  KEY user_workout (user_id, workout_uid)
)

{prefix}ol_logged_sets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workout_log_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  exercise_id BIGINT UNSIGNED NOT NULL,
  prescription_uid CHAR(36) NOT NULL,
  set_number TINYINT UNSIGNED NOT NULL,
  load_kg DECIMAL(6,2) NULL,               -- NULL = bodyweight / not recorded
  reps SMALLINT UNSIGNED NULL,
  seconds SMALLINT UNSIGNED NULL,
  performed_at DATETIME NOT NULL,
  UNIQUE KEY log_set (workout_log_id, prescription_uid, set_number),
  KEY user_exercise (user_id, exercise_id, performed_at)
)
```

### Flows

1. **Author**: Training › Exercises, then Training › Training Plans. Shift-click
   a Week's duplicate icon, then edit the copy.
2. **Sell**: a virtual Product with Training Plans set. The theme marks virtual
   Products as sold individually.
3. **Grant**: `woocommerce_order_status_processing|completed` → for each line
   item's Product, for each Plan → insert `ol_access` if absent. If the order
   has no customer, find the user by billing email or create one (WooCommerce
   sends the set-password email), then assign the order to that user.
4. **Revoke**: `woocommerce_order_status_refunded|cancelled` → set `revoked_at`
   on that order's rows.
5. **Deliver**: the order-received page, the processing/completed emails and
   My Account › Downloads link to the Download and to the Portal.
6. **Follow**: My Account › Plans (`/my-account/plans/`) lists the Plans the
   Customer has Access to. `/my-account/plans/{id}/` shows Weeks, Workouts and
   completion ticks. `/my-account/plans/{id}/workout/{uid}/` is the logging
   screen.
7. **Log**: the logging screen is server-rendered, then enhanced with JS that
   saves each set to the REST API as it changes. Failed saves are queued in
   `localStorage` and retried, because gym signal is poor.
8. **Progress**: the Exercise history and the Personal Record are shown next to
   each Prescription on the logging screen, and a "New Personal Record" badge
   appears when a saved set beats it.

### REST API (`ol/v1`, cookie auth + `wp_rest` nonce, Access-checked)

| Method | Route | Does |
| --- | --- | --- |
| POST | `/workout-logs` | `{plan_id, workout_uid}` → reuse the Customer's in-progress log for that Workout or start one |
| PUT | `/workout-logs/{id}/sets` | `{prescription_uid, set_number, load_kg, reps, seconds}` → upsert; returns `{personal_record: bool}` |
| DELETE | `/workout-logs/{id}/sets` | `{prescription_uid, set_number}` |
| POST | `/workout-logs/{id}/complete` | `{notes}` |
| GET | `/exercises/{id}/history` | the Customer's Logged Sets and Personal Record for the Exercise |

### Download

`GET /my-account/plans/{id}/download/` (Customers with Access) and an admin
"Preview PDF" link on the Plan edit screen (`edit_post`). Cover, Phase overview,
then one section per Week with a table per Workout, then an Exercise appendix
with the image and instructions. Cached per Plan, keyed by a hash of the
rendered HTML, and pre-rendered by WP-Cron after a Plan is saved. The cache
folder denies direct HTTP access.

## Out of scope for this effort

- Body measurements, progress photos, volume and consistency charts.
- A custom React/Vue Plan builder (ACF until it hurts).
- Nutrition Plan structure (its own modeling session).
- Buyer name in the PDF footer (ADR-0005 consequence).
- Trainer or multi-author tooling (still out of scope, per the map).
- Albanian translations. Strings are translatable, but the `.po` file is a
  separate task.

## Tickets

See `issues/`. The order is the build order.
