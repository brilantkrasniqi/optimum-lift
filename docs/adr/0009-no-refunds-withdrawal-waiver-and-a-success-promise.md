# ADR-0009: No refunds on digital Products; buyers waive withdrawal at checkout; the guarantee is a success promise

**Status:** Accepted (2026-10-02)

Answers the practical half of launch issue 10 (refund policy and EU withdrawal
consent). Supersedes the 30-day money-back guarantee the storefront shipped
with (the `guarantee_days` setting and token in `.scratch/storefront-theme/spec.md`).

## Context

The storefront promised "30 ditë garanci — pa pyetje": a full refund within 30
days, and the buyer keeps the materials. The owner decided the store will not
refund digital Products: a Download cannot be returned, and a refund that lets
the buyer keep the plan is a free plan.

Saying "no refunds" is not enough on its own. An EU consumer (the diaspora is
a target market) has a 14-day right of withdrawal on digital content, and it
only ends early if, before paying, they ask for immediate access and
acknowledge that they lose the right once access starts; the merchant must
then confirm that on a durable medium. Without that consent the right stands,
whatever the policy page says. Content that is faulty or not as described is
covered by separate statutory rights that no policy removes.

The design still wants a trust signal where the guarantee used to be (header,
cart, buy bar, checkout, shop, Product pages).

## Decision

- **No refunds once access is delivered.** The refund policy page says so, and
  that a Product which does not open or is not as described is fixed or
  replaced, without affecting statutory rights.
- **A withdrawal waiver at checkout** (`inc/shop/withdrawal.php`): an unticked,
  required box above "Place order" for every cart that needs no shipping. The
  order stores when it was ticked and its exact wording; the order screen shows
  it; the buyer's order emails confirm it.
- **The guarantee becomes a promise, not money back.** The Customizer's
  "Money-back guarantee (days)" is replaced by a text setting, `guarantee`,
  default "Success guaranteed" ("Sukses i garantuar"), shown with what backs
  it: follow the plan, and if you get stuck we help you. Empty hides every
  mention. The `{guarantee}` token replaces `{guarantee_days}`, which is
  retired and always empty, so stale editor text drops the phrase instead of
  printing a number.

## Consequences

- Every surface that said "30 days" or "money back" now shows the promise or
  nothing. Editor content (section headings, the hero's reassurance lines) is
  text, not a token, so it has to be kept in line with the Customizer by hand.
- The promise is only honest while help is real: someone answers the email or
  WhatsApp messages a stuck buyer sends.
- A chargeback is now the only way a buyer can force a refund. The stored
  waiver and the confirmation email are the evidence against one.
- If launch issue 03 picks a Merchant of Record, its checkout collects this
  consent itself and the box should go, so the two do not ask twice.
- The policy and the box wording are placeholders until reviewed by someone
  qualified in Kosovo and EU consumer law.
