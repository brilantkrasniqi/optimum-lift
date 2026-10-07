// Recipes: one dish per file in recipes/<recipe-key>.json, written once and named by many plans.
//
// loadRecipes() reads and validates every Recipe; resolvePlan() turns a plan's meals, which only
// name a Recipe, into the full meal objects render.js scales and prints. Both collect every problem
// before returning, worded for the person who wrote the file.

const fs = require('fs');
const path = require('path');

const SLOTS = ['breakfast', 'snack', 'lunch', 'dinner', 'preworkout'];
const TAGS = ['vegetarian', 'pescatarian', 'dairy-free', 'gluten-free', 'meal-prep', 'balkan'];
const KEY = /^[a-z0-9]+(-[a-z0-9]+)*$/;
const RECIPE_PROPS = ['name', 'slots', 'minutes', 'tags', 'photo', 'ingredients', 'steps', 'swap'];
const INGREDIENT_PROPS = ['food', 'grams', 'note', 'label', 'household'];
const MEAL_PROPS = ['slot', 'time', 'recipe', 'portion', 'grams'];
const PORTION = { min: 0.5, max: 2 };

const isObject = (v) => v !== null && typeof v === 'object' && !Array.isArray(v);
const isText = (v) => typeof v === 'string' && v.trim() !== '';
const list = (items) => items.join(', ');

// Reads every recipes/*.json in dir. Returns { recipes, errors }: recipes maps each key to its
// Recipe, or to null when the Recipe has errors, so a plan naming it is not also told it is unknown.
function loadRecipes(dir, foods) {
  const recipes = new Map();
  const errors = [];
  if (!fs.existsSync(dir)) return { recipes, errors: [`recipes folder ${dir} not found`] };
  const files = fs.readdirSync(dir).filter((f) => f.endsWith('.json'))
    .sort((a, b) => (a.slice(0, -5) < b.slice(0, -5) ? -1 : 1));
  const names = new Map();
  for (const file of files) {
    const key = file.slice(0, -'.json'.length);
    const where = `Recipe "${key}"`;
    let recipe;
    recipes.set(key, null);
    try {
      recipe = JSON.parse(fs.readFileSync(path.join(dir, file), 'utf8'));
    } catch (e) {
      errors.push(`${where}: not valid JSON (${e.message})`);
      continue;
    }
    const before = errors.length;
    if (!KEY.test(key)) errors.push(`${where}: the file name must be lowercase ASCII words joined by hyphens, like "omelete-me-spinaq"`);
    if (!isObject(recipe)) {
      errors.push(`${where}: must be a JSON object`);
      continue;
    }
    validateRecipe(recipe, where, foods, errors);
    if (isText(recipe.name)) {
      if (names.has(recipe.name)) errors.push(`${where}: name "${recipe.name}" is already used by Recipe "${names.get(recipe.name)}"`);
      else names.set(recipe.name, key);
    }
    if (errors.length === before) recipes.set(key, recipe);
  }
  return { recipes, errors };
}

function validateRecipe(recipe, where, foods, errors) {
  Object.keys(recipe).filter((k) => !RECIPE_PROPS.includes(k)).forEach((k) => errors.push(`${where}: unknown property "${k}"`));
  if (!isText(recipe.name)) errors.push(`${where}: "name" is required`);
  if (!Array.isArray(recipe.slots) || !recipe.slots.length) errors.push(`${where}: "slots" needs at least one of ${list(SLOTS)}`);
  else recipe.slots.filter((s) => !SLOTS.includes(s)).forEach((s) => errors.push(`${where}: unknown slot "${s}" (use ${list(SLOTS)})`));
  if (!Number.isInteger(recipe.minutes) || recipe.minutes < 0 || recipe.minutes > 240) errors.push(`${where}: "minutes" must be a whole number from 0 to 240`);
  if (recipe.tags !== undefined) {
    if (!Array.isArray(recipe.tags)) errors.push(`${where}: "tags" must be a list`);
    else recipe.tags.filter((t) => !TAGS.includes(t)).forEach((t) => errors.push(`${where}: unknown tag "${t}" (use ${list(TAGS)})`));
  }
  if (recipe.photo !== undefined && typeof recipe.photo !== 'string') errors.push(`${where}: "photo" must be a path like "photos/<file>.jpg"`);
  else if (recipe.photo && !recipe.photo.startsWith('photos/')) errors.push(`${where}: photo "${recipe.photo}" must be under photos/`);
  if (!Array.isArray(recipe.steps) || recipe.steps.length < 1 || recipe.steps.length > 4 || !recipe.steps.every(isText)) {
    errors.push(`${where}: "steps" needs 1 to 4 short sentences`);
  }
  if (recipe.swap !== undefined && typeof recipe.swap !== 'string') errors.push(`${where}: "swap" must be text`);
  if (!Array.isArray(recipe.ingredients) || recipe.ingredients.length < 1 || recipe.ingredients.length > 10) {
    errors.push(`${where}: "ingredients" needs 1 to 10 entries`);
    return;
  }
  const seen = new Set();
  recipe.ingredients.forEach((ing, i) => {
    const at = `${where}, ingredient ${i + 1}`;
    if (!isObject(ing)) {
      errors.push(`${at}: must be an object with "food" and "grams"`);
      return;
    }
    Object.keys(ing).filter((k) => !INGREDIENT_PROPS.includes(k)).forEach((k) => errors.push(`${at}: unknown property "${k}"`));
    const food = foods[ing.food];
    if (!food) {
      errors.push(`${at}: unknown food "${ing.food}" (add it to foods.json first)`);
      return;
    }
    if (ing.grams !== undefined && !(typeof ing.grams === 'number' && ing.grams > 0)) errors.push(`${at}: "grams" must be a number above 0`);
    else if (food.kcal > 0 && ing.grams === undefined) errors.push(`${at}: "${ing.food}" needs grams`);
    // Spices may appear more than once, each line with its own label.
    if (food.kcal === 0 && !isText(ing.label)) errors.push(`${at}: "${ing.food}" needs a "label" saying which one`);
    const id = food.kcal === 0 ? `${ing.food}:${ing.label}` : ing.food;
    if (seen.has(id)) errors.push(`${at}: "${ing.food}"${food.kcal === 0 ? ` "${ing.label}"` : ''} is listed twice`);
    seen.add(id);
  });
}

