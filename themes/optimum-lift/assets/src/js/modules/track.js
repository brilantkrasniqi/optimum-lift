/**
 * Analytics: one call feeds the dataLayer (GTM), the Meta Pixel and gtag,
 * each only when present.
 *
 *   import { track } from './track.js';
 *   track('add_to_cart', { id: 12, name: '…', price: 7.99, currency: 'EUR' });
 *
 * Server-rendered events are JSON payloads the page prints:
 *
 *   <script type="application/json" data-ol-track="view_item">{"id":12,"name":"…","price":7.99,"currency":"EUR"}</script>
 *
 * The event name is the attribute value, else an "event" key in the payload; a
 * payload with neither is skipped. A payload with a truthy "once" is tracked
 * once per browser, keyed by "once" when it is a string, else by the first of
 * eventID, transaction_id and order_key, else by the whole payload. Every
 * [data-cta] click is tracked as cta_click with { cta_id }.
 */

const META_EVENTS = {
  view_item: 'ViewContent',
  add_to_cart: 'AddToCart',
  begin_checkout: 'InitiateCheckout',
  purchase: 'Purchase',
};

const ONCE_PREFIX = 'ol_tracked:';

/**
 * Meta's standard parameters from our payload shapes: a single item
 * ({ id, name, price, currency }) or an order ({ value, currency, items }).
 */
function metaParams(data) {
  const items = Array.isArray(data.items) ? data.items : data.id !== undefined ? [data] : [];
  const params = { content_type: 'product', content_ids: items.map((item) => String(item.id)) };
  const value = data.value ?? data.price;

  if (value !== undefined) {
    params.value = Number(value);
  }
  if (data.currency) {
    params.currency = data.currency;
  }
  if (data.name) {
    params.content_name = data.name;
  }

  return params;
}

export function track(event, data = {}) {
  const { eventID } = data;
  const payload = { ...data };
  delete payload.eventID;
  delete payload.once;
  delete payload.event;

  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ event: 'ol_' + event, ...payload, ...(eventID ? { event_id: eventID } : {}) });

  if (typeof window.fbq === 'function') {
    if (META_EVENTS[event]) {
      window.fbq('track', META_EVENTS[event], metaParams(payload), eventID ? { eventID } : undefined);
    } else if (event === 'cta_click') {
      window.fbq('trackCustom', 'CTAClick', payload);
    }
  }

  if (typeof window.gtag === 'function') {
    window.gtag('event', event, payload);
  }
}

function alreadyTracked(key) {
  try {
    if (window.localStorage.getItem(ONCE_PREFIX + key)) {
      return true;
    }
    window.localStorage.setItem(ONCE_PREFIX + key, '1');
  } catch {
    // Storage blocked (private mode): tracking twice beats not tracking.
  }

  return false;
}

function trackPrintedPayloads() {
  document.querySelectorAll('script[type="application/json"][data-ol-track]').forEach((script) => {
    let payload;

    try {
      payload = JSON.parse(script.textContent || '{}');
    } catch {
      return;
    }

    const event = script.dataset.olTrack || payload.event;
    if (!event) {
      return;
    }

    if (payload.once) {
      const key = typeof payload.once === 'string'
        ? payload.once
        : payload.eventID || payload.transaction_id || payload.order_key || script.textContent.trim();
      if (alreadyTracked(event + ':' + key)) {
        return;
      }
    }

    track(event, payload);
  });
}

export function init() {
  document.addEventListener('click', (e) => {
    const el = e.target instanceof Element ? e.target.closest('[data-cta]') : null;
    if (el) {
      track('cta_click', { cta_id: el.getAttribute('data-cta') });
    }
  });

  trackPrintedPayloads();
}
