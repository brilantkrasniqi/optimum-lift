# Owner check: sell "Djegie e shpejtë" in Sizes on the local site

Type: task
Status: ready-for-human
Blocked by: 10

## What to do

Brilant, on your PC, with the branch pulled, `npm run build` done and the stack running. The agent gives you the exact commands for the first step.

1. Seed the demo again (`docker compose --profile cli run --rm wpcli ol-shop seed`) so the Gjinia and Pesha attributes exist.
2. Follow `content/diets/README.md` › "Selling the sizes in WooCommerce" to make **Djegie e shpejtë** a variable Product with only **Pesha** (it is men-only), using the 5 PDFs from the thread. The Size PDFs table should show no warnings.
3. On the Product page, on your phone or in the browser's phone view:
   - Press **Bli tani** with nothing chosen. It should point you at the weight picker.
   - Pick **70–80 kg** and press **Bli tani**. Checkout should show the diet with "70–80 kg" under it.
   - Place the order with cash on delivery, then set it to Processing in wp-admin.
   - Open the PDF from the thank-you page. Its cover should say "Mashkull · 70–80 kg".
4. Pick **80–90 kg** and press **Shto në shportë**, then go back, pick **90+ kg** and add again. The cart should hold one line: 90+ kg.
5. Read the new Albanian texts on the page, in the cart drawer and in the Size PDFs box. Fix anything in Poedit or tell the agent.
6. From a cards page (the shop), press the diet's button. It should say "Choose your size" in Albanian and take you to the picker.

## Acceptance criteria

- [ ] Every step above behaves as described.
- [ ] Brilant says whether to merge to `main`.
- [ ] After merging, before selling on production: the Gjinia and Pesha attributes are created there with the same slugs (README step 1).

## Comments
