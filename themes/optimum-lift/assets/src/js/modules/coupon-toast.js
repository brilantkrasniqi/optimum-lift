/**
 * The coupon link's confirmation (template-parts/components/coupon-toast.php).
 *
 * It is printed hidden and shown a moment after load, so the status region
 * changes while the page is up and screen readers announce it. It stays until
 * closed, or until the cart drawer opens: the drawer then shows the discount.
 */

const SHOW_AFTER = 400;

export function init() {
  const toast = document.querySelector('[data-coupon-toast]');
  if (!toast) {
    return;
  }

  const hide = () => {
    toast.hidden = true;
  };

  toast.querySelector('[data-coupon-toast-close]')?.addEventListener('click', hide);
  document.addEventListener('ol:cart:open', hide, { once: true });

  window.setTimeout(() => {
    toast.hidden = false;
  }, SHOW_AFTER);
}
