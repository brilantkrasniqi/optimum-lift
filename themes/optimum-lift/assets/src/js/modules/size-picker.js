/**
 * The Size picker (template-parts/product/size-picker.php). The form works
 * without this module, which only makes it nicer:
 *
 * - options that cannot complete an available Size, given the other groups'
 *   choices, are disabled, and a choice that makes another group's checked
 *   option impossible unchecks that option;
 * - `variation_id` holds the matching variation, or is empty;
 * - every [data-size-summary] (the buy bar) shows the chosen Size;
 * - the form dispatches `ol:size:change` with { variationId, label }.
 *
 * Sending the add through the drawer is modules/cart.js's job.
 */

function readVariations(form) {
  try {
    const list = JSON.parse(form.querySelector('[data-size-variations]')?.textContent || '[]');
    return Array.isArray(list) ? list.filter((v) => v && v.available && v.attributes) : [];
  } catch (error) {
    console.error(error);
    return [];
  }
}

export function init() {
  const form = document.querySelector('[data-size-picker]');
  if (!(form instanceof HTMLFormElement)) {
    return;
  }

  const variations = readVariations(form);
  const idField = form.querySelector('input[name="variation_id"]');
  const notice = form.querySelector('[data-size-notice]');
  const groups = [...form.querySelectorAll('fieldset')]
    .map((fieldset) => {
      const inputs = [...fieldset.querySelectorAll('input[type="radio"]')];
      return { name: inputs[0]?.name || '', inputs };
    })
    .filter((group) => group.name !== '');

  const chosen = () => Object.fromEntries(groups.map((g) => [g.name, g.inputs.find((i) => i.checked)?.value || '']));

  // A variation fits a choice when it has every chosen value. An "Any" value
  // ('') fits nothing: those Sizes are not sold (inc/shop/sizes.php).
  const fits = (variation, choice) => Object.entries(choice).every(([name, value]) => value === '' || variation.attributes[name] === value);
  const sold = (choice) => variations.some((v) => fits(v, choice));

  function update(changed) {
    let choice = chosen();

    // Keep the group just changed; give up the others' choices until it fits.
    groups.forEach((group) => {
      if (group.name !== changed && choice[group.name] !== '' && !sold(choice)) {
        group.inputs.forEach((input) => {
          input.checked = false;
        });
        choice = chosen();
      }
    });

    groups.forEach((group) => {
      group.inputs.forEach((input) => {
        input.disabled = !sold({ ...choice, [group.name]: input.value });
      });
    });

    const complete = groups.every((g) => choice[g.name] !== '');
    const match = complete ? variations.find((v) => fits(v, choice)) : undefined;
    const label = groups
      .map((g) => g.inputs.find((i) => i.checked)?.closest('label')?.textContent.trim() || '')
      .filter(Boolean)
      .join(' · ');

    if (idField) {
      idField.value = match ? String(match.id) : '';
    }

    document.querySelectorAll('[data-size-summary]').forEach((summary) => {
      summary.textContent = match ? label : summary.dataset.empty || '';
    });

    form.dispatchEvent(new CustomEvent('ol:size:change', {
      bubbles: true,
      detail: { variationId: match ? match.id : null, label: match ? label : '' },
    }));
  }

  form.addEventListener('change', (e) => {
    if (notice) {
      notice.innerHTML = '';
    }
    update(e.target instanceof HTMLInputElement ? e.target.name : null);
  });

  // The back/forward cache restores checked radios but not this state.
  window.addEventListener('pageshow', (e) => {
    if (e.persisted) {
      update(null);
    }
  });

  update(null);
}
