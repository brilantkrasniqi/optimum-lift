/**
 * Builds the Exercise library that ships inside the Plans plugin, from the
 * curated source folder (see .scratch/exercise-library/spec.md).
 *
 *     node tools/build-exercise-library.mjs <source-folder>
 *
 * Reads <source-folder>/curated-exercises-with-instructions.json, images/ and
 * videos/, and writes:
 *
 * - plugins/optimum-lift-plans/data/exercises/exercises.json (format version 1)
 * - plugins/optimum-lift-plans/data/exercises/media/ (the stills and GIFs)
 * - .scratch/exercise-library/review.md (what a person checks before import)
 *
 * The source has no muscles or equipment. They are derived here from the
 * movement pattern and the name; anything that is a judgement call is flagged
 * in the review sheet. Corrections go in OVERRIDES, never in the output, and
 * the script is run again. The same source always gives byte-identical output.
 *
 * Every value is checked against the plugin's Choices.php before anything is
 * written, so the vocabulary is defined in one place.
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const PLUGIN = path.join(ROOT, 'plugins/optimum-lift-plans');
const OUT = path.join(PLUGIN, 'data/exercises');
const MEDIA = path.join(OUT, 'media');
const REVIEW = path.join(ROOT, '.scratch/exercise-library/review.md');
const SOURCE_JSON = 'curated-exercises-with-instructions.json';
const FORMAT_VERSION = 1;

/**
 * Patterns that settle the primary muscle on their own.
 */
const PATTERN_MUSCLE = {
    horizontal_push: 'chest',
    vertical_push: 'shoulders',
    chest_fly: 'chest',
    elbow_extension: 'triceps',
    horizontal_pull: 'back',
    vertical_pull: 'back',
    back_extension: 'back',
    scapular: 'back',
    shoulder_extension: 'back',
    rear_delt: 'shoulders',
    shoulder_abduction: 'shoulders',
    external_rotation: 'shoulders',
    elbow_flexion: 'biceps',
    wrist_forearm: 'forearms',
    squat: 'quads',
    knee_extension: 'quads',
    knee_flexion: 'hamstrings',
    calf_raise: 'calves',
    hip_extension: 'glutes',
    hip_abduction: 'glutes',
    core_flexion: 'core',
    core_anti_extension: 'core',
    core_lateral: 'core',
    core_rotation: 'core',
    anti_rotation: 'core',
};

/**
 * Patterns that mix muscles: the muscle is judged from the name, and flagged.
 */
function judgedMuscle(pattern, name) {
    switch (pattern) {
        case 'hinge':
            return /romanian|stiff|single.leg/.test(name) ? 'hamstrings' : 'glutes';
        case 'lunge':
            return /curtsey/.test(name) ? 'glutes' : 'quads';
        case 'plyometric':
            return /chest pass|push.up/.test(name) ? 'chest' : 'quads';
        case 'carry':
            return /overhead/.test(name) ? 'core' : 'full_body';
        case 'conditioning':
        case 'ballistic':
        case 'olympic_lift':
        case 'squat_to_press':
        case 'get_up':
        case 'mobility':
            return 'full_body';
        default:
            return null;
    }
}

/**
 * Equipment from the source name. Every rule that matches adds its value; no
 * match means bodyweight.
 */
