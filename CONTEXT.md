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

**Download** — the file a buyer receives after purchase. Currently a PDF.

**Customer** — someone who has bought at least one Product.

## Retired terms

**Program** — retired. It was used interchangeably with *Plan* and did no work that *Plan* does not already do. Use **Plan**.
