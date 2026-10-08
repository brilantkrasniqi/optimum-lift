/**
 * Cookie consent (inc/consent.php): the banner, and what loads after "Accept".
 *
 *   <div data-ol-consent data-pixel="123" hidden>
 *     … <button data-ol-consent-choice="no"> <button data-ol-consent-choice="yes">
 *   </div>
 *   <button data-ol-consent-open>   (footer, reopens the banner)
 *
 * The choice is the cookie ol_consent=yes|no. On "yes" the Meta Pixel loads
 * and WooCommerce's order attribution switches on. Runs before track.js, so
 * events a page prints find the Pixel already queued for a visitor who
 * accepted earlier. A loaded Pixel cannot be unloaded, so changing "yes" to
 * "no" reloads the page.
 */

const COOKIE = 'ol_consent';
const MAX_AGE = 60 * 60 * 24 * 182;

function choice() {
  const match = document.cookie.match(/(?:^|;\s*)ol_consent=(yes|no)/);
  return match ? match[1] : '';
}

function remember(value) {
  const secure = window.location.protocol === 'https:' ? '; Secure' : '';
  document.cookie = `${COOKIE}=${value}; path=/; max-age=${MAX_AGE}; SameSite=Lax${secure}`;
}

function loadPixel(id) {
  if (!id || window.fbq) {
    return;
  }

  // Meta's base code: a queue that records calls until fbevents.js arrives.
  const fbq = function (...args) {
    if (fbq.callMethod) {
      fbq.callMethod(...args);
    } else {
      fbq.queue.push(args);
    }
  };
  fbq.push = fbq;
  fbq.loaded = true;
  fbq.version = '2.0';
  fbq.queue = [];
  window.fbq = fbq;
  window._fbq = window._fbq || fbq;

  const script = document.createElement('script');
  script.async = true;
  script.src = 'https://connect.facebook.net/en_US/fbevents.js';
  document.head.appendChild(script);

  window.fbq('init', id);
  window.fbq('track', 'PageView');
}

function setOrderAttribution(allow) {
  const apply = () => window.wc_order_attribution?.setOrderTracking?.(allow);

  if (window.wc_order_attribution) {
    apply();
  } else if (document.readyState !== 'complete') {
    window.addEventListener('load', apply, { once: true });
  }
}

function grant(banner) {
  loadPixel(banner.dataset.pixel);
  setOrderAttribution(true);
}

export function init() {
  const banner = document.querySelector('[data-ol-consent]');
  if (!banner) {
    return;
  }

  const current = choice();
  if (current === 'yes') {
    grant(banner);
  } else if (current === '') {
    banner.hidden = false;
  }

  banner.addEventListener('click', (e) => {
    const button = e.target instanceof Element ? e.target.closest('[data-ol-consent-choice]') : null;
    if (!button) {
      return;
    }

    const value = button.getAttribute('data-ol-consent-choice') === 'yes' ? 'yes' : 'no';
    const before = choice();
    remember(value);
    banner.hidden = true;

    if (value === 'yes') {
      grant(banner);
    } else {
      setOrderAttribution(false);
      if (before === 'yes') {
        window.location.reload();
      }
    }
  });

  document.addEventListener('click', (e) => {
    if (e.target instanceof Element && e.target.closest('[data-ol-consent-open]')) {
      banner.hidden = false;
      banner.querySelector('[data-ol-consent-choice]')?.focus();
    }
  });
}