const EQUIPMENT_RULES = [
    [/\btrap bar\b/, 'trap_bar'],
    [/\bbarbell\b|\blandmine\b|^(snatch pull|power clean|squat jerk)$/, 'barbell'],
    [/\bdumbbell\b/, 'dumbbell'],
    [/\bkettlebell\b/, 'kettlebell'],
    [/\bsmith\b/, 'smith_machine'],
    [/\blever\b|\bsled\b/, 'machine'],
    [/\bcable\b/, 'cable'],
    [/\bband\b|^monster walk$/, 'band'],
    [/\bbench\b|\bdumbbell fly\b|\bincline biceps\b|skull crusher|preacher|step-up/, 'bench'],
    [/pull.?up|chin-up|\bmuscle up\b|\bhanging\b/, 'pullup_bar'],
    [/^chest dip$|\btricep dips\b/, 'dip_bars'],
    [/\bring\b/, 'rings'],
    [/\bsuspended\b/, 'suspension_trainer'],
    [/\bmedicine ball\b/, 'medicine_ball'],
    [/\bexercise ball\b/, 'exercise_ball'],
    [/\bwheel\b/, 'ab_wheel'],
    [/battling ropes|rope climb|jump rope/, 'rope'],
    [/treadmill|elliptical|stationary bike|ergometer|^run \(equipment\)$/, 'cardio_machine'],
];

/**
 * Tidying applied to every name, in order, before sentence case.
 */
const NAME_TIDY = [
    [/\s*\(male\)|\s+male$/g, ''],
    [/\s+v\.\s*\d+$/, ''],
    [/\s*\(side pov\)/, ''],
    [/\bpush up\b/g, 'push-up'],
    [/\bpull up\b/g, 'pull-up'],
    [/\bmuscle up\b/g, 'muscle-up'],
    [/\bget up\b/g, 'get-up'],
    [/\b(single|one) (arm|leg)\b/g, '$1-$2'],
    [/\bclose grip\b/g, 'close-grip'],
    [/\bbent over\b/g, 'bent-over'],
    [/\bstiff leg\b/g, 'stiff-leg'],
    [/\bstraight arm\b/g, 'straight-arm'],
    [/\bbottoms up\b/g, 'bottoms-up'],
    [/\bpull through\b/g, 'pull-through'],
    [/\bt bar\b/g, 'T-bar'],
    // "Lever" and "sled" are the source's words for a machine.
    [/^lever /, 'machine '],
    [/^smith /, 'Smith machine '],
    [/^resistance band /, 'band '],
];

const PROPER_NOUNS = ['Arnold', 'Zottman', 'Pendlay', 'Romanian', 'Russian', 'Turkish', 'Pallof', 'Nordic', 'Y-raise', 'L-sit'];

const WEIGHTED = 'Needs a dip belt or a plate; no equipment choice covers added load.';

/**
 * Per-Exercise corrections, keyed by the source name. Any of: name, key,
 * primary_muscle, equipment (replaces the derived list), note (shown in the
 * review sheet). Every key here must match a source name.
 */
