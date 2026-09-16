/**
 * Workout screen: saves each set as it changes, keeps saves that failed for
 * lack of signal in localStorage, and retries them.
 *
 * Markup contract: templates/portal/workout.php. Plain ES2020, no build step,
 * so the plugin works without the theme's toolchain.
 */
(() => {
  const root = document.querySelector('.ol-workout[data-log-id]');

  if (!root || !window.olPortal) {
    return;
  }

  const { root: api, nonce, i18n } = window.olPortal;
  const logId = root.dataset.logId;
  const queueKey = `ol-plans-queue-${logId}`;

  // key "uid:set" -> { key, id, uid, set, body }. body null means delete.
  let queue = readQueue();
  let flushing = false;

  function readQueue() {
    try {
      return JSON.parse(localStorage.getItem(queueKey) || '{}');
    } catch {
      return {};
    }
  }

  function writeQueue() {
    try {
      if (Object.keys(queue).length) {
        localStorage.setItem(queueKey, JSON.stringify(queue));
      } else {
        localStorage.removeItem(queueKey);
      }
    } catch {
      // Private mode or storage full: the in-memory queue still retries.
    }
  }

  async function request(method, path, body) {
    const response = await fetch(api + path, {
      method,
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      body: body ? JSON.stringify(body) : undefined,
    });

    if (response.status === 204) {
      return null;
    }

    const data = await response.json().catch(() => null);

    if (!response.ok) {
      const error = new Error((data && data.message) || response.statusText);
      error.status = response.status;
      throw error;
    }

    return data;
  }

  function parseNumber(value) {
    const normalised = value.trim().replace(',', '.');

    if (normalised === '') {
      return null;
    }

    const number = Number(normalised);

    return Number.isFinite(number) && number >= 0 ? number : NaN;
  }

  function rowFor(uid, set) {
    return root.querySelector(`[data-prescription-uid="${uid}"] .ol-set[data-set-number="${set}"]`);
  }

  function showStatus(row, text, state) {
    const cell = row && row.querySelector('.ol-set__status');

    if (cell) {
      cell.textContent = text;
      cell.dataset.state = state;
    }
  }

  /**
   * The save a row currently asks for, or null when there is nothing to save
   * yet (a load without reps).
   */
  function operationFor(row) {
    const uid = row.closest('[data-prescription-uid]').dataset.prescriptionUid;
    const set = Number(row.dataset.setNumber);
    const resultField = row.dataset.resultField;
    const load = parseNumber(row.querySelector('[data-field="load_kg"]').value);
    const result = parseNumber(row.querySelector(`[data-field="${resultField}"]`).value);

    if (Number.isNaN(load) || Number.isNaN(result)) {
      return { invalid: true };
    }

    const operation = { key: `${uid}:${set}`, id: `${Date.now()}-${Math.random()}`, uid, set, body: null };

    if (result === null) {
      return load === null ? operation : null;
    }

    operation.body = { prescription_uid: uid, set_number: set, load_kg: load, [resultField]: Math.round(result) };

    return operation;
  }

  function settle(operation) {
    // A newer edit of the same set may have been queued while this one was in flight.
    if (queue[operation.key] && queue[operation.key].id === operation.id) {
      delete queue[operation.key];
      writeQueue();
    }
  }

  /**
   * @returns {Promise<boolean>} false when the network is unavailable.
   */
  async function send(operation) {
    const row = rowFor(operation.uid, operation.set);

    showStatus(row, i18n.saving, 'saving');

    try {
      if (operation.body) {
        const data = await request('PUT', `workout-logs/${logId}/sets`, operation.body);

        settle(operation);
        showStatus(row, data && data.personal_record ? i18n.personalRecord : '✓', data && data.personal_record ? 'record' : 'saved');
      } else {
        const query = new URLSearchParams({ prescription_uid: operation.uid, set_number: String(operation.set) });

        await request('DELETE', `workout-logs/${logId}/sets?${query}`);
        settle(operation);
        showStatus(row, '', 'saved');
      }

      return true;
    } catch (error) {
      if (error.status) {
        // The server refused it; retrying the same request will not help.
        settle(operation);
        showStatus(row, `${i18n.failed}: ${error.message}`, 'error');

        return true;
      }

      showStatus(row, i18n.queued, 'queued');

      return false;
    }
  }

  async function flush() {
    if (flushing) {
      return;
    }

    flushing = true;

    try {
      for (const operation of Object.values(queue)) {
        if (!(await send(operation))) {
          break;
        }
      }
    } finally {
      flushing = false;
    }
  }

  root.addEventListener('change', (event) => {
    const row = event.target.closest('.ol-set');

    if (!row) {
      return;
    }

    const operation = operationFor(row);

    if (operation === null) {
      showStatus(row, '', '');

      return;
    }

    if (operation.invalid) {
      showStatus(row, i18n.failed, 'error');

      return;
    }

    queue[operation.key] = operation;
    writeQueue();
    send(operation);
  });

  // Put unsaved values from an earlier visit back into their inputs.
  for (const operation of Object.values(queue)) {
    const row = rowFor(operation.uid, operation.set);

    if (!row) {
      continue;
    }

    const resultField = row.dataset.resultField;
    const body = operation.body || {};

    row.querySelector('[data-field="load_kg"]').value = body.load_kg ?? '';
    row.querySelector(`[data-field="${resultField}"]`).value = body[resultField] ?? '';
    showStatus(row, i18n.queued, 'queued');
  }

  window.addEventListener('online', flush);
  setInterval(() => Object.keys(queue).length && flush(), 20000);
  flush();

  const finish = root.querySelector('[data-finish-workout]');
  const message = root.querySelector('.ol-finish-message');

  if (finish) {
    finish.addEventListener('click', async () => {
      finish.disabled = true;
      message.textContent = '';

      await flush();

      if (Object.keys(queue).length) {
        message.textContent = i18n.unsaved;
        finish.disabled = false;

        return;
      }

      try {
        const notes = root.querySelector('[data-workout-notes]');

        await request('POST', `workout-logs/${logId}/complete`, { notes: notes ? notes.value : '' });
        window.location.assign(root.dataset.planUrl);
      } catch (error) {
        message.textContent = error.status ? `${i18n.failed}: ${error.message}` : i18n.queued;
        finish.disabled = false;
      }
    });
  }
})();
