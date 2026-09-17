/**
 * Scroll reveal: .reveal elements are hidden by CSS only under html.js, and
 * get .in as they enter the viewport. Never put .reveal above the fold.
 */

export function init() {
  const elements = document.querySelectorAll('.reveal');

  if (!elements.length) {
    return;
  }

  if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    elements.forEach((el) => el.classList.add('in'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
  );

  elements.forEach((el) => observer.observe(el));
}
