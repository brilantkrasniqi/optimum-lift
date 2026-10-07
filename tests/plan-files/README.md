# Plan file test fixtures

Test files for the Plan file reader and importer (spec: `.scratch/plan-files/`). They live outside the plugin so they never ship; inside the `wpcli` container this folder is mounted read-only at `/plan-fixtures`.

- `minimal.json`: only the required properties. It must import, with every default filled in.
- `full.json`: every property set (two Phases, a multi-line note, a `seconds` target, a `null` rest, Albanian text, quotes) in the exporter's exact layout, so importing it and exporting the copy gives the same bytes.
- `broken.json`: breaks every rule at least once and must import nothing. `broken.expected.txt` is the exact list of problems it gives, one per line, in order.

`broken.json` expects the library Exercise `band-y-raise` to be in the trash. Before checking it, find its ID with `wp post list --post_type=ol_exercise --meta_key=library_key --meta_value=band-y-raise --field=ID` and trash it with `wp eval "wp_trash_post(<id>);"` (`wp post delete` refuses to trash anything but posts and pages); afterwards restore it with `wp eval "wp_untrash_post(<id>); wp_publish_post(<id>);"`.
