# Link Products to Plans and grant Access

Type: task
Status: resolved
Blocked by: 03

## What to build

- The `plans` field on Products.
- `AccessRepository` over `ol_access`.
- Grant on processing/completed, including guest checkouts (find or create the user, assign the order).
- Revoke on refunded/cancelled.
- A "Plans" column in the Users list and on the order screen, showing what an order granted.
- The theme marks *virtual* Products as sold individually; it no longer checks *downloadable*.

## Acceptance criteria

- [ ] A guest order that is paid creates or attaches a user, and that user has Access.
- [ ] Marking the order completed after processing does not grant twice.
- [ ] Refunding the order revokes Access; completing it again restores Access.
- [ ] Editing the Product's Plans afterwards does not change existing Access.

## Comments

**2026-09-16 (implemented):** Verified with real orders: a guest order creates an account ("arber.krasniqi") and grants Access; processing then completed writes one row; refund revokes; completing again restores; pointing the Product at another Plan afterwards changes nothing; a second guest order with the same email attaches to the same account. Deviation from "What to build": no Users-list column; Access is shown on the order screen only. Add the column if it turns out to be missed.
