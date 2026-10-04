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
      error.code = data && data.code;
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

  // Per set, the request on its way. Clearing a set fires two changes (load,
  // then reps): sent at once, the server could apply them out of order and
  // keep the set the Customer just cleared.
  const inFlight = {};

  /**
   * Sends an edit after any earlier edit of the same set has finished, and
   * skips it when a newer edit of that set has replaced it meanwhile.
   *
   * @returns {Promise<boolean>} false when the network is unavailable.
   */
  function send(operation) {
    const previous = inFlight[operation.key] || Promise.resolve(true);
    const current = previous.then(() => {
      const newest = queue[operation.key];

      return newest && newest.id !== operation.id ? true : transmit(operation);
    });

    inFlight[operation.key] = current;
    current.finally(() => {
      if (inFlight[operation.key] === current) {
        delete inFlight[operation.key];
      }
    });

    return current;
  }

  /**
   * @returns {Promise<boolean>} false when the network is unavailable.
   */
  async function transmit(operation) {
    const row = rowFor(operation.uid, operation.set);

    showStatus(row, i18n.saving, 'saving');

    try {
      if (operation.body) {
        const data = await request('PUT', `workout-logs/${logId}/sets`, operation.body);

        settle(operation);
        syncStatus(data && data.log_status);
        showStatus(row, data && data.personal_record ? i18n.personalRecord : '✓', data && data.personal_record ? 'record' : 'saved');
      } else {
        const query = new URLSearchParams({ prescription_uid: operation.uid, set_number: String(operation.set) });
        const data = await request('DELETE', `workout-logs/${logId}/sets?${query}`);

        settle(operation);
        syncStatus(data && data.log_status);
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
  const confirmDialog = root.querySelector('[data-finish-confirm]');

  /**
   * A finished Workout whose sets were all cleared is back in progress
   * (RestController::reopenIfEmpty), so its button finishes it again.
   */
  function syncStatus(status) {
    if (finish && status === 'in_progress') {
      finish.textContent = i18n.finish;
    }
  }

  /**
   * The server decides whether the Workout can be finished: it refuses an
   * empty one, and asks before finishing one with much left (409) until the
   * request confirms.
   */
  async function complete(confirmed) {
    const notes = root.querySelector('[data-workout-notes]');

    try {
      await request('POST', `workout-logs/${logId}/complete`, { notes: notes ? notes.value : '', confirm: confirmed });
      window.location.assign(root.dataset.planUrl);
    } catch (error) {
      if (error.code === 'ol_workout_unfinished' && !confirmed) {
        askToFinish(error.message);

        return;
      }

      if (!error.status) {
        message.textContent = i18n.queued;
      } else {
        message.textContent = error.code === 'ol_nothing_logged' ? error.message : `${i18n.failed}: ${error.message}`;
      }

      finish.disabled = false;
    }
  }

  function askToFinish(progress) {
    // A theme override of the template without the dialog.
    if (!confirmDialog || typeof confirmDialog.showModal !== 'function') {
      if (window.confirm(progress)) {
        complete(true);
      } else {
        finish.disabled = false;
      }

      return;
    }

    confirmDialog.querySelector('[data-finish-confirm-progress]').textContent = progress;
    confirmDialog.returnValue = '';
    confirmDialog.showModal();
  }

  if (confirmDialog) {
    // Escape closes it with no return value, which is the same as going back.
    confirmDialog.addEventListener('close', () => {
      if (confirmDialog.returnValue === 'finish') {
        complete(true);

        return;
      }

      finish.disabled = false;
      finish.focus();
    });
  }

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

      await complete(false);
    });
  }
})();
