# Writing a Training Plan as a Plan file

Read this when the owner asks for a Training Plan ("a 10-week home hypertrophy Plan for beginners, 3 Workouts a week"). It is all you need to write one. The words are the ones in `CONTEXT.md`: **Plan**, **Week**, **Phase**, **Workout**, **Prescription**, **Exercise**, **library key**. The spec behind this is `.scratch/plan-files/spec.md`.

A **Plan file** is one Training Plan written as JSON, naming Exercises by library key. Importing one always creates a new Plan, as a draft; it never changes an existing Plan.

## The workflow

1. Write the file in `content/plans/<slug>.json`. The slug is the title in lowercase letters and digits, every run of anything else (spaces, punctuation) turned into one hyphen: "10-Week Home Hypertrophy!" becomes `10-week-home-hypertrophy.json`.
2. Check it: `import-plan --dry-run` (commands below). Fix every problem it lists and run it again until it says "Ready to import".
3. Import it on the local site. It becomes a draft Training Plan.
4. Tell the owner (see the last section). The owner reviews the draft in wp-admin, and in the Portal and the PDF preview after publishing it locally.
5. If the owner edits the draft in wp-admin, export it back over the file, so the repo file matches what they approved.
6. On production, the owner uploads the file on **Training › Import Plan** (or exports it from local wp-admin with **Export as JSON** and uploads that). The Exercise library must be imported there first.

The commands need the Docker stack on the owner's PC. A cloud session cannot run them: commit the file to its working branch, push it, and ask the owner to run the commands (or ask for a session on the owner's PC). Until then, check the file against the format table yourself.

## The format (version 1)

```json
{
    "format": "optimum-lift-plan",
    "version": 1,
    "plan": {
        "title": "10-week home hypertrophy",
        "summary": "Three full-body Workouts a week with dumbbells and a bench.",
        "goal": "Muscle gain",
        "target_audience": "Beginners training at home",
        "difficulty": "beginner",
        "phases": [
            { "name": "Foundation", "first_week": 1, "last_week": 4 },
            { "name": "Build", "first_week": 5, "last_week": 10 }
        ],
        "weeks": [
            {
                "workouts": [
                    {
                        "name": "Full body A",
                        "prescriptions": [
                            {
                                "exercise": "dumbbell-single-leg-split-squat",
                                "sets": 3,
                                "target_type": "reps",
                                "target": "10-12",
                                "intensity": "RPE 7",
                                "rest_seconds": 90,
                                "notes": "Each leg. Pause 1 second at the bottom."
                            }
                        ]
                    }
                ]
            }
        ]
    }
}
```

| Path | Rule | When left out |
| --- | --- | --- |
| `format` | exactly `"optimum-lift-plan"` | required |
| `version` | the number `1` | required |
| `plan.title` | not empty | required |
| `plan.summary` | text; line breaks allowed | `""` |
| `plan.goal`, `plan.target_audience` | text, one line | `""` |
| `plan.difficulty` | `"beginner"`, `"intermediate"`, `"advanced"` or `""` | `""` |
| `plan.phases` | a list, may be empty. Each: `name` not empty; `first_week` at least 1; `last_week` not before `first_week` and not after the last Week; no two Phases share a Week | `[]` |
| `plan.weeks` | at least one. Weeks are numbered by position: the first entry is Week 1 | required |
| `weeks[].workouts` | at least one | required |
| `workouts[].name` | not empty | required |
| `workouts[].prescriptions` | at least one | required |
| `prescriptions[].exercise` | a library key of a published Exercise on the importing site | required |
| `prescriptions[].sets` | a whole number from 1 to 20, written as a number (`3`, not `"3"`) | required |
| `prescriptions[].target_type` | `"reps"` or `"seconds"` | `"reps"` |
| `prescriptions[].target` | text, not empty, at most 20 characters (`"8"`, `"8-10"`, `"AMRAP"`, `"30"`) | required |
| `prescriptions[].intensity` | text, at most 20 characters (`"RPE 8"`) | `""` |
| `prescriptions[].rest_seconds` | a whole number, 0 or more, or `null` | `null` |
| `prescriptions[].notes` | text; line breaks allowed | `""` |

Anything else is a problem: an unknown property (`"rest": 90` instead of `"rest_seconds": 90`) is reported, not ignored. Save the file as UTF-8. There are no IDs, uids or dates in a Plan file, ever. Phases need not cover every Week. Write every property, defaults included, in the table's order: that is what an export writes, so the file diffs cleanly against a later export.

A whole Plan in the format: `content/plans/demo-body-recomposition.json`, the seeded demo, exported. It shows the format only: it is a gym Plan with 2-week Phases, made for testing, so do not copy its programming, and its file name predates the slug rule.

## Choosing Exercises

Use only keys from `plugins/optimum-lift-plans/data/exercises/exercises.json`, the Exercise library. Every site imports it, so its keys exist on production too. Never invent a key or use one of a hand-made local Exercise. Each entry has `key`, `name`, `equipment`, `difficulty`, `pattern` (the movement pattern) and `settings`.

