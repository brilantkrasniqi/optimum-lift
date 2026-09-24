/**
 * Mobile menu: a fixed panel over the page (template-parts/header/mobile-menu.php),
 * so opening it never pushes content down.
 *
 * [data-menu-toggle] opens it; the backdrop, [data-menu-close], Escape and any
 * link inside close it. While open, page scroll is locked (html.menu-open) and
 * Tab cycles inside the panel and the rest of the page is `inert`. The panel is
 * `inert` while closed, so its links are not reachable off-screen.
 */

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
const DESKTOP = '(min-width: 1024px)';

export function init() {
  const panel = document.getElementById('ol-mobile-menu');
  const toggle = document.querySelector('[data-menu-toggle]');
  const backdrop = document.querySelector('[data-menu-backdrop]');

  if (!panel || !toggle || !backdrop) {
    return;
  }

  const root = document.documentElement;
  let isOpen = false;
  let madeInert = [];

  const focusables = () => [...panel.querySelectorAll(FOCUSABLE)].filter((el) => el.getClientRects().length > 0);

  function onKeydown(e) {
    if (e.key === 'Escape') {
      e.preventDefault();
      close(true);
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

    if (!panel.contains(document.activeElement)) {
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
    isOpen = true;
    panel.inert = false;
    panel.classList.remove('translate-x-full');
    backdrop.classList.remove('opacity-0', 'pointer-events-none');
    root.classList.add('menu-open');
    toggle.setAttribute('aria-expanded', 'true');

    // As for the cart drawer: everything else is out of reach while the
    // dialog is open, and only what this module made inert is restored.
    madeInert = [...document.body.children].filter(
      (el) => el !== panel && el !== backdrop && el.tagName !== 'SCRIPT' && !el.inert,
    );
    madeInert.forEach((el) => {
      el.inert = true;
    });
    document.addEventListener('keydown', onKeydown);

    const closeButton = panel.querySelector('[data-menu-close]');
    (closeButton || focusables()[0])?.focus({ preventScroll: true });
  }

  function close(restoreFocus) {
    if (!isOpen) {
      return;
    }

    isOpen = false;
    panel.inert = true;
    panel.classList.add('translate-x-full');
    backdrop.classList.add('opacity-0', 'pointer-events-none');
    root.classList.remove('menu-open');
    toggle.setAttribute('aria-expanded', 'false');
    madeInert.forEach((el) => {
      el.inert = false;
    });
    madeInert = [];
    document.removeEventListener('keydown', onKeydown);

    if (restoreFocus) {
      toggle.focus({ preventScroll: true });
    }
  }

  toggle.addEventListener('click', () => (isOpen ? close(true) : open()));
  backdrop.addEventListener('click', () => close(true));

  panel.addEventListener('click', (e) => {
    const target = e.target instanceof Element ? e.target : null;

    if (target?.closest('[data-menu-close]')) {
      close(true);
    } else if (target?.closest('a[href]')) {
      // Let the link navigate (often an in-page anchor): unlock scrolling
      // first, and leave focus where the browser puts it.
      close(false);
    }
  });

  window.matchMedia(DESKTOP).addEventListener('change', (e) => {
    if (e.matches) {
      close(false);
    }
  });
}
