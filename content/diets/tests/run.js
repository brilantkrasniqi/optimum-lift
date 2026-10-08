// Checks the renderer against the fixtures: `node tests/run.js` (or `npm test`) from content/diets/.
// Needs no Playwright: both checks use --dump.

const fs = require('fs');
const os = require('os');
const path = require('path');
const { spawnSync } = require('child_process');

const ROOT = path.join(__dirname, '..');
const FIX = path.join(__dirname, 'fixtures');
const out = fs.mkdtempSync(path.join(os.tmpdir(), 'diets-test-'));
const run = (plan, recipes) => spawnSync(process.execPath,
  [path.join(ROOT, 'render.js'), path.join(FIX, plan), '--recipes', path.join(FIX, recipes), '--dump', '--out', out],
  { encoding: 'utf8' });
let failed = 0;
const check = (name, ok, detail) => {
  console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}`);
  if (!ok) {
    failed++;
    if (detail) console.log(detail);
  }
};

const okRun = run('plan-ok.json', 'recipes');
const dumped = path.join(out, 'plan-ok', 'plan-ok-mashkull-80kg.json');
check('plan-ok renders', okRun.status === 0, okRun.stdout + okRun.stderr);
check('plan-ok dump matches plan-ok.expected.json',
  fs.existsSync(dumped) && fs.readFileSync(dumped, 'utf8') === fs.readFileSync(path.join(FIX, 'plan-ok.expected.json'), 'utf8'));
check('a Recipe in the wrong slot is a WARNING', /^WARNING Day 2 \("Dita 2"\), dinner: /m.test(okRun.stdout), okRun.stdout);

const broken = run('plan-broken.json', 'recipes-broken');
check('plan-broken exits non-zero', broken.status !== 0);
const expected = fs.readFileSync(path.join(FIX, 'broken.expected.txt'), 'utf8');
check('plan-broken prints broken.expected.txt', broken.stdout === expected, `--- expected\n${expected}--- got\n${broken.stdout}`);

fs.rmSync(out, { recursive: true, force: true });
process.exit(failed ? 1 : 0);
