# ADR-0004: Access, Workout Logs and Logged Sets live in custom tables

**Status:** Accepted (2026-09-16)

## Context

Plans are content that Optimum Lift writes. Performance is data that Customers
write, far more often: every Logged Set is a row. The Portal has to answer
questions like "every Logged Set of this Exercise for this Customer, newest
first" and "this Customer's Personal Record" quickly.

Access could also be worked out when a page loads: look up the Customer's paid
orders, then the Plans linked to each Product. But the Plans linked to a
Product can change after it is sold, and Access must not change with them (see
`CONTEXT.md`).

## Decision

The plugin creates and migrates three tables with `dbDelta`, checking a stored
schema version:

- `{prefix}ol_access`: one row per Customer, Plan and order. Written when an
  order reaches *processing* or *completed*. Revoked (`revoked_at` set, row
  kept) when the order is refunded or cancelled.
- `{prefix}ol_workout_logs`: one row per Workout Log.
- `{prefix}ol_logged_sets`: one row per Logged Set. It stores `user_id`,
  `exercise_id` and `performed_at` again, even though they could be joined from
  other tables, so Exercise history and Personal Records need a single indexed
  query.

We rejected post meta and a Workout Log post type. Neither can index by
Customer and Exercise, and the admin screens and revisions they bring are no use
for this data.

## Consequences

- There are no database foreign keys (`dbDelta` does not manage them). The
  plugin deletes a Customer's rows when the WordPress user is deleted, and
  WordPress's personal-data export and erase tools cover these tables.
- Personal Records are calculated when needed, not stored as a flag, so
  deleting or correcting a Logged Set cannot leave a stale record.
- Loads are stored in kilograms only. The market is metric.
- Access needs a user account. A guest checkout that grants Access creates the
  account (or attaches the order to the existing account with that email) and
  sends WooCommerce's set-password email, so checkout stays free of account
  fields for cold ad traffic.
