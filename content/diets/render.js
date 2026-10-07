// Renders a diet plan JSON into branded A4 PDFs, one per size (gender × weight range).
//
//   node render.js plans/<plan>.json                 every size -> out/<plan>/<plan>-<gender>-<weight>kg.pdf
//   node render.js plans/<plan>.json --only m-80-90  one size (see the report for the codes)
//   node render.js plans/<plan>.json --png           also one PNG per page of the reference size, for checking
//   node render.js plans/<plan>.json --out <dir>     write to <dir>/<plan>/ instead of out/<plan>/
//   node render.js plans/<plan>.json --dump          write each size's computed plan as JSON, no PDFs
//                                                    (needs no Playwright; used to prove a change moved no number)
//   node render.js plans/<plan>.json --recipes <dir> read Recipes from <dir> instead of recipes/
//
// A plan's meals name Recipes (recipes/<key>.json, see lib/recipes.js), optionally with a portion
// and per-Food grams for this plan.
// The plan is written once, for the reference person in sizes.json. Each size scales every
// portion by the same factor and rounds it to something a cook can measure. Every calorie and
// macro number is then computed from foods.json, so the numbers always match the grams printed.

const fs = require('fs');
const path = require('path');
const { loadRecipes, resolvePlan } = require('./lib/recipes');

const ROOT = __dirname;
// The PDFs use the theme's own copy of the fonts, so the site and the PDFs never drift apart.
const FONTS = path.join(ROOT, '..', '..', 'themes', 'optimum-lift', 'assets', 'fonts');
const args = process.argv.slice(2);
const option = (name) => (args.includes(name) ? args[args.indexOf(name) + 1] : null);
const VALUED = ['--only', '--out', '--recipes'];
const planArg = args.find((a, i) => a.endsWith('.json') && !VALUED.includes(args[i - 1]));
const only = option('--only');
const wantPng = args.includes('--png');
const dump = args.includes('--dump');
if (!planArg || !fs.existsSync(path.resolve(planArg)) || VALUED.some((o) => args.includes(o) && !option(o))) {
  console.error('Usage: node render.js plans/<plan>.json [--only <size>] [--png] [--out <dir>] [--dump] [--recipes <dir>]');
  process.exit(1);
}
// Paths in messages are relative to this folder, the way the README writes them.
const rel = (p) => {
  const r = path.relative(ROOT, p).split(path.sep).join('/');
  return r.startsWith('..') ? p : r || '.';
};
const planPath = path.resolve(planArg);
const source = JSON.parse(fs.readFileSync(planPath, 'utf8'));
const { foods, groups } = JSON.parse(fs.readFileSync(path.join(ROOT, 'foods.json'), 'utf8'));
const defaults = JSON.parse(fs.readFileSync(path.join(ROOT, 'sizes.json'), 'utf8'));
const slug = path.basename(planPath, '.json');

// ---------- recipes ----------
// Every Recipe is checked, used or not, and every meal is resolved before any scaling.
const recipesDir = option('--recipes') ? path.resolve(option('--recipes')) : path.join(ROOT, 'recipes');
const loaded = loadRecipes(recipesDir, foods);
const resolved = resolvePlan(source, loaded.recipes);
const problems = [...loaded.errors, ...resolved.errors];
if (problems.length) {
  resolved.warnings.forEach((w) => console.log(`WARNING ${w}`));
  problems.forEach((e) => console.log(`ERROR ${e}`));
  process.exit(1);
}
const outDir = path.join(option('--out') ? path.resolve(option('--out')) : path.join(ROOT, 'out'), slug);
fs.mkdirSync(outDir, { recursive: true });

// ---------- sizes ----------
const sizing = { ...defaults, ...(source.sizes || {}) };
const sizes = [];
for (const g of sizing.genders) {
  const gender = sizing.gender_labels[g];
  for (const w of sizing.weights) {
    const factor = Math.pow(w.mid / sizing.reference_weight, sizing.exponent) * (g === 'female' ? sizing.female_factor : 1);
    sizes.push({
      code: `${gender.code}-${w.code}`,
      file: `${slug}-${gender.file}-${w.code}kg`,
      gender: gender.sq,
      weight: `${w.label} kg`,
      factor,
    });
  }
}

