/**
 * Training › Import: runs the Exercise library import one batch at a time.
 * Every batch is safe to repeat, so a failed batch is retried from where it
 * stopped, and an import cut short by closing the tab is finished by starting
 * it again.
 *
 * Markup contract: templates/admin/import-exercises.php. Plain ES2020, no
 * build step, so the plugin works without the theme's toolchain.
 */
(() => {
  const root = document.querySelector('[data-ol-import]');
  const button = root?.querySelector('[data-start]');

  if (!root || !button || !window.olImport) {
    return;
  }

  const { ajaxUrl, action, nonce, i18n } = window.olImport;
  const total = Number(root.dataset.total);
  const progress = root.querySelector('[data-progress]');
  const bar = progress.querySelector('progress');
  const status = root.querySelector('[data-status]');
  const result = root.querySelector('[data-result]');

  const counts = {};
  const notes = [];
  const errors = [];
  let offset = 0;
  let running = false;

  function element(tag, props = {}, children = []) {
    const node = Object.assign(document.createElement(tag), props);
    node.append(...children);
    return node;
  }

  async function batch() {
    const body = new FormData();
    body.append('action', action);
    body.append('_ajax_nonce', nonce);
    body.append('offset', String(offset));

    let response;

    try {
      response = await fetch(ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
    } catch {
      throw new Error(i18n.offline);
    }

    const json = await response.json().catch(() => null);

    if (!json || !json.success) {
      throw new Error(json?.data?.message || `HTTP ${response.status}`);
    }

    return json.data;
  }

  function showProgress() {
    bar.value = offset;
    status.textContent = i18n.progress.replace('%1$d', offset).replace('%2$d', total);
  }

  function showResult() {
    const rows = Object.entries(i18n.counts)
      .filter(([key]) => counts[key] > 0)
      .map(([key, label]) => element('tr', {}, [
        element('td', { textContent: label }),
        element('td', { textContent: counts[key].toLocaleString(), style: 'width: 6rem; text-align: right;' }),
      ]));

    const parts = [
      element('div', { className: `notice inline ${errors.length ? 'notice-warning' : 'notice-success'}` }, [
        element('p', { textContent: errors.length ? i18n.errors : i18n.done }),
      ]),
      element('table', { className: 'widefat striped' }, [element('tbody', {}, rows)]),
    ];

    if (errors.length) {
      parts.push(element('ul', { style: 'list-style: disc; padding-left: 1.5em;' }, errors.map((text) => element('li', { textContent: text }))));
    }

    if (notes.length) {
      parts.push(element('details', {}, [
        element('summary', { textContent: i18n.details.replace('%d', notes.length) }),
        element('ul', { style: 'list-style: disc; padding-left: 1.5em;' }, notes.map((text) => element('li', { textContent: text }))),
      ]));
    }

    parts.push(element('p', {}, [element('a', { className: 'button', href: root.dataset.exercises, textContent: i18n.view })]));

    result.replaceChildren(...parts);
    result.hidden = false;
  }

  async function run() {
    running = true;
    button.disabled = true;
    progress.hidden = false;
    showProgress();

    try {
      for (;;) {
        const data = await batch();

        for (const [key, value] of Object.entries(data.counts)) {
          counts[key] = (counts[key] || 0) + value;
        }

        notes.push(...data.notes);
        errors.push(...data.errors);
        offset = data.next;
        showProgress();

        if (data.finished) {
          break;
        }
      }

      // The preview's numbers are out of date now. Hide its wrapper rather
      // than the button: wp-admin's .button display rule beats [hidden].
      root.querySelector('[data-plan]').hidden = true;
      // The result's notice says it visibly; the status still announces it.
      status.className = 'screen-reader-text';
      status.textContent = errors.length ? i18n.errors : i18n.done;
      showResult();
    } catch (error) {
      // offset still points at the batch that failed, so Continue retries it.
      status.textContent = i18n.failed.replace('%s', error.message);
      button.textContent = i18n.retry;
      button.disabled = false;
    } finally {
      running = false;
    }
  }

  button.addEventListener('click', () => {
    if (!running) {
      run();
    }
  });

  window.addEventListener('beforeunload', (event) => {
    if (running) {
      event.preventDefault();
      event.returnValue = i18n.leave;
    }
  });
})();