- **Setting:** a home Plan uses only Exercises whose `settings` include `home`; a gym Plan, `gym`.
- **Equipment:** only what the owner says is available, plus `bodyweight`, which is always available. Every item in an Exercise's `equipment` must be available (`["dumbbell", "bench"]` needs both), so "dumbbells" alone rules out bench Exercises.
- **Difficulty:** no `advanced` Exercises in a beginner Plan unless the owner asks.
- **Balance across each Week, by `pattern`, counting Prescriptions:** as many pushes (`horizontal_push`, `vertical_push`) as pulls (`horizontal_pull`, `vertical_pull`; `rear_delt` and `scapular` are accessories, not pulls); lower-body work with both a knee-dominant pattern (`squat`, `lunge`) and a hip-dominant one (`hinge`, `hip_extension`); some core (`core_anti_extension`, `anti_rotation`, `core_lateral`, `core_flexion`, `carry`).
- **Known gaps in the library:** with dumbbells at home the only pull is `dumbbell-one-arm-bent-over-row` (bands and a pull-up bar add more); `inverted-row` is tagged bodyweight but needs a waist-height bar or a suspension trainer, so use it only when the owner has one; there is no dumbbell squat, so knee-dominant dumbbell work is lunges and split squats. When a gap forces repetition, say so to the owner.

From the repo root, in bash, Exercises for a home Plan with dumbbells (and bodyweight), no advanced ones. Put exactly the owner's equipment in `IN(...)`; add `"bench"` only if they have one:

```bash
jq -r '.exercises[] | select((.settings | index("home")) and .difficulty != "advanced" and all(.equipment[]; IN("dumbbell", "bodyweight"))) | [.key, .pattern, .difficulty, (.equipment | join("+"))] | @tsv' plugins/optimum-lift-plans/data/exercises/exercises.json
```

The same for one pattern (add `and .pattern == "hinge"` inside `select(...)`), or every pattern with its count:

```bash
jq -r '[.exercises[].pattern] | group_by(.) | map("\(.[0]) \(length)") | .[]' plugins/optimum-lift-plans/data/exercises/exercises.json
```

In PowerShell:

```powershell
$lib = (Get-Content plugins/optimum-lift-plans/data/exercises/exercises.json -Raw -Encoding UTF8 | ConvertFrom-Json).exercises
$lib | Where-Object { $_.settings -contains 'home' -and $_.difficulty -ne 'advanced' -and -not ($_.equipment | Where-Object { $_ -notin 'dumbbell', 'bodyweight' }) } | Select-Object key, pattern, difficulty, @{ n = 'equipment'; e = { $_.equipment -join '+' } } | Format-Table -AutoSize
```

## Programming defaults

These are the owner's to change; follow what the owner asks over anything here.

- Write every Week out in full. There is no "repeat Week 1"; repetition is written out.
- Phases of 3 to 6 Weeks, named for what they do ("Foundation", "Build", "Peak"). The last Week of a Phase is lighter: fewer sets or a lower RPE. A Plan may end on that lighter Week; mention it to the owner.
- Progress each Exercise from one Week to the next through reps, sets or RPE, changing one of them at a time. The lighter Week and the start of a new Phase are the exceptions.
- Beginner Plans may use `intermediate` Exercises in later Phases; never `advanced` unless asked.
- Ranges for hypertrophy (`"8-12"`, `"10-15"`); lower reps for strength (`"3-5"`, `"5"`).
- `"target_type": "seconds"` for holds, carries and conditioning, with the seconds as the target (`"30"`).
- Intensity as RPE (`"RPE 7"`). Leave it empty for warm-ups, holds and core work.
- Rest by Exercise: 120 to 180 seconds for heavy compound lifts, 60 to 90 for accessories, 30 to 60 for core and conditioning.
- Workout names say what the Workout is ("Upper body A", "Full body B"), never a weekday.
- Notes only where they change how the Exercise is done in this Workout ("Pause 2 seconds at the bottom."). Exercise instructions already live on the Exercise.
- Keep the same Workout layout from Week to Week within a Phase, so Customers recognise it.

## Commands

Run from the repo root on the owner's PC with the stack up (`docker compose up -d`). They work the same in PowerShell and bash. Inside the container, `content/plans/` is `/plans`.

Check a file, writing nothing:

```
docker compose --profile cli run --rm wpcli ol-plans import-plan /plans/<slug>.json --dry-run
```

Import it as a draft (it prints the new Plan's ID and edit link):

```
docker compose --profile cli run --rm wpcli ol-plans import-plan /plans/<slug>.json
```

After the owner edits the draft in wp-admin, write it back over the file:

```
docker compose --profile cli run --rm wpcli ol-plans export-plan <plan-id> --file=/plans/<slug>.json
```

Never redirect output with `>` in PowerShell: it writes UTF-16. Use `--file=`.

## Rules

- Importing always creates a new draft Plan. A published Plan that Customers use is fixed in place in wp-admin, never replaced by a new import, because its Workout Logs point at its rows (ADR-0003).
- A Plan file never carries post IDs or uids.
- The file name is the Plan's slug.
- One Plan per file.

## What to tell the owner after importing

- The draft's edit link.
- One paragraph on the Plan: how many Weeks and Workouts a week, the Phases and how it progresses, and the equipment it needs.
- Anything you assumed that the owner did not say (equipment, goal, target audience, session length, difficulty), and any gap in the library that forced a choice, so they can correct it.