// ---------- numbers ----------
const zero = () => ({ kcal: 0, p: 0, c: 0, f: 0 });
const add = (a, b) => ({ kcal: a.kcal + b.kcal, p: a.p + b.p, c: a.c + b.c, f: a.f + b.f });
const r = Math.round;

// Scales one ingredient and rounds it to what a cook can measure: whole or half pieces for
// foods with a unit, 5 g steps above 20 g, 1 g steps below.
function scaleIngredient(ing, factor) {
  const food = foods[ing.food];
  if (!food || !ing.grams) return { ...ing };
  const g = ing.grams * factor;
  if (food.unit) {
    const step = food.unit.step || 1;
    const count = Math.max(step, Math.round(g / food.unit.grams / step) * step);
    return { ...ing, grams: r(count * food.unit.grams), count };
  }
  return { ...ing, grams: g < 20 ? Math.max(1, r(g)) : r(g / 5) * 5 };
}

function build(size, errors, warnings) {
  const plan = JSON.parse(JSON.stringify(source));
  plan.size = size;
  plan.days.forEach((day) => {
    const target = day.kcal_target ? r(day.kcal_target * size.factor / 10) * 10 : null;
    const rawMeals = day.meals;
    // Rounding to pieces and 5 g steps drifts a day off its target, so nudge the day's own
    // factor towards the target a few times and keep the closest result.
    let best = null;
    let factor = size.factor;
    for (let attempt = 0; attempt < 8; attempt++) {
      const meals = rawMeals.map((meal) => scaleMeal(meal, factor, day.title, errors));
      const kcal = meals.reduce((s, m) => s + m.totals.kcal, 0);
      if (!best || (target && Math.abs(kcal - target) < Math.abs(best.kcal - target))) best = { meals, kcal };
      if (!target || Math.abs(kcal - target) / target < 0.02) break;
      factor *= 1 + (target / kcal - 1) * (attempt < 3 ? 1 : 0.5);
    }
    day.meals = best.meals;
    day.kcal_target = target;
    day.totals = day.meals.reduce((t, m) => add(t, m.totals), zero());
    if (target) {
      const off = (day.totals.kcal - target) / target;
      if (Math.abs(off) > 0.05) {
        warnings.push(`${size.code} ${day.title}: ${r(day.totals.kcal)} kcal is ${r(off * 100)}% off the ${target} kcal target`);
      }
    }
  });
  return plan;
}

function scaleMeal(source, factor, dayTitle, errors) {
  const meal = { ...source, totals: zero() };
  meal.ingredients = source.ingredients.map((raw) => {
    const ing = scaleIngredient(raw, factor);
    const food = foods[ing.food];
    const where = `${dayTitle} / ${meal.name}`;
    ing.totals = zero();
    if (!food) errors.add(`${where}: unknown food "${ing.food}" (add it to foods.json first)`);
    else if (food.kcal > 0 && !(ing.grams > 0)) errors.add(`${where}: "${ing.food}" needs grams`);
    else {
      const k = (ing.grams || 0) / 100;
      ing.totals = { kcal: food.kcal * k, p: food.p * k, c: food.c * k, f: food.f * k };
    }
    meal.totals = add(meal.totals, ing.totals);
    return ing;
  });
  if (meal.swap) meal.swap = meal.swap.replace(/(\d+) g\b/g, (_, x) => `${r((x * factor) / 5) * 5} g`);
  return meal;
}

// ---------- helpers ----------
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch]));
const n = (x) => r(x).toLocaleString('de-DE').replace(/\./g, ' ');
const half = (x) => (Number.isInteger(x) ? String(x) : `${Math.floor(x) || ''}½`);
const font = (f) => fs.readFileSync(path.join(FONTS, f)).toString('base64');
const mime = { '.jpg': 'jpeg', '.jpeg': 'jpeg', '.png': 'png', '.webp': 'webp' };

