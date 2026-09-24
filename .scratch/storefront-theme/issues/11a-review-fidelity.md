# Review and fix: visual fidelity against the mocks

Type: task
Status: ready-for-agent
Wave: 3
Parent: 11
Blocked by: 05, 06, 07, 08, 09, 10

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. The 11 sub-tickets run one at a time, because fixes may touch any file.

## What to do

1. **Screenshot every page at 390px and 1440px.** Use the spec's Verification section. Scroll first, or force `.reveal` visible.

   | Page | Compare with |
   | --- | --- |
   | `/` | `index.html` |
   | Product 60 | `produkt.html` |
   | Product 62 | `produkt-dieta.html` |
   | Products 61, 63, 64 | the same system, no mock |
   | `/shop/` and the three kind archives | `dyqani.html` |
   | Drawer open | `cart.js` §4 look |
   | Checkout, thank-you, `/cart/`, My Account, Portal, blog, search, 404 | the design system (no mock) |

2. **List the visual regressions against the mocks.** Leave out what the spec lists as a deviation.
3. **Verify each finding** by re-checking it at the other width and against the mock's markup, then fix it.

## Acceptance criteria

- [ ] The Answer holds a findings table: page, width, finding, verified yes/no, and what was fixed or why not.
- [ ] Before/after screenshots for every fix.
- [ ] Build, lint and PHPStan pass.
