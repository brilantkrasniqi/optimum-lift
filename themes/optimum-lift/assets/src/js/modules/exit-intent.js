/**
 * The homepage exit-intent offer (template-parts/home/exit-modal.php).
 *
 * Desktop only (a fine pointer), armed after 8 s on the page, opened when the
 * pointer leaves through the top of the window, and at most once per 7 days
 * (localStorage `ol_exit_seen`; blocked storage counts as seen, so the offer
 * never nags someone we cannot remember). While open it is a modal dialog:
 * page scroll locked, the rest of the page inert, Tab trapped, Escape and the
 * backdrop close it. The call to action is a plain link (a coupon link), so it
 * only records "seen" and navigates.
 */

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
const KEY = 'ol_exit_seen';
const ARM_AFTER = 8000;
const COOLDOWN = 7 * 24 * 60 * 60 * 1000;

function seenRecently() {
  try {
    const at = Number(window.localStorage.getItem(KEY));
    return Number.isFinite(at) && at > 0 && Date.now() - at < COOLDOWN;
  } catch (error) {
    return true;
  }
}

function markSeen() {
  try {
    window.localStorage.setItem(KEY, String(Date.now()));
  } catch (error) {
    // Nothing to remember it with; seenRecently() already treats that as seen.
  }
}

export function init() {
  const overlay = document.querySelector('[data-exit-modal]');
  if (!overlay || !window.matchMedia('(pointer: fine)').matches || seenRecently()) {
    return;
  }

  const dialog = overlay.querySelector('[role="dialog"]') || overlay;
  const root = document.documentElement;
  let isOpen = false;
  let opener = null;
  let madeInert = [];

  const focusables = () => [...dialog.querySelectorAll(FOCUSABLE)].filter((el) => el.getClientRects().length > 0);

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
      return;
    }

    const first = items[0];
    const last = items[items.length - 1];

    if (!dialog.contains(document.activeElement)) {
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

  function open() {
    if (isOpen || seenRecently()) {
      return;
    }

    isOpen = true;
    markSeen();
    opener = document.activeElement;
    overlay.hidden = false;
    root.classList.add('ol-modal-open');

    madeInert = [...document.body.children].filter((el) => el !== overlay && el.tagName !== 'SCRIPT' && !el.inert);
    madeInert.forEach((el) => {
      el.inert = true;
    });

    document.addEventListener('keydown', onKeydown);
    overlay.querySelector('[data-exit-close]')?.focus({ preventScroll: true });
  }

  function close() {
    if (!isOpen) {
      return;
    }

    isOpen = false;
    overlay.hidden = true;
    root.classList.remove('ol-modal-open');
    madeInert.forEach((el) => {
      el.inert = false;
    });
    madeInert = [];
    document.removeEventListener('keydown', onKeydown);

    if (opener instanceof HTMLElement && opener.isConnected) {
      opener.focus({ preventScroll: true });
    }
    opener = null;
  }

  overlay.addEventListener('click', (e) => {
    const target = e.target instanceof Element ? e.target : null;
    if (target === overlay || target?.closest('[data-exit-close]')) {
      close();
    } else if (target?.closest('[data-exit-cta]')) {
      markSeen();
      // The link navigates (often to an anchor on this page): unlock the page first.
      close();
    }
  });

  function onLeave(e) {
    if (!e.relatedTarget && e.clientY <= 0) {
      document.removeEventListener('mouseout', onLeave);
      open();
    }
  }

  window.setTimeout(() => document.addEventListener('mouseout', onLeave), ARM_AFTER);
}