function photoStyle(meal, warnings) {
  if (!meal.photo) return '';
  const p = path.resolve(ROOT, meal.photo);
  if (!fs.existsSync(p)) {
    warnings.push(`Recipe "${meal.recipe}": photo ${meal.photo} not found, showing the placeholder`);
    return '';
  }
  const type = mime[path.extname(p).toLowerCase()] || 'jpeg';
  return ` style="background-image:linear-gradient(to top, rgba(0,0,0,.65), rgba(0,0,0,0) 70%),url(data:image/${type};base64,${fs.readFileSync(p).toString('base64')})"`;
}

const ICON = {
  breakfast: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
  snack: '<path d="M12 7c-3-3-8-1-8 4 0 5 4 10 8 10s8-5 8-10c0-5-5-7-8-4z"/><path d="M12 7c0-2 1-4 3-5"/>',
  lunch: '<circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="3"/>',
  dinner: '<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/>',
  preworkout: '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
};
const SLOT = { breakfast: 'Mëngjesi', snack: 'Vakt i ndërmjetëm', lunch: 'Dreka', dinner: 'Darka', preworkout: 'Para stërvitjes' };
const SWAP_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M7 7h11l-3-3M17 17H6l3 3"/></svg>';

// ---------- pages ----------
function render(plan, warnings) {
  const size = plan.size;
  const forWhom = `${size.gender} · ${size.weight}`;
  let pageNo = 1; // the cover is page 1 and carries no footer
  const footer = (left) => `<footer class="foot"><span>${esc(left)}</span><span>Optimum Lift · faqe ${++pageNo}</span></footer>`;

  function coverPage() {
    const kcals = plan.days.map((d) => d.kcal_target || r(d.totals.kcal));
    const range = Math.min(...kcals) === Math.max(...kcals) ? n(kcals[0]) : `${n(Math.min(...kcals))}–${n(Math.max(...kcals))}`;
    const avgP = plan.days.reduce((s, d) => s + d.totals.p, 0) / plan.days.length;
    return `<div class="page cover">
      <div class="brand"><b>OPTIMUM <span>LIFT</span></b><span>Plani i ushqimit</span></div>
      <div class="cover-main">
        <div class="for">Përgatitur për ty<b>${esc(forWhom)}</b></div>
        <small>${esc(plan.plan.tagline || '')}</small>
        <h1>${esc(plan.plan.name)}</h1>
        <p>${esc(plan.plan.for_whom || '')}</p>
      </div>
      <div class="cover-facts">
        <div><span>Kalori në ditë</span><b>${range}</b></div>
        <div><span>Proteina në ditë</span><b>~${r(avgP)} g</b></div>
        <div><span>Ditë në plan</span><b>${plan.days.length}</b></div>
        <div><span>Vakte në ditë</span><b>${plan.days[0].meals.length}</b></div>
      </div>
      <div class="cover-bar"></div>
    </div>`;
  }

  function howToPage() {
    const list = (items) => items.map((t) => `<li>${esc(t)}</li>`).join('');
    const who = size.gender === sizing.gender_labels.female.sq ? 'një grua' : 'një burrë';
    return `<div class="page plain">
      <header class="mini"><b>OPTIMUM <span>LIFT</span></b><span>${esc(plan.plan.name)} · ${esc(forWhom)}</span></header>
      <div class="content">
        <h1 class="h">Si ta përdorësh</h1>
        <p class="lead">Ky version është llogaritur për ${who} ${esc(size.weight)}: çdo porcion është përshtatur për peshën tënde.</p>
        <ol class="rules">${list(plan.how_to || [])}</ol>
        ${plan.adjust && plan.adjust.length ? `<h2 class="h2">Si ta përshtatësh</h2><ul class="adjust">${list(plan.adjust)}</ul>` : ''}
        <h2 class="h2">Si lexohet një ditë</h2>
        <div class="legend">
          <div><span class="chip p">P</span> Proteina</div>
          <div><span class="chip c">K</span> Karbohidrate</div>
          <div><span class="chip f">Y</span> Yndyrna</div>
          <div><span class="sw">${SWAP_ICON}</span> Zëvendësim me të njëjtat kalori</div>
          <div><span class="tick"></span> Shënoje kur e ha vaktin</div>
        </div>
        <p class="note">Gramët janë për ushqimin e papjekur, përveç kur shkruhet ndryshe. Vlerat ushqyese janë të përafërta, sipas tabelave zyrtare të ushqimeve. Ky plan nuk zëvendëson këshillën e mjekut: nëse je shtatzënë, ke diabet, sëmundje të veshkave ose çrregullime të të ngrënit, konsultohu fillimisht me mjekun.</p>
      </div>
      ${footer('')}
    </div>`;
  }

  function amount(ing) {
    const food = foods[ing.food];
    const parts = [];
    if (food?.unit && ing.count) parts.push(`${half(ing.count)} ${food.unit.name}`);
    else if (!ing.grams && ing.household) parts.push(ing.household);
    if (ing.grams) parts.push(`${ing.grams} g`);
    if (ing.note) parts.push(ing.note);
    return parts.join(' · ');
  }

  function mealCard(meal, i) {
    const t = meal.totals;
    const ings = meal.ingredients.map((ing) => `<li><span><b>${esc(ing.label || foods[ing.food]?.sq || ing.food)}</b></span><span>${esc(amount(ing))}</span></li>`).join('');
    const steps = (meal.steps || []).map((s) => `<li>${esc(s)}</li>`).join('');
    const style = photoStyle(meal, warnings);
    return `<article class="meal m${(i % 4) + 1}">
      <div class="photo${style ? ' has' : ''}"${style}>${style ? '' : '<span class="ph">Foto e pjatës</span>'}<div class="when"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${ICON[meal.slot] || ICON.lunch}</svg>${esc(SLOT[meal.slot] || meal.slot)}${meal.time ? ` · ${esc(meal.time)}` : ''}</div></div>
      <div class="body">
        <div class="name"><h2>${esc(meal.name)}</h2><span class="kc">${r(t.kcal)}<small>kcal</small></span></div>
        <div class="meta"><span class="chip p">P ${r(t.p)} g</span><span class="chip c">K ${r(t.c)} g</span><span class="chip f">Y ${r(t.f)} g</span>${meal.minutes ? `<span class="chip t">${meal.minutes} min</span>` : ''}</div>
        <h3>Përbërësit</h3><ul class="ing">${ings}</ul>
        ${steps ? `<h3>Përgatitja</h3><ol class="steps">${steps}</ol>` : ''}
        <div class="swap">${meal.swap ? `${SWAP_ICON}<span>${esc(meal.swap)}</span>` : ''}<span class="done"><i></i>E ngrëna</span></div>
      </div>
    </article>`;
  }

  function dayPage(day) {
    const t = day.totals;
    const share = (g, per) => r((g * per * 100) / t.kcal);
    const minutes = day.meals.reduce((s, m) => s + (m.minutes || 0), 0);
    const glasses = '<span></span>'.repeat(Math.round((plan.plan.water_litres || 3) / 0.375));
    return `<div class="page">
      <header class="head">
        <div class="brand"><b>OPTIMUM <span>LIFT</span></b><span>${esc(plan.plan.name)} · ${esc(forWhom)}</span></div>
        <div class="title">
          <h1>${day.week ? `<small>${esc(day.week)}</small>` : ''}${esc(day.title)}</h1>
          <div class="daytype"><strong>${esc(day.type || '')}</strong>${day.meals.length} vakte${minutes ? ` · rreth ${r(minutes / 5) * 5} min gatim` : ''}</div>
        </div>
      </header>
      <section class="totals">
        <div class="stat kcal"><div class="k">Kalori sot</div><div class="v">${n(t.kcal)}<em>kcal</em></div></div>
        <div class="stat"><div class="k">Proteina</div><div class="v">${r(t.p)}<em>g</em></div><div class="bar"><i style="width:${share(t.p, 4)}%;background:var(--p)"></i></div></div>
        <div class="stat"><div class="k">Karbohidrate</div><div class="v">${r(t.c)}<em>g</em></div><div class="bar"><i style="width:${share(t.c, 4)}%;background:var(--c)"></i></div></div>
        <div class="stat"><div class="k">Yndyrna</div><div class="v">${r(t.f)}<em>g</em></div><div class="bar"><i style="width:${share(t.f, 9)}%;background:var(--f)"></i></div></div>
        <div class="stat water"><div class="k">Ujë</div><div class="v">${plan.plan.water_litres || 3}<em>litra</em></div><div class="glasses">${glasses}</div></div>
      </section>
      <section class="meals">${day.meals.map(mealCard).join('')}</section>
      ${day.tip ? `<div class="tip"><b>Këshilla e ditës</b><span>${esc(day.tip)}</span></div>` : ''}
      ${footer('Vlerat janë të përafërta, sipas tabelave zyrtare të ushqimeve.')}
    </div>`;
  }

  function shoppingPages() {
    const weeks = {};
    plan.days.forEach((d) => {
      const w = d.week || 'Plani';
      weeks[w] = weeks[w] || {};
      d.meals.forEach((m) => m.ingredients.forEach((ing) => {
        const food = foods[ing.food];
        if (!food || food.shop === false || !ing.grams) return;
        weeks[w][ing.food] = (weeks[w][ing.food] || 0) + ing.grams;
      }));
    });
    return Object.entries(weeks).map(([week, items]) => {
      const days = plan.days.filter((d) => (d.week || 'Plani') === week).length;
      const cols = Object.entries(groups).map(([g, label]) => {
        const rows = Object.entries(items).filter(([k]) => foods[k].group === g)
          .sort((a, b) => foods[a[0]].sq.localeCompare(foods[b[0]].sq, 'sq'))
          .map(([k, grams]) => `<li><i></i><span>${esc(foods[k].sq)}</span><b>${grams >= 1000 ? `${(grams / 1000).toFixed(1).replace('.', ',')} kg` : `${r(grams / 10) * 10} g`}</b></li>`).join('');
        return rows ? `<section class="group"><h3>${esc(label)}</h3><ul>${rows}</ul></section>` : '';
      }).join('');
      return `<div class="page plain">
        <header class="mini"><b>OPTIMUM <span>LIFT</span></b><span>${esc(plan.plan.name)} · ${esc(forWhom)}</span></header>
        <div class="content">
          <h1 class="h">Lista e blerjeve</h1>
          <p class="lead">${esc(week)} · ${days} ditë. Sasitë janë për ushqimin e papjekur; bli pak më shumë perime e fruta për humbjet nga qërimi.</p>
          <div class="shop">${cols}</div>
        </div>
        ${footer('')}
      </div>`;
    }).join('');
  }

  return `<!doctype html><html lang="sq"><head><meta charset="utf-8"><title>${esc(plan.plan.name)} · Optimum Lift</title><style>${css}</style></head><body>
${coverPage()}
${howToPage()}
${plan.days.map(dayPage).join('\n')}
${shoppingPages()}
</body></html>`;
}

