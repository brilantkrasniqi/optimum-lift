/**
 * Cart drawer (ADR-0007). The server renders everything the drawer shows
 * (inc/shop/cart.php); this module only posts to the theme's wc-ajax
 * endpoints and swaps in the fragments they answer with. It never computes a
 * price and holds no cart state of its own.
 *
 * [data-add-to-cart] adds without leaving the page and opens the drawer; if
 * the request fails, the link's own href lets WooCommerce add it instead.
 * [data-buy-now] is plain navigation and is never intercepted.
 */

import { track } from './track.js';

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function init() {
  const drawer = document.getElementById('ol-cart-drawer');
  const endpoint = window.optimumLift?.wcAjaxUrl;

  if (!drawer || !endpoint) {
    return;
  }

  const root = document.documentElement;
  const backdrop = document.querySelector('.olc-backdrop');
  const notice = drawer.querySelector('[data-cart-notice]');
  let isOpen = false;
  let opener = null;
  let madeInert = [];

  const url = (name) => endpoint.replace('%%endpoint%%', name);
  const focusables = () => [...drawer.querySelectorAll(FOCUSABLE)].filter((el) => el.getClientRects().length > 0);

  async function post(name, data) {
    const body = new FormData();
    Object.entries(data).forEach(([key, value]) => body.append(key, value));

    const response = await fetch(url(name), { method: 'POST', body, credentials: 'same-origin' });
    if (!response.ok) {
      throw new Error(`${name}: HTTP ${response.status}`);
    }

    return response.json();
  }

  function applyFragments(fragments) {
    if (!fragments || typeof fragments !== 'object') {
      return;
    }

    const hadFocus = drawer.contains(document.activeElement);

    Object.entries(fragments).forEach(([selector, html]) => {
      const template = document.createElement('template');
      template.innerHTML = String(html).trim();
      const replacement = template.content.firstElementChild;
      if (!replacement) {
        return;
      }

      document.querySelectorAll(selector).forEach((el) => el.replaceWith(replacement.cloneNode(true)));
    });

    // The focused control (a remove button) may have been replaced.
    if (isOpen && hadFocus && !drawer.contains(document.activeElement)) {
      drawer.querySelector('[data-cart-close]')?.focus({ preventScroll: true });
    }

    document.dispatchEvent(new CustomEvent('ol:cart:updated', { detail: { fragments } }));
  }

  function showNotice(html) {
    if (notice) {
      notice.innerHTML = html || '';
    }
  }

  function onKeydown(e) {
    if (e.key === 'Escape') {
      e.preventDefault();
      close();
      return;
    }

    if (e.key !== 'Tab') {
      return;
    }

    const items = focusables();
    if (!items.length) {
      e.preventDefault();
      return;
    }

    const first = items[0];
    const last = items[items.length - 1];

    if (!drawer.contains(document.activeElement)) {
      e.preventDefault();
      first.focus();
    } else if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }

  function setToggles(expanded) {
    document.querySelectorAll('[data-cart-toggle]').forEach((toggle) => toggle.setAttribute('aria-expanded', String(expanded)));
  }

  function open(from) {
    if (isOpen) {
      return;
    }

    isOpen = true;
    opener = from instanceof HTMLElement ? from : document.activeElement;
    root.classList.add('olc-open');
    drawer.inert = false;

    // Everything else is out of reach while the dialog is open. Only what this
    // module made inert is restored (the closed mobile menu stays inert).
    madeInert = [...document.body.children].filter(
      (el) => el !== drawer && el !== backdrop && el.tagName !== 'SCRIPT' && !el.inert,
    );
    madeInert.forEach((el) => {
      el.inert = true;
    });

    setToggles(true);
    document.addEventListener('keydown', onKeydown);
    drawer.querySelector('.olc-close')?.focus({ preventScroll: true });
    document.dispatchEvent(new CustomEvent('ol:cart:open'));
  }

  function close() {
    if (!isOpen) {
      return;
    }

    isOpen = false;
    root.classList.remove('olc-open');
    drawer.inert = true;
    madeInert.forEach((el) => {
      el.inert = false;
    });
    madeInert = [];

    setToggles(false);
    document.removeEventListener('keydown', onKeydown);

    if (opener instanceof HTMLElement && opener.isConnected) {
      opener.focus({ preventScroll: true });
    }
    opener = null;
  }

  /**
   * Runs one endpoint call for a control: busy state while it runs, then
   * fragments, notice and the drawer. Returns the response, or null when
   * the request failed.
   */
  async function run(el, name, data) {
    el.setAttribute('aria-busy', 'true');

    try {
      const result = await post(name, data);
      applyFragments(result.fragments);
      showNotice(result.notice);
      return result;
    } catch (error) {
      console.error(error);
      return null;
    } finally {
      el.removeAttribute('aria-busy');
    }
  }

  async function addToCart(el) {
    showNotice('');
    const result = await run(el, 'ol_add_to_cart', { product_id: el.dataset.addToCart });

    if (!result) {
      // Let WooCommerce add it the classic way.
      if (el instanceof HTMLAnchorElement && el.href) {
        window.location.href = el.href;
      }
      return;
    }

    open(el.closest('#ol-cart-drawer') ? opener : el);

    if (result.ok && result.added !== false && result.item) {
      track('add_to_cart', result.item);
    }
  }

  async function swapToBundle(el) {
    showNotice('');
    const result = await run(el, 'ol_swap_to_bundle', { bundle_id: el.dataset.cartSwap, nonce: el.dataset.nonce || '' });

    if (result?.ok && result.added !== false && result.item) {
      track('add_to_cart', result.item);
    }
  }

  async function removeLine(el) {
    showNotice('');
    await run(el, 'ol_remove_from_cart', { cart_item_key: el.dataset.cartRemove, nonce: el.dataset.nonce || '' });
  }

  document.addEventListener('click', (e) => {
    const target = e.target instanceof Element ? e.target : null;
    if (!target) {
      return;
    }

    const add = target.closest('[data-add-to-cart]');
    if (add) {
      // A modified click (new tab, new window) follows the link as usual.
      if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
        return;
      }
      e.preventDefault();
      if (!add.hasAttribute('aria-busy')) {
        addToCart(add);
      }
      return;
    }

    const remove = target.closest('[data-cart-remove]');
    if (remove) {
      e.preventDefault();
      if (!remove.hasAttribute('aria-busy')) {
        removeLine(remove);
      }
      return;
    }

    const swap = target.closest('[data-cart-swap]');
    if (swap) {
      e.preventDefault();
      if (!swap.hasAttribute('aria-busy')) {
        swapToBundle(swap);
      }
      return;
    }

    const toggle = target.closest('[data-cart-toggle]');
    if (toggle) {
      e.preventDefault();
      open(toggle);
      return;
    }

    if (target.closest('[data-cart-close]')) {
      e.preventDefault();
      close();
      return;
    }

    const checkout = target.closest('[data-begin-checkout]');
    if (checkout) {
      try {
        track('begin_checkout', JSON.parse(checkout.dataset.beginCheckout || '{}'));
      } catch (error) {
        console.error(error);
      }
    }
  });

  // A cached page may show a stale cart: refresh it once when WooCommerce
  // says the visitor has one.
  if (document.cookie.includes('woocommerce_items_in_cart')) {
    post('get_refreshed_fragments', {})
      .then((result) => applyFragments(result?.fragments))
      .catch((error) => console.error(error));
  }
}
