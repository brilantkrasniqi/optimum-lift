# Plan file reader and validator

Type: task
Status: ready-for-agent
Blocked by: none

## What to build

`src/PlanFile/PlanFile.php`: reads a Plan file (from a path or a string) and checks the whole thing against the spec's format table. Like `LibraryFile`, a file with any problem exposes no data, only `problems` (a list of human-readable strings). Valid files expose a normalised, typed structure (defaults filled in, strings sanitised) that the importer writes and the exporter's output is compared against.

- Refuse before parsing: over 2 MB, not valid JSON, top level not an object.
- Then collect **every** problem, not the first. Each names its location in author terms: `Week 3, Workout 2 ("Upper body"), Prescription 4: unknown Exercise "barbel-bench-press".`
- Strict keys: an unknown property at any level is a problem (`unknown property "rest"`).
- Phase rules reuse the wording of `PlanFields::validate()` ("A Phase must end on or after the Week it starts.", "This Plan only has %d Weeks.", "Phases overlap at Week %d.").
- Exercise resolution is a separate step that needs the database: `resolveExercises(LibraryKeys $keys)` turns every key into a post ID with `LibraryKeys::find()` and adds a problem for a key that is not found, or whose post is not `publish` (say which: "in the trash", "a draft"). Keep it separate so the format checks can run on a string with no WordPress data.
- `notes`: a list of non-blocking remarks the importer can add to (title already in use).
- In `PlanFields`, turn the limits and field keys the validator and importer need into public constants (sets max 20, target and intensity maxlength 20, the `field_ol_*` keys used by `SeedCommand`) and use those constants in the field definitions. No behaviour change.

## Acceptance criteria

- [ ] A minimal valid file (1 Week, 1 Workout, 1 Prescription, only required properties) passes, and the normalised result has every default from the spec's table.
- [ ] A file with all of these lists all of them and nothing else: wrong `format`, `version` 2, empty title, unknown difficulty, a Phase ending after the last Week, overlapping Phases, a Week with no Workouts, a Workout with no name, `sets: 0`, `sets: "3"` (string), a 21-character target, `target_type: "minutes"`, `rest_seconds: -5`, an unknown property, a malformed key (`"Bench Press"`), a well-formed key that does not exist, a key of a trashed Exercise.
- [ ] A 3 MB file and a file that is not JSON are refused with one problem each.
- [ ] `PlanFields` still registers identical field definitions (compare `acf_get_local_fields()` output, or the field group JSON, before and after).
- [ ] `npm run lint:php` and `npm run analyse:php` pass.

## Comments
