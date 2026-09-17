/**
 * Offer countdowns, rendered by template-parts/components/countdown.php:
 *
 *   <span data-countdown="2026-09-19T21:59:00+00:00">
 *     <span data-cd-unit="d"><span data-cd="d">2</span>d </span><span data-cd="h">04</span>:<span data-cd="m">05</span>:<span data-cd="s">06</span>
 *   </span>
 *
 * The end date is real (ADR-0008), so at zero the offer is over: the nearest
 * [data-countdown-scope] around each countdown is hidden instead of resetting.
 * A [data-cd-unit="d"] wrapper is hidden while less than a day remains.
 */

const pad = (n) => String(n).padStart(2, '0');

function render(el, endsAt) {
  const left = Math.max(0, Math.floor((endsAt - Date.now()) / 1000));
  const values = {
    d: Math.floor(left / 86400),
    h: Math.floor((left % 86400) / 3600),
    m: Math.floor((left % 3600) / 60),
    s: left % 60,
  };

  el.querySelectorAll('[data-cd]').forEach((part) => {
    const key = part.getAttribute('data-cd');
    const text = key === 'd' ? String(values.d) : pad(values[key] ?? 0);
    if (part.textContent !== text) {
      part.textContent = text;
    }
  });

  el.querySelectorAll('[data-cd-unit="d"]').forEach((unit) => {
    unit.hidden = values.d === 0;
  });

  if (left === 0) {
    const scope = el.closest('[data-countdown-scope]');
    if (scope) {
      scope.hidden = true;
    }
  }

  return left > 0;
}

export function init() {
  let timers = [...document.querySelectorAll('[data-countdown]')]
    .map((el) => ({ el, endsAt: Date.parse(el.getAttribute('data-countdown') || '') }))
    .filter((timer) => !Number.isNaN(timer.endsAt));

  if (!timers.length) {
    return;
  }

  const tick = () => {
    timers = timers.filter((timer) => render(timer.el, timer.endsAt));
    if (!timers.length) {
      window.clearInterval(interval);
    }
  };

  const interval = window.setInterval(tick, 1000);
  tick();
}