const OVERRIDES = {
    // Names the tidying cannot reach
    'run (equipment)': { name: 'Treadmill run' },
    'stationary bike run v. 3': { name: 'Stationary bike (fast)' },
    'stationary bike walk': { name: 'Stationary bike (easy)' },
    'walk elliptical cross trainer': { name: 'Elliptical cross-trainer' },
    'walking on incline treadmill': { name: 'Incline treadmill walk' },
    'wheel rollerout': { name: 'Ab wheel rollout' },
    'world greatest stretch': { name: "World's greatest stretch" },
    'jack jump (male)': { name: 'Jumping jack' },
    'kneeling plank tap shoulder (male)': { name: 'Kneeling plank shoulder tap' },
    'single leg squat (pistol) male': { name: 'Pistol squat' },
    'sled 45° leg press (side pov)': { name: 'Leg press (45°)' },
    'sled hack squat': { name: 'Machine hack squat' },
    'sled one leg calf press on leg press': { name: 'Single-leg calf press on leg press' },
    'cable bar lateral pulldown': { name: 'Cable lat pulldown' },
    'crunch floor': { name: 'Floor crunch' },
    'air bike': { name: 'Bicycle crunch', note: 'The source calls it "air bike", which reads as the cardio machine.' },
    'barbell full squat': { name: 'Barbell back squat' },
    'barbell standing close grip military press': { name: 'Barbell close-grip military press' },
    'barbell lying triceps extension skull crusher': { name: 'Barbell skull crusher' },
    'cable overhead triceps extension (rope attachment)': { name: 'Cable overhead triceps extension (rope)' },
    'cable pushdown': { name: 'Cable triceps pushdown' },
    'cable standing shoulder external rotation': { name: 'Cable shoulder external rotation' },
    'self assisted inverse leg curl (on floor)': { name: 'Self-assisted Nordic curl' },
    'inverse leg curl (bench support)': { name: 'Nordic curl (bench-supported)' },
    'low glute bridge on floor': { name: 'Glute bridge' },
    'single leg bridge with outstretched leg': { name: 'Single-leg glute bridge' },
    'monster walk': { name: 'Band monster walk' },
    'split squats': { name: 'Split squat' },
    'alternate heel touchers': { name: 'Alternating heel touches' },
    'bodyweight incline side plank': { name: 'Incline side plank' },
    'resistance band seated straight back row': { name: 'Band seated row' },
    'kettlebell alternating renegade row': { name: 'Kettlebell renegade row' },
    'medicine ball overhead slam': { name: 'Medicine ball slam' },
    'kettlebell bottoms up clean from the hang position': { name: 'Kettlebell bottoms-up hang clean' },
    'kettlebell turkish get up (squat style)': { name: 'Kettlebell Turkish get-up' },
    'band horizontal pallof press': { name: 'Band Pallof press' },
    'band assisted pull-up': { name: 'Band-assisted pull-up' },
    'dumbbell rear lunge': { name: 'Dumbbell reverse lunge' },
    'lunge with jump': { name: 'Jumping lunge' },
    'skater hops': { name: 'Skater hop' },
    'l-sit on floor': { name: 'L-sit' },
    'handstand': { name: 'Handstand hold' },
    'suspended abdominal fallout': { name: 'Suspension trainer fallout' },
    'standing calf raise (on a staircase)': { name: 'Standing calf raise on a step' },
    'ski ergometer': { name: 'Ski erg' },
    'cable pull through (with rope)': { name: 'Cable pull-through' },
    'lever lying two-one leg curl': { name: 'Machine lying leg curl (2 up, 1 down)' },
    'lever bicep curl': { name: 'Machine biceps curl' },
    'lever seated crunch (chest pad)': { name: 'Machine seated crunch' },

    // Muscle: the pattern says one thing, the Exercise is mostly another
    'close-grip push-up': { primary_muscle: 'triceps' },
    'barbell close-grip bench press': { primary_muscle: 'triceps' },
    'diamond push-up': { primary_muscle: 'triceps' },
    'chest dip': { primary_muscle: 'chest' },
    'ring dips': { name: 'Ring dip', primary_muscle: 'chest' },
    'weighted tricep dips': { name: 'Weighted triceps dip', primary_muscle: 'triceps', note: WEIGHTED },
    'weighted straight bar dip': {
        primary_muscle: 'chest',
        equipment: ['pullup_bar'],
        note: `Done on a single straight bar, not dip bars. ${WEIGHTED}`,
    },
    'band y-raise': { primary_muscle: 'shoulders' },

    // Equipment the name does not say
    'wrist rollerer': { name: 'Wrist roller', equipment: [], note: 'No equipment choice fits a wrist roller, so none is set.' },
    'farmers walk': { name: "Farmer's walk", equipment: ['dumbbell'], note: 'Dumbbells or kettlebells; the name says neither.' },
    'assisted pull-up': { name: 'Machine-assisted pull-up', equipment: ['machine'] },
    'assisted hanging knee raise': { equipment: ['machine'], note: "Probably a knee-raise station (captain's chair); check the GIF." },
    'hyperextension': { equipment: ['machine'], note: 'A hyperextension bench (Roman chair).' },
    'glute-ham raise': { equipment: ['machine'], note: 'A glute-ham developer (GHD).' },
    'inverted row': { note: 'Under a bar at hip height (rack or Smith machine) or a sturdy table.' },
    'weighted pull-up': { note: WEIGHTED },
    'weighted close grip chin-up on dip cage': { name: 'Weighted close-grip chin-up', note: WEIGHTED },
    'weighted front plank': { name: 'Weighted plank', note: 'Needs a plate on the back; no equipment choice covers it.' },
};