// Scales the grams in a swap text ("Oriz → patate, 150 g") by a factor, to whole grams.
const scaleSwap = (text, factor) => text.replace(/(\d+) g\b/g, (_, x) => `${Math.round(x * factor)} g`);

// Turns one plan meal ({ slot, time, recipe, portion?, grams? }) into the meal render.js prints.
// Returns null and adds to errors when the meal cannot be resolved.
function resolveMeal(meal, where, recipes, errors, warnings) {
  if (!isObject(meal)) {
    errors.push(`${where}: a meal must be an object naming a recipe`);
    return null;
  }
  if (meal.ingredients !== undefined && meal.recipe === undefined) {
    errors.push(`${where}: "${meal.name || 'this meal'}" is written out inline: write this meal as a Recipe and name it`);
    return null;
  }
  const before = errors.length;
  Object.keys(meal).filter((k) => !MEAL_PROPS.includes(k)).forEach((k) => errors.push(`${where}: unknown property "${k}"`));
  if (!SLOTS.includes(meal.slot)) errors.push(`${where}: "slot" must be one of ${list(SLOTS)}`);
  if (typeof meal.time !== 'string' || !/^([01]\d|2[0-3]):[0-5]\d$/.test(meal.time)) errors.push(`${where}: "time" must be HH:MM`);
  const portion = meal.portion === undefined ? 1 : meal.portion;
  if (typeof portion !== 'number' || portion < PORTION.min || portion > PORTION.max) {
    errors.push(`${where}: portion ${JSON.stringify(meal.portion)} must be from ${PORTION.min} to ${PORTION.max}`);
  }
  const recipe = recipes.get(meal.recipe);
  if (!isText(meal.recipe)) errors.push(`${where}: "recipe" is required`);
  else if (!recipes.has(meal.recipe)) errors.push(`${where}: unknown recipe "${meal.recipe}"`);
  const grams = meal.grams === undefined ? {} : meal.grams;
  if (!isObject(grams)) errors.push(`${where}: "grams" must be an object like { "rice": 85 }`);
  else {
    Object.entries(grams).forEach(([food, g]) => {
      if (recipe && !recipe.ingredients.some((ing) => ing.food === food)) errors.push(`${where}: grams for "${food}", which Recipe "${meal.recipe}" does not contain`);
      else if (recipe && !recipe.ingredients.some((ing) => ing.food === food && ing.grams !== undefined)) errors.push(`${where}: "${food}" has no grams in Recipe "${meal.recipe}" to set`);
      if (typeof g !== 'number' || g < 0) errors.push(`${where}: grams for "${food}" must be 0 or more`);
    });
  }
  if (errors.length > before || !recipe) return null;

  if (SLOTS.includes(meal.slot) && !recipe.slots.includes(meal.slot)) {
    warnings.push(`${where}: Recipe "${meal.recipe}" is written for ${list(recipe.slots)}, not ${meal.slot}`);
  }
  const ingredients = recipe.ingredients
    .map((ing) => (ing.grams === undefined ? { ...ing } : { ...ing, grams: ing.grams * portion }))
    .map((ing) => (grams[ing.food] === undefined || ing.grams === undefined ? ing : { ...ing, grams: grams[ing.food] }))
    .filter((ing) => ing.grams !== 0);
  return {
    slot: meal.slot,
    time: meal.time,
    minutes: recipe.minutes,
    name: recipe.name,
    photo: recipe.photo || '',
    recipe: meal.recipe,
    ingredients,
    steps: recipe.steps,
    ...(recipe.swap ? { swap: portion === 1 ? recipe.swap : scaleSwap(recipe.swap, portion) } : {}),
  };
}

// Resolves every meal of a plan in place. Returns { errors, warnings }.
function resolvePlan(plan, recipes) {
  const errors = [];
  const warnings = [];
  (plan.days || []).forEach((day, d) => {
    (day.meals || []).forEach((meal, m) => {
      const where = `Day ${d + 1} ("${day.title}"), ${isObject(meal) && SLOTS.includes(meal.slot) ? meal.slot : `meal ${m + 1}`}`;
      day.meals[m] = resolveMeal(meal, where, recipes, errors, warnings);
    });
  });
  return { errors, warnings };
}

module.exports = { SLOTS, TAGS, KEY, loadRecipes, resolvePlan, scaleSwap };
