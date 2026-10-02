/**
 * Links whose next page takes a server round trip first: Buy Now
 * (`?ol_buy_now=`, which fills the cart and redirects to checkout) and the
 * drawer's "Continue to checkout". They stay plain navigation; a click only
 * makes them aria-busy, which shows a spinner (components.css) and blocks a
 * second click while the browser loads.
 *
 * A page restored from the back/forward cache would come back still spinning,
 * so pageshow clears it, and so does a timeout if the page never arrives.
 */

const SELECTOR = '[data-buy-now], [data-begin-checkout]';
const GIVE_UP_AFTER = 20000;

function clear(link) {
  link.removeAttribute('aria-busy');
}

export function init() {
  document.addEventListener('click', (e) => {
    const link = e.target instanceof Element ? e.target.closest(SELECTOR) : null;

    // A modified click opens a new tab: this page is not going anywhere.
    if (
      !(link instanceof HTMLAnchorElement)
      || e.defaultPrevented
      || e.button !== 0
      || e.metaKey
      || e.ctrlKey
      || e.shiftKey
      || e.altKey
      || link.target === '_blank'
      || link.getAttribute('href')?.startsWith('#')
    ) {
      return;
    }

    link.setAttribute('aria-busy', 'true');
    window.setTimeout(() => clear(link), GIVE_UP_AFTER);
  });

  window.addEventListener('pageshow', (e) => {
    if (e.persisted) {
      document.querySelectorAll(`${SELECTOR}[aria-busy]`).forEach(clear);
    }
  });
}
