/**
 * The Product page's sticky buy bar (template-parts/single-product/buy-bar.php).
 *
 * [data-buybar] gets data-shown while the price box is above the part of the
 * page the sticky header leaves visible, and loses it when the box comes back.
 * Before the reader reaches the box (below the fold on a phone) it stays
 * hidden.
 *
 * [data-buybar-target] is a sentinel from the top of the hero to the bottom of
 * the price box (see price-box.php), so "not intersecting" means exactly
 * "scrolled past the box", including after a jump. The observer reports each
 * crossing once: the bar slides in or out once per crossing, never per scroll
 * event.
 */

export function init() {
  const bar = document.querySelector('[data-buybar]');
  const target = document.querySelector('[data-buybar-target]');

  if (!bar || !target || !('IntersectionObserver' in window)) {
    return;
  }

  // What the sticky header covers once stuck: its height plus its top offset (the admin bar).
  const header = document.querySelector('[data-site-header]');
  const covered = header ? Math.round(header.offsetHeight + (parseFloat(window.getComputedStyle(header).top) || 0)) : 0;

  const observer = new IntersectionObserver(
    ([entry]) => bar.toggleAttribute('data-shown', !entry.isIntersecting),
    { rootMargin: `-${covered}px 0px 0px 0px` },
  );

  observer.observe(target);
}