// ---------------------------------------------------------------------------

function fail(problems) {
    console.error(`Nothing written. ${problems.length} problem(s):`);
    for (const p of problems) console.error(`  - ${p}`);
    process.exit(1);
}

/**
 * The keys of each Choices list, read from the PHP source.
 */
function readChoices() {
    const php = fs.readFileSync(path.join(PLUGIN, 'src/Content/Choices.php'), 'utf8');
    const lists = {};

    for (const name of ['muscles', 'equipment', 'difficulty', 'patterns', 'settings']) {
        const block = php.match(new RegExp(`function ${name}\\(\\): array\\s*\\{\\s*return \\[([\\s\\S]*?)\\];`));
        if (!block) fail([`Choices::${name}() not found in Choices.php`]);
        lists[name] = [...block[1].matchAll(/'([a-z_]+)'\s*=>/g)].map((m) => m[1]);
    }

    return lists;
}

/**
 * Same rules as LibraryKeys::slug() in PHP.
 */
function slug(name) {
    const ascii = name.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/['’]/g, '');
    return ascii.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 96).replace(/-+$/, '');
}

function tidyName(source) {
    let name = source;
    for (const [pattern, replacement] of NAME_TIDY) name = name.replace(pattern, replacement);
    for (const noun of PROPER_NOUNS) name = name.replace(new RegExp(`\\b${noun}\\b`, 'gi'), noun);
    return name.charAt(0).toUpperCase() + name.slice(1);
}

function deriveEquipment(source) {
    const found = EQUIPMENT_RULES.filter(([pattern]) => pattern.test(source)).map(([, value]) => value);
    return found.length > 0 ? found : ['bodyweight'];
}

/**
 * Keep a list in the order Choices defines, without duplicates.
 */
const inOrder = (values, order) => order.filter((v) => values.includes(v));

