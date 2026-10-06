# Verify end to end and go-live checklist

Type: task
Status: ready-for-agent
Blocked by: 04, 05, 06

## What to build

An end-to-end check on the local stack, then a short go-live checklist written into this ticket.

Check:

1. Delete every Exercise (force), run the import from the wp-admin screen, and count: 200 Exercises, 200 stills, 200 animations.
2. Run it again: nothing changes.
3. In a headless browser (Playwright with Edge), open the Import screen, a Plan's Portal logging screen with "How to" open, and the PDF preview.
4. `npm run lint:php` and `npm run analyse:php`.

Go-live checklist (production):

- Build the plugin zip with `vendor/` included (it is gitignored) and with `data/exercises/media/` (about 20 MB). Check the host's upload size limit; if the zip is too large, upload by SFTP instead.
- Check `max_execution_time` and `memory_limit`; the batches assume a 30-second limit.
- Run **Training → Import** once and spot-check five Exercises.
- Keep the licence document for the exercise data and media with the project files.

## Acceptance criteria

- [ ] Every step above passes, with the counts recorded under `## Comments`.
- [ ] The go-live checklist is complete and accurate.

## Comments

**2026-10-05 (review of tickets 01 to 06, before the first commit):** Read the whole change again. Fixed:
- The empty-library notice read and validated the library file on every Exercises list load; it now reads it only when there are no Exercises.
- A batch killed by a time or memory limit skips `finally`, which left the import lock for 5 minutes. The lock value is now `{time}:{token}`; a shutdown function releases it, and a run can only release its own lock. Verified: a normal run leaves no lock; another run's fresh lock is refused and left untouched; a stale one is cleared; a run that `exit`s inside `save_post` leaves no lock, and the next run fills in the half-created Exercise (1 filled in, media reused).
- No PHP warnings from the plugin in the container log or `debug.log` across all of the day's testing. Smoke test after restoring: Exercises list, Import screen ("fully imported") and Plan #31's Workout screen (GIF in "How to") load with no JavaScript errors.

Still for this ticket: the clean-site run and the go-live checklist above.
