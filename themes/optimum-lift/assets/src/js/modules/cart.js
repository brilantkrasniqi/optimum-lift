/**
 * Cart drawer (ADR-0007). The server renders everything the drawer shows
 * (inc/shop/cart.php); this module only posts to the theme's wc-ajax
 * endpoints and swaps in the fragments they answer with. It never computes a
 * price and holds no cart state of its own.
 *
 * [data-add-to-cart] opens the drawer at once and adds without leaving the
 * page; if the request fails, the link's own href lets WooCommerce add it
 * instead. [data-buy-now] is plain navigation and is never intercepted
 * (pending-links.js only shows that it is loading).
 *
 * The Size picker's add button ([data-size-add]) submits its form; the add
 * goes through the drawer on `submit`, after the browser has checked that
 * every group has a choice. If the request fails, the form is submitted
 * natively, and WooCommerce adds it on a page load. An add that still needs a
 * Size (the endpoint's `needs_choice`) closes the drawer and points at the
 * picker; from anywhere else, it goes to the Product's picker.
 *
 * Nothing waits silently: the control that started a request is aria-busy (a
 * spinner, components.css) and the drawer has data-busy (a moving bar, dimmed
 * totals and, for an add, a placeholder line; drawer.css) until it answers.
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
  const status = drawer.querySelector('[data-cart-status]');
  let isOpen = false;
  let opener = null;
  let madeInert = [];
  let pending = 0;

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
   * The drawer's busy state, counted so that one request finishing does not
   * clear it while another still runs. `kind` is `add` or `update`.
   */
  function busy(kind) {
    pending += 1;
    drawer.dataset.busy = kind;
    if (status) {
      status.textContent = kind === 'add' ? drawer.dataset.labelAdd || '' : drawer.dataset.labelUpdate || '';
    }
  }

  function idle() {
    pending = Math.max(0, pending - 1);
    if (pending === 0) {
      delete drawer.dataset.busy;
      if (status) {
        status.textContent = '';
      }
    }
  }

  /**
   * Runs one endpoint call for a control: busy state while it runs, then
   * fragments and notice. Returns the response, or null when the request
   * failed.
   */
  async function run(el, name, data, kind) {
    el.setAttribute('aria-busy', 'true');
    busy(kind);

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
      idle();
    }
  }

  async function addToCart(el) {
    showNotice('');
    // Open before the server answers: the buyer sees the cart at work rather
    // than a button that seems to do nothing.
    open(el.closest('#ol-cart-drawer') ? opener : el);

    const result = await run(el, 'ol_add_to_cart', { product_id: el.dataset.addToCart }, 'add');

    if (!result) {
      // Let WooCommerce add it the classic way.
      if (el instanceof HTMLAnchorElement && el.href) {
        window.location.href = el.href;
      }
      return;
    }

    if (result.needs_choice && result.url) {
      window.location.href = result.url;
      return;
    }

    if (result.ok && result.added !== false && result.item) {
      track('add_to_cart', result.item);
    }
  }

  async function addSize(form, button) {
    const data = { product_id: button.value };
    new FormData(form).forEach((value, key) => {
      if (key === 'variation_id' || key.startsWith('attribute_')) {
        data[key] = String(value);
      }
    });

    showNotice('');
    open(button);

    const result = await run(button, 'ol_add_to_cart', data, 'add');

    if (!result) {
      form.dataset.native = 'true';
      form.requestSubmit(button);
      return;
    }

    if (result.needs_choice) {
      showNotice('');
      close();
      const notice = form.querySelector('[data-size-notice]');
      if (notice) {
        notice.innerHTML = result.notice || '';
      }
      const empty = [...form.querySelectorAll('fieldset')].find((group) => !group.querySelector('input:checked'));
      (empty || form.querySelector('fieldset'))?.querySelector('input:not([disabled])')?.focus();
      return;
    }

    if (result.ok && result.added !== false && result.item) {
      track('add_to_cart', result.item);
    }
  }

  async function swapToBundle(el) {
    showNotice('');
    const result = await run(el, 'ol_swap_to_bundle', { bundle_id: el.dataset.cartSwap, nonce: el.dataset.nonce || '' }, 'update');

    if (result?.needs_choice && result.url) {
      window.location.href = result.url;
      return;
    }

    if (result?.ok && result.added !== false && result.item) {
      track('add_to_cart', result.item);
    }
  }

  async function removeLine(el) {
    showNotice('');
    await run(el, 'ol_remove_from_cart', { cart_item_key: el.dataset.cartRemove, nonce: el.dataset.nonce || '' }, 'update');
  }

  document.addEventListener('click', (e) => {
    const target = e.target instanceof Element ? e.target : null;
    if (!target) {
      return;
    }

    const add = target.closest('[data-add-to-cart]');
    if (add) {
      // A modified click (new tab, new window) follows the link as usual, and
      // so does any add on the cart page, whose table the drawer cannot update.
      if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || document.body.classList.contains('woocommerce-cart')) {
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

  document.addEventListener('submit', (e) => {
    const form = e.target;
    const button = e.submitter;
    if (
      !(form instanceof HTMLFormElement)
      || !form.matches('[data-size-picker]')
      || !(button instanceof HTMLButtonElement)
      || !button.matches('[data-size-add]')
      || document.body.classList.contains('woocommerce-cart')
    ) {
      return;
    }

    // The fallback after a failed request: let the browser submit it.
    if (form.dataset.native) {
      delete form.dataset.native;
      return;
    }

    e.preventDefault();
    if (!button.hasAttribute('aria-busy')) {
      addSize(form, button);
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
