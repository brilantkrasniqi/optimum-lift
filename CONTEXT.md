# Optimum Lift — Context

The glossary for this project. Terms here are canonical: use them in issues, specs, code, and conversation. This file holds language only — no implementation detail, no plans, no decisions. Decisions live in `docs/adr/`.

## Brand

**Optimum Lift** — the brand. Note the spelling: *Optimum*, not *Optimal*. It carries prior goodwill: 600+ physical plans were sold under this name.

## Core terms

**Plan** — the content authored by Optimum Lift and followed by a customer. A Plan exists independently of how it is sold or delivered. Two kinds:

- **Training Plan** — a structured programme of workouts (e.g. a 10-week body recomposition plan).
- **Nutrition Plan** — a structured diet or eating programme.

Preferred over "fitness plan" and "diet plan", which blur into each other in conversation.

**Product** — a sellable listing in the shop: it has a price, a page, and imagery. One Plan may be sold as several Products — a seasonal edition, a bundle, a discounted variant. This split is what makes a multi-SKU, seasonal catalogue possible without re-authoring content.

**Download** — the PDF of a Plan that a Customer receives after purchase. It is a rendering of the Plan, never authored separately, so it cannot disagree with what the Portal shows.

**Customer** — someone who has bought at least one Product.
_Avoid_: Client, user, member

**Access** — a Customer's standing right to follow one Plan and receive its Download, obtained by buying a Product that includes that Plan. Once obtained, Access does not change when the Product is later edited; it ends only if the purchase is refunded or cancelled.

**Portal** — the signed-in area where a Customer follows the Plans they have Access to and logs their training.
_Avoid_: Dashboard, client area, members area

## Training Plan structure

**Week** — one numbered week of a Training Plan, authored in full. Week 3 is written out, not derived from Week 2 by a progression rule.

**Phase** — an optional label over a stretch of consecutive Weeks (e.g. *Weeks 9–12: Strength*). Only a name; it carries no content of its own. Some Training Plans have Phases; some are just Weeks.

**Workout** — one authored training session within a Week (e.g. *Week 3, Workout 2: Upper body*). It is what the Plan prescribes, not a record of someone training on a date.
_Avoid_: Day (implies a calendar date), Session

**Exercise** — a movement, independent of any Plan (e.g. *Barbell back squat*). The same Exercise appears in many Workouts across many Plans.
_Avoid_: Movement

**Prescription** — one line of a Workout: an Exercise plus how to perform it there. A number of identical sets, each with a target (reps or seconds) and optionally an intensity (e.g. RPE) and a rest between sets, plus notes that apply only to this Workout (e.g. *pause 2 seconds at the bottom*). Sets within a Prescription never differ.
_Avoid_: Workout exercise, Exercise (for the line itself)

## Training performance

What a Customer actually did, as opposed to what the Plan prescribes. Performance never changes the Plan.

**Workout Log** — a Customer's record of doing one Workout on one occasion. The same Workout may be logged more than once (e.g. repeating Week 1).
_Avoid_: Session, Workout (for the record itself)

**Logged Set** — one set within a Workout Log as actually performed: the load used and the reps or seconds achieved, against one Prescription.
_Avoid_: Set log, Performance (for a single set)

**Personal Record** — the heaviest load a Customer has logged for an Exercise, across every Plan. A Logged Set that beats it sets a new one.
_Avoid_: PR (in writing), Best

## Retired terms

**Program** — retired. It was used interchangeably with *Plan* and did no work that *Plan* does not already do. Use **Plan**.