const css = fs.readFileSync(path.join(ROOT, 'style.css'), 'utf8')
  .replace('__ANTON__', font('anton-latin-400-normal.woff2'))
  .replace('__INTER__', font('inter-latin-wght-normal.woff2'));

// ---------- build every size, then report ----------
const errors = new Set();
const warnings = [...resolved.warnings];
if (!option('--recipes') && !require('./catalogue').indexIsCurrent()) warnings.push('recipes/INDEX.md is out of date: run node catalogue.js');
const chosen = only ? sizes.filter((s) => s.code === only) : sizes;
if (!chosen.length) {
  console.error(`No size "${only}". Sizes: ${sizes.map((s) => s.code).join(', ')}`);
  process.exit(1);
}
const reference = sizes.filter((s) => s.code.startsWith(sizing.gender_labels[sizing.genders[0]].code))
  .reduce((best, s) => (Math.abs(s.factor - 1) < Math.abs(best.factor - 1) ? s : best));
const built = chosen.map((size) => {
  const plan = build(size, errors, warnings);
  const html = render(plan, warnings);
  const htmlPath = path.join(outDir, `${size.file}.html`);
  if (!dump) fs.writeFileSync(htmlPath, html);
  return { size, plan, htmlPath };
});

console.log(`Plan: ${source.plan.name} (${source.days.length} days, written for ${sizing.reference_weight} kg; reference size ${reference.code})`);
console.log(`  size          ${source.days.map((d) => d.title.padStart(16)).join('')}`);
built.forEach(({ size, plan }) => {
  const cells = plan.days.map((d) => `${r(d.totals.kcal)} kcal P${r(d.totals.p)}`.padStart(16)).join('');
  console.log(`  ${size.code.padEnd(12)}  ${cells}`);
});
[...new Set(warnings)].forEach((w) => console.log(`WARNING ${w}`));
errors.forEach((e) => console.log(`ERROR ${e}`));
if (errors.size) process.exit(1);

