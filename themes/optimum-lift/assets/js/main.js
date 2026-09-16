/**
 * Front-end behaviour. Deferred, so the DOM is parsed by the time this runs.
 *
 * WooCommerce replaces fragments (the cart count, mini-cart) over AJAX and
 * fires `wc_fragments_refreshed` afterwards. Anything that decorates those
 * nodes has to re-run on that event, not just on load.
 */

(function () {
  'use strict';

  function onFragmentsRefreshed() {
    // Re-bind anything that touches cart markup here.
  }

  document.body.addEventListener('wc_fragments_refreshed', onFragmentsRefreshed);
  document.body.addEventListener('wc_fragments_loaded', onFragmentsRefreshed);
})();
