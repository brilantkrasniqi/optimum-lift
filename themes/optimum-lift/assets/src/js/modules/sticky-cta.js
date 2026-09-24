/**
 * The homepage's mobile sticky CTA (template-parts/home/sticky-cta.php).
 *
 * [data-sticky-cta] gets data-shown once the page has scrolled past 700px and
 * loses it above that; while hidden it is inert, so its link is not reachable
 * off-screen. Without JavaScript it simply stays visible.
 */

const THRESHOLD = 700;

export function init() {
  const bar = document.querySelector('[data-sticky-cta]');
  if (!bar) {
    return;
  }

  let shown = null;

  function update() {
    const next = window.scrollY > THRESHOLD;
    if (next === shown) {
      return;
    }

    shown = next;
    bar.toggleAttribute('data-shown', next);
    bar.inert = !next;
  }

  update();
  window.addEventListener('scroll', update, { passive: true });
}
