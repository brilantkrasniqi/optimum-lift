# Conversion tracking approach

Type: grilling
Status: open
Blocked by: 03

## Question

How do purchases get reported back to Meta?

Browser-side Pixel alone is increasingly lossy — ad blockers, iOS restrictions, cookie consent. Server-side Conversions API is more reliable but more work. Which combination, and how is it verified as actually working before ad spend starts?

The complication: if checkout is hosted by a Merchant of Record on their domain, the purchase event fires somewhere the site does not control, and standard Woo tracking plugins may not see it at all.

## Definition of done

A decided approach, plus a way to prove the purchase event is firing correctly with the right value attached — *before* any money goes into ads.

## Notes

This is not optional and it is not a nice-to-have. Untracked ads do not merely leave you blind; Meta's optimization needs the purchase signal to find buyers, so untracked campaigns perform materially worse. Launching without it burns budget twice over.

Cookie consent for EU visitors intersects here and should be settled in the same conversation.