function build(sourceDir, choices) {
    const raw = JSON.parse(fs.readFileSync(path.join(sourceDir, SOURCE_JSON), 'utf8'));
    const problems = [];
    const sourceNames = new Set(raw.map((e) => e.name));

    for (const name of Object.keys(OVERRIDES)) {
        if (!sourceNames.has(name)) problems.push(`OVERRIDES has "${name}", which is not a source name`);
    }

    const rows = raw.map((e) => {
        const o = OVERRIDES[e.name] ?? {};
        const flags = [];

        let muscle = PATTERN_MUSCLE[e.pattern] ?? null;
        if (muscle === null) {
            muscle = judgedMuscle(e.pattern, e.name);
            flags.push('muscle judged from the name');
        }
        if (o.primary_muscle !== undefined) {
            muscle = o.primary_muscle;
            flags.push('muscle set by hand');
        }

        let equipment = deriveEquipment(e.name);
        if (o.equipment !== undefined) {
            equipment = o.equipment;
            flags.push('equipment set by hand');
        }

        const name = o.name ?? tidyName(e.name);
        const key = o.key ?? slug(name);
        if (o.note) flags.push(o.note);

        const image = path.basename(e.image);
        const animation = path.basename(e.gif_url);

        const entry = {
            key,
            source_id: e.id,
            name,
            primary_muscle: muscle,
            secondary_muscles: [],
            equipment: inOrder(equipment, choices.equipment),
            difficulty: e.difficulty,
            pattern: e.pattern,
            settings: inOrder(e.settings, choices.settings),
            instructions: e.instructions.trim(),
            image: `media/${image}`,
            animation: `media/${animation}`,
        };

        // Validation: every value must be one the plugin accepts.
        const where = `${e.id} "${e.name}"`;
        if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(key) || key.length > 100) problems.push(`${where}: invalid key "${key}"`);
        if (!choices.muscles.includes(muscle)) problems.push(`${where}: unknown muscle "${muscle}"`);
        if (entry.equipment.length !== equipment.length) problems.push(`${where}: unknown equipment in ${JSON.stringify(equipment)}`);
        if (!choices.difficulty.includes(e.difficulty)) problems.push(`${where}: unknown difficulty "${e.difficulty}"`);
        if (!choices.patterns.includes(e.pattern)) problems.push(`${where}: unknown pattern "${e.pattern}"`);
        if (entry.settings.length !== e.settings.length || entry.settings.length === 0) problems.push(`${where}: bad settings ${JSON.stringify(e.settings)}`);
        if (entry.instructions === '') problems.push(`${where}: no instructions`);
        if (!fs.existsSync(path.join(sourceDir, 'images', image))) problems.push(`${where}: missing images/${image}`);
        if (!fs.existsSync(path.join(sourceDir, 'videos', animation))) problems.push(`${where}: missing videos/${animation}`);
        // The types the Exercise's image and animation fields accept.
        if (!/\.(jpe?g|png)$/i.test(image)) problems.push(`${where}: still "${image}" is not a JPEG or PNG`);
        if (!/\.(gif|webp)$/i.test(animation)) problems.push(`${where}: animation "${animation}" is not a GIF or WebP`);

        return { entry, source: e, flags, renamed: o.name !== undefined };
    });

    const seen = new Map();
    for (const { entry } of rows) {
        if (seen.has(entry.key)) problems.push(`key "${entry.key}" is used by both ${seen.get(entry.key)} and ${entry.source_id}; give one a name or key override`);
        seen.set(entry.key, entry.source_id);
    }

    if (problems.length > 0) fail(problems);

    return rows;
}

function writeIfChanged(file, content) {
    const buffer = Buffer.isBuffer(content) ? content : Buffer.from(content, 'utf8');
    if (fs.existsSync(file) && fs.readFileSync(file).equals(buffer)) return false;
    fs.mkdirSync(path.dirname(file), { recursive: true });
    fs.writeFileSync(file, buffer);
    return true;
}

function writeMedia(sourceDir, rows) {
    const wanted = new Map();
    for (const { entry } of rows) {
        wanted.set(path.basename(entry.image), path.join(sourceDir, 'images', path.basename(entry.image)));
        wanted.set(path.basename(entry.animation), path.join(sourceDir, 'videos', path.basename(entry.animation)));
    }

    fs.mkdirSync(MEDIA, { recursive: true });
    let copied = 0;
    for (const [file, from] of wanted) {
        if (writeIfChanged(path.join(MEDIA, file), fs.readFileSync(from))) copied++;
    }

    let removed = 0;
    for (const file of fs.readdirSync(MEDIA)) {
        if (!wanted.has(file)) {
            fs.rmSync(path.join(MEDIA, file));
            removed++;
        }
    }

    return { files: wanted.size, copied, removed };
}

function table(header, lines) {
    return [`| ${header.join(' | ')} |`, `| ${header.map(() => '---').join(' | ')} |`, ...lines.map((l) => `| ${l.join(' | ')} |`)].join('\n');
}

function count(values) {
    const tally = {};
    for (const v of values) tally[v] = (tally[v] ?? 0) + 1;
    return Object.entries(tally).sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0]));
}

