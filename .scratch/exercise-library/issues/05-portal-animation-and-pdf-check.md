# Portal animation and PDF check

Type: task
Status: resolved
Blocked by: 03

## What to build

- In `templates/portal/workout.php`, show the Exercise's animation at full size inside the "How to" panel, above the instructions. The 64px list thumbnail stays a still. Today the Portal asks for the `thumbnail` size, which WordPress renders as a still, so no GIF ever plays.
- Give the animation useful alt text and `loading="lazy"`.
- Look at the Download with real library data (admin **Preview PDF**): image size and sharpness, instructions layout, muscles and equipment line. If the 180px images look soft in print, record that in this ticket and decide with the user whether to ask for higher-resolution files. Do not scale them up in code.

## Acceptance criteria

- [x] On the logging screen, opening "How to" for an Exercise with a GIF shows it playing.
- [x] An Exercise with only a still still shows the still.
- [x] The PDF preview of a Plan made from library Exercises renders every image and instruction, with no overflow.
- [x] A short verdict on print quality is appended under `## Comments`.

## Comments

**2026-10-05 (implemented):**

What changed:
- `templates/portal/workout.php`: the list thumbnail is now the still photo (the animation's thumbnail only when there is no still), with `alt=""` because the Exercise name is right beside it. "How to" opens with the original animation (the still when there is none), at its own size, `loading="lazy"`, alt "{name} demonstration". A `<picture>` gives users who ask for reduced motion the still instead. The panel now also appears for an Exercise that has media but no instructions.
- The `<img>` is written by hand, not with `wp_get_attachment_image()`: that adds a `srcset` with the 150px size, which for a GIF is a still, and a browser could pick it.
- `assets/portal.css`: `.ol-howto__media img`, never enlarged.
- One new string ("%s demonstration"), with Albanian.

Verified in Edge on a throwaway Plan of 13 Exercises (the 6 with the longest instructions in the library, the 3 longest names, 2 with several pieces of equipment, one Exercise with only a still, one with no media), with the database and uploads backed up and restored afterwards:
- List thumbnails are the 150px JPEG stills, `alt=""`. No library GIF is downloaded while every "How to" is closed.
- Opening "How to" downloads only that Exercise's original GIF (not a resized one), shown at 180px, with the alt text. Eight screenshots 200 ms apart gave 4 distinct frames: it plays.
- With reduced motion, the panel shows the full-size still JPEG.
- Still only: the still in both places, no reduced-motion source. No media: no thumbnail, and the panel shows the instructions alone.
- At 375px wide (2x): no horizontal overflow.
- `npm run lint:php` 0 errors, `npm run analyse:php` no errors.

**Print quality verdict (PDF preview of the same Plan, 7 pages):** every image and every instruction rendered, the muscle and equipment line is right, nothing overflows, and the no-media Exercise lays out as text alone. Each image is drawn 150 CSS px wide, about 40 mm on A4, from a 180px file: about 115 dpi. Sharp on screen; slightly soft on paper, though the simple drawings on white hold up. Good enough for launch. Files of at least 360px (about 230 dpi) would make printed copies crisp; that is the user's call, and nothing in code should scale the 180px files up.