// ---------- dump ----------
// The computed plan of each size as JSON, in a fixed shape: numbers rounded to 0.1 and keys in
// a fixed order, so two runs give the same bytes and two versions of a plan can be diffed.
if (dump) {
  const num = (x) => Math.round(x * 10) / 10;
  const totals = (t) => ({ kcal: num(t.kcal), p: num(t.p), c: num(t.c), f: num(t.f) });
  const pick = (o, keys) => Object.fromEntries(keys.filter((k) => o[k] !== undefined && o[k] !== '').map((k) => [k, o[k]]));
  built.forEach(({ size, plan }) => {
    const out = {
      plan: plan.plan.name,
      size: { code: size.code, gender: size.gender, weight: size.weight },
      days: plan.days.map((day) => ({
        title: day.title,
        kcal_target: day.kcal_target,
        totals: totals(day.totals),
        meals: day.meals.map((meal) => ({
          ...pick(meal, ['slot', 'time', 'name', 'minutes']),
          ingredients: meal.ingredients.map((ing) => pick(
            { ...ing, grams: ing.grams === undefined ? undefined : num(ing.grams) },
            ['food', 'grams', 'count', 'label', 'note', 'household'],
          )),
          steps: meal.steps || [],
          ...pick(meal, ['swap']),
          totals: totals(meal.totals),
        })),
      })),
    };
    fs.writeFileSync(path.join(outDir, `${size.file}.json`), `${JSON.stringify(out, null, 2)}\n`);
  });
  console.log(`Wrote ${built.length} dump${built.length === 1 ? '' : 's'} to ${rel(outDir)}/`);
  process.exit(0);
}

