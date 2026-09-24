/**
 * FAQ accordion (template-parts/blocks/faq.php, CSS in components.css).
 *
 * One item open at a time per [data-accordion] container, so two FAQ blocks
 * on a page stay independent. Collapsed answers are `inert`: a 0fr grid row
 * hides them visually but would leave their links focusable. Without
 * JavaScript every answer shows.
 */

export function init() {
  const containers = document.querySelectorAll('[data-accordion]');
  if (!containers.length) {
    return;
  }

  const items = (container) => [...container.querySelectorAll('.acc')].filter((acc) => acc.closest('[data-accordion]') === container);

  function set(acc, open) {
    acc.classList.toggle('open', open);
    acc.querySelector('.acc-btn')?.setAttribute('aria-expanded', String(open));
    const body = acc.querySelector('.acc-body');
    if (body) {
      body.inert = !open;
    }
  }

  containers.forEach((container) => {
    items(container).forEach((acc) => set(acc, acc.classList.contains('open')));

    container.addEventListener('click', (e) => {
      const button = e.target instanceof Element ? e.target.closest('.acc-btn') : null;
      const acc = button?.closest('.acc');
      if (!acc || acc.closest('[data-accordion]') !== container) {
        return;
      }

      const open = !acc.classList.contains('open');
      items(container).forEach((other) => set(other, other === acc ? open : false));
    });
  });
}