function review(rows, choices) {
    const list = (values) => (values.length > 0 ? values.join(', ') : '—');
    const flagged = rows.filter((r) => r.flags.length > 0);
    const renamed = rows.filter((r) => r.renamed);
    const byPattern = choices.patterns.map((p) => [p, rows.filter((r) => r.entry.pattern === p)]).filter(([, r]) => r.length > 0);

    return `# Exercise library: review sheet

Generated by \`node tools/build-exercise-library.mjs <source-folder>\`. Do not edit
this file: corrections go in \`OVERRIDES\` in the script, then it is run again.

Check three things:

1. **Needs a look** below: every muscle or piece of equipment that was a judgement call.
2. **Renamed**: names changed beyond tidying.
3. Skim the full list for anything that reads wrong.

Reply with corrections in any form ("Farmer's walk should be forearms, kettlebell").

## How the values were made

- **Name:** sentence case; "(male)", "v. 3" and "(side pov)" removed; "push up" to "push-up" and similar hyphens; the source's "lever" and "sled" are "Machine"; "resistance band" is "Band"; proper nouns capitalised (Romanian, Arnold, Pallof, ...).
- **Key:** a slug of the name. It never changes once imported, so a rename after import keeps the old key.
- **Primary muscle:** from the movement pattern where it decides it (horizontal push is chest, elbow flexion is biceps, ...). Hinges, lunges, carries, plyometrics, conditioning, ballistic and Olympic lifts are judged from the name and flagged.
- **Equipment:** from words in the name (dumbbell, cable, band, bench, pull-up, ...); nothing found means bodyweight.
- **Secondary muscles:** left empty. **Instructions:** the source English, unchanged. The source's "reason" line is not imported.

## Totals

${rows.length} Exercises, ${flagged.length} flagged, ${renamed.length} renamed.

${table(['Primary muscle', 'Exercises'], count(rows.map((r) => r.entry.primary_muscle)))}

${table(['Equipment', 'Exercises'], count(rows.flatMap((r) => (r.entry.equipment.length > 0 ? r.entry.equipment : ['(none)']))))}

## Needs a look (${flagged.length})

${table(
    ['Name', 'Source name', 'Pattern', 'Muscle', 'Equipment', 'Why'],
    flagged.map((r) => [r.entry.name, r.source.name, r.entry.pattern, r.entry.primary_muscle, list(r.entry.equipment), r.flags.join('; ')])
)}

## Renamed (${renamed.length})

${table(['Source name', 'Name', 'Key'], renamed.map((r) => [r.source.name, r.entry.name, `\`${r.entry.key}\``]))}

## Full list, by movement pattern

${byPattern
    .map(([pattern, group]) => `### ${pattern} (${group.length})\n\n${table(
        ['Name', 'Key', 'Muscle', 'Equipment', 'Settings', 'Difficulty'],
        group.map((r) => [
            r.flags.length > 0 ? `${r.entry.name} ⚑` : r.entry.name,
            `\`${r.entry.key}\``,
            r.entry.primary_muscle,
            list(r.entry.equipment),
            list(r.entry.settings),
            r.entry.difficulty,
        ])
    )}`)
    .join('\n\n')}
`;
}

// ---------------------------------------------------------------------------

const sourceDir = process.argv[2];
if (!sourceDir || !fs.existsSync(path.join(sourceDir, SOURCE_JSON))) {
    console.error(`Usage: node tools/build-exercise-library.mjs <source-folder>\n(the folder holding ${SOURCE_JSON}, images/ and videos/)`);
    process.exit(1);
}

const choices = readChoices();
const rows = build(sourceDir, choices);
const sorted = [...rows].sort((a, b) => a.entry.key.localeCompare(b.entry.key));

const json = `${JSON.stringify({ version: FORMAT_VERSION, exercises: sorted.map((r) => r.entry) }, null, 2)}\n`;
const jsonChanged = writeIfChanged(path.join(OUT, 'exercises.json'), json);
const media = writeMedia(sourceDir, rows);
const reviewChanged = writeIfChanged(REVIEW, review(rows, choices));

console.log(`${rows.length} Exercises, ${rows.filter((r) => r.flags.length > 0).length} flagged for review.`);
console.log(`exercises.json ${jsonChanged ? 'written' : 'unchanged'}; media ${media.files} files (${media.copied} copied, ${media.removed} removed); review.md ${reviewChanged ? 'written' : 'unchanged'}.`);
