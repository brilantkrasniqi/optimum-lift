# Exercise fields and vocabulary

Type: task
Status: resolved
Blocked by: none

## What to build

- Three new Exercise fields in `ExerciseFields`, registered in PHP like the rest (ADR-0003):
  - `library_key`: text, read-only, unique, generated from the title when empty, never changed once set. Hand-made Exercises get one on save.
  - `movement_pattern`: select, `Choices::patterns()` (the 35 source values).
  - `settings`: checkbox, `Choices::settings()` (`home`, `gym`, `crossfit`).
- `Choices::equipment()` gains the values the 200 names need. Starting set: `smith_machine`, `trap_bar`, `medicine_ball`, `exercise_ball`, `rope`, `rings`, `suspension_trainer`, `cardio_machine`, `ab_wheel`. Ticket 02 may add or drop one if the names demand it. Never rename a key once used.
- `Exercise` (the value object) and `PlanRepository` carry `key`, `pattern` and `settings`.
- Albanian labels for every new choice in `optimum-lift-plans-sq.po`, then the `make-pot`, `update-po`, `make-mo`, `make-php` steps from `CLAUDE.md`.

## Acceptance criteria

- [x] A new Exercise created by hand gets a `library_key` that is unique, and editing its title later does not change the key.
- [x] Two Exercises with the same title get different keys.
- [x] The new fields appear on the Exercise edit screen and are stored by key.
- [x] `PlanRepository::find()` returns Exercises with key, pattern and settings.
- [x] `npm run lint:php` and `npm run analyse:php` pass.

## Comments

**2026-10-05 (implemented):**

What changed:
- `src/Content/LibraryKeys.php` (new, wired in `Plugin::boot`). A key is generated from the title on the first save that has a usable title, then never changes: `protect()` on the field's `acf/update_value` filter returns the stored key whatever is posted, and `ensure()` on `save_post_ol_exercise` creates it for programmatic inserts too (it runs before ACF's own save, so the form's empty read-only value cannot clobber it). It skips auto-drafts and titles with no letters or digits. Helpers `isValid()`, `find()` (sees the trash) and `unique()` are for the importer in ticket 03.
- The slug is not `sanitize_title()`: that keeps non-ASCII as percent-encoded octets (`°` becomes `%c2%b0`), which are not valid keys. `Farmer's walk` gives `farmers-walk`, `ë` gives `e`.
- `Choices`: `patterns()` (35), `settings()` (3), and 9 new equipment values (18 in all). New equipment sits before `bodyweight`; the order is display only, stored values are the keys.
- `Exercise` now has `libraryKey`, `pattern`, `settings`; `PlanRepository` reads them.
- Albanian: 54 new strings in `optimum-lift-plans-sq.po`, compiled. They are machine-written in the register of the existing strings and **have not been reviewed by a native speaker.**

Verified on the local site:
- A throwaway `wp eval-file` check, 28 assertions, all passing: key from title; `-2` and `-3` suffixes for repeated titles; title edit and `update_field()` cannot change a key; `°`, `'` and `ë` titles; a symbols-only title gets no key until a usable title arrives; auto-drafts and untitled drafts get none; a key supplied at insert is kept; a keyless post adopts a valid unused key but refuses an invalid or already-used one; a trashed Exercise keeps its key reserved; the value object returns key, pattern, settings, muscle and equipment.
- In a headless browser on the real edit screen: the fields render with the read-only key and its placeholder; publishing creates the key; changing the title and pressing Update leaves it; a tampered key in the posted form is ignored; pattern, settings and equipment save and reload.
- `npm run analyse:php`: no errors. `npm run lint:php`: 0 errors, and only the repo's usual long-line warnings.
- Albanian loads (`switch_to_locale('sq')` returns "Çelësi i bibliotekës", "Makinë Smith").

Left for later:
- The 8 demo Exercises already in the local database have no key (they pre-date this). Ticket 03 must give every keyless Exercise a key, not only adopt the ones that match the library; see its comments.
- No new admin list column. The key shows on the edit screen only.
