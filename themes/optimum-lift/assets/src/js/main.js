/**
 * Front-end behaviour, bundled to assets/dist/main.js (deferred, no jQuery).
 *
 * Every module exports init() and returns early when its markup is not on the
 * page, so one bundle serves every template. User-facing strings never live
 * here: templates render them and modules read them from data-* attributes.
 * Page data the server provides is on window.optimumLift (inc/assets.php).
 */

import * as track from './modules/track.js';
import * as countdown from './modules/countdown.js';
import * as menu from './modules/menu.js';
import * as reveal from './modules/reveal.js';
import * as accordion from './modules/accordion.js';
import * as tabs from './modules/tabs.js';
import * as gallery from './modules/gallery.js';
import * as buybar from './modules/buybar.js';
import * as stickyCta from './modules/sticky-cta.js';
import * as exitIntent from './modules/exit-intent.js';
import * as shopSort from './modules/shop-sort.js';
import * as cart from './modules/cart.js';

const modules = [track, countdown, menu, reveal, accordion, tabs, gallery, buybar, stickyCta, exitIntent, shopSort, cart];

function boot() {
  modules.forEach((module) => {
    // One failing module must not take the buy buttons down with it.
    try {
      module.init();
    } catch (error) {
      console.error(error);
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
  boot();
}
