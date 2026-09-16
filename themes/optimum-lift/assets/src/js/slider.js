/**
 * Splide sliders. Registered as `optimum-lift-slider` but not enqueued globally:
 * a template that outputs a slider calls wp_enqueue_script('optimum-lift-slider')
 * so pages without one don't pay for Splide.
 *
 * Markup follows Splide's structure. Options go in a `data-splide` JSON
 * attribute, which Splide reads itself:
 *
 *   <section class="splide" aria-label="…" data-splide='{"perPage":3}'>
 *     <div class="splide__track"><ul class="splide__list">
 *       <li class="splide__slide">…</li>
 *     </ul></div>
 *   </section>
 *
 * Splide's core CSS is bundled into main.css; style arrows and pagination with
 * utilities or in the components layer.
 */

import Splide from '@splidejs/splide';

document.querySelectorAll('.splide').forEach((element) => {
  new Splide(element).mount();
});
