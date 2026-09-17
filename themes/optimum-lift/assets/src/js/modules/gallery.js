/**
 * Product gallery (template-parts/single-product/gallery.php):
 *
 *   <div data-gallery>
 *     <img data-gallery-image srcset="…" sizes="…">
 *     <button data-gallery-thumb data-src="…" data-srcset="…" data-alt="…" aria-pressed="true">…</button>
 *   </div>
 *
 * A thumbnail puts its image in the main slot, srcset included, so the
 * browser still picks the right size, and becomes the pressed one.
 */

export function init() {
  document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const image = gallery.querySelector('[data-gallery-image]');
    if (!image) {
      return;
    }

    gallery.addEventListener('click', (event) => {
      const thumb = event.target instanceof Element ? event.target.closest('[data-gallery-thumb]') : null;
      if (!thumb || thumb.getAttribute('aria-pressed') === 'true') {
        return;
      }

      // srcset before src: with a srcset present the browser ignores src.
      if (thumb.dataset.srcset) {
        image.srcset = thumb.dataset.srcset;
      } else {
        image.removeAttribute('srcset');
      }
      image.src = thumb.dataset.src || '';
      image.alt = thumb.dataset.alt || '';

      gallery.querySelectorAll('[data-gallery-thumb]').forEach((other) => {
        other.setAttribute('aria-pressed', String(other === thumb));
      });
    });
  });
}
