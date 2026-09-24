/**
 * Goal tabs (template-parts/blocks/goal-tabs.php): the WAI-ARIA tabs pattern.
 *
 * Click, ArrowLeft/ArrowRight (wrapping), Home and End select a tab; focus
 * follows the selection and the roving tabindex moves with it, so Tab leaves
 * the tab list for the panel. Panels after the first are hidden before first
 * paint by `js:hidden`; this module swaps that class for the `hidden`
 * attribute it can toggle. Without JavaScript the tab list stays hidden and
 * every panel shows, stacked.
 */

export function init() {
  document.querySelectorAll('[data-tabs]').forEach((root) => {
    const tablist = root.querySelector('[role="tablist"]');
    if (!tablist) {
      return;
    }

    const tabs = [...tablist.querySelectorAll('[role="tab"]')];
    const panelOf = (tab) => document.getElementById(tab.getAttribute('aria-controls'));

    function select(tab, focus) {
      tabs.forEach((other) => {
        const selected = other === tab;
        other.setAttribute('aria-selected', String(selected));
        other.tabIndex = selected ? 0 : -1;
        const panel = panelOf(other);
        if (panel) {
          panel.hidden = !selected;
        }
      });

      if (focus) {
        tab.focus();
      }
    }

    const current = tabs.find((tab) => tab.getAttribute('aria-selected') === 'true') || tabs[0];
    tabs.forEach((tab) => panelOf(tab)?.classList.remove('js:hidden'));
    select(current, false);

    tablist.addEventListener('click', (e) => {
      const tab = e.target instanceof Element ? e.target.closest('[role="tab"]') : null;
      if (tab && tabs.includes(tab)) {
        select(tab, false);
      }
    });

    tablist.addEventListener('keydown', (e) => {
      const index = tabs.indexOf(document.activeElement);
      if (index < 0) {
        return;
      }

      const next = {
        ArrowRight: tabs[(index + 1) % tabs.length],
        ArrowLeft: tabs[(index - 1 + tabs.length) % tabs.length],
        Home: tabs[0],
        End: tabs[tabs.length - 1],
      }[e.key];

      if (next) {
        e.preventDefault();
        select(next, true);
      }
    });
  });
}