// ---------- PDFs ----------
(async () => {
  let chromium;
  try {
    ({ chromium } = require('playwright'));
  } catch {
    const { execSync } = require('child_process');
    ({ chromium } = require(path.join(execSync('npm root -g').toString().trim(), 'playwright')));
  }
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 794, height: 1123 }, deviceScaleFactor: 2 });
  for (const { size, htmlPath } of built) {
    await page.goto('file://' + htmlPath);
    await page.evaluate(() => document.fonts.ready);
    const overflow = await page.evaluate(() => [...document.querySelectorAll('.page')]
      .map((p, i) => (p.scrollHeight > p.clientHeight + 1 ? i + 1 : 0)).filter(Boolean));
    overflow.forEach((i) => console.log(`WARNING ${size.code} page ${i} is too full and gets cut off: shorten steps or ingredients`));
    await page.pdf({ path: path.join(outDir, `${size.file}.pdf`), format: 'A4', printBackground: true, preferCSSPageSize: true });
    if (wantPng && size === (only ? size : reference)) {
      const pages = await page.$$('.page');
      for (let i = 0; i < pages.length; i++) await pages[i].screenshot({ path: path.join(outDir, `${size.file}-${i + 1}.png`) });
    }
    fs.unlinkSync(htmlPath);
  }
  await browser.close();
  console.log(`Wrote ${built.length} PDF${built.length === 1 ? '' : 's'} to ${rel(outDir)}/`);
})();
