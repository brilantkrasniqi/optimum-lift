# Refund policy and EU withdrawal consent at checkout

Type: grilling
Status: open
Blocked by: 03

## Question

What is the refund position on a digital Download, and how is it made lawful?

EU consumers have a 14-day right of withdrawal. For digital content that right **can** be waived — but only if the buyer gives prior express consent and acknowledges losing it, at the point of purchase. That makes this a checkout requirement, not just a policy page: a checkbox with specific wording, and a record that it was ticked.

Also decide the practical position: refund on request anyway (cheap goodwill, and chargebacks cost more than refunds), or hold the line?

## Definition of done

A refund policy in plain Albanian and English, and a decided checkout mechanism for capturing withdrawal consent.

## Notes

Merchants of Record typically handle this consent themselves as part of their checkout — another point where 02 and 03 change the answer.

Worth weighing against the EUR 7.99 price: at that level a disputed chargeback costs several times the sale value, which argues for generous refunds rather than a fight.

## Comments

2026-10-02: the owner decided the practical position: **hold the line, no refunds** on digital Products. Recorded in ADR-0009 and built for the classic checkout:

- `inc/shop/withdrawal.php`: an unticked, required box above "Place order" ("Dua akses në plan menjëherë dhe e kuptoj që, sapo të nisë aksesi, humb të drejtën e tërheqjes brenda 14 ditëve."), stored on the order with its time and wording, shown on the order screen, confirmed in the buyer's order emails.
- The refund policy page (seeded, Albanian) now says no refunds once access is delivered, and that a Product which does not open or is not as described is fixed or replaced.
- The 30-day money-back guarantee is gone everywhere; the Customizer `guarantee` text ("Sukses i garantuar") replaces it.

Still open for this ticket: the English version of the policy, a review of the policy and the box wording by someone qualified in Kosovo/EU consumer law, and the dependency on 03 (a Merchant of Record would collect this consent itself, and the box should then go).
