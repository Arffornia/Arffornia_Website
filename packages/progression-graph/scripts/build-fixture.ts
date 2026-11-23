// Builds the viewer fixture from the spec 017 current tree, until the website serves real snapshots.
// Usage: node packages/progression-graph/scripts/build-fixture.ts <model-v1.json> <backup.json> <item_textures dir> <out dir>
//   model-v1.json: `python3 scripts/progression.py convert current-tree-2026-07-31.json` in Arffornia/docs
//   backup.json:   the same backup, read only for the icon paths the old website resolved
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { computeLayout } from '../src/layout.ts';
import type { PlayerState, Snapshot } from '../src/types.ts';

const [modelPath, backupPath, texturesDir, outDir] = process.argv.slice(2);
if (!outDir) {
  console.error('usage: build-fixture.ts <model-v1.json> <backup.json> <item_textures dir> <out dir>');
  process.exit(2);
}

const model = JSON.parse(readFileSync(modelPath, 'utf8')) as Snapshot;
const backup = JSON.parse(readFileSync(backupPath, 'utf8'));

for (const m of model.milestones) for (const u of m.unlocks) delete u.recipe;

const DEMO_MODULE = [
  { item: 'minecraft:iron_ingot', count: 25000 },
  { item: 'minecraft:redstone', count: 12000 },
  { item: 'minecraft:copper_ingot', count: 8000 },
];
model.stages = model.stages.map((s) => ({ ...s, module: s.module.length ? s.module : DEMO_MODULE.map((d) => ({ ...d, count: d.count * s.number })) }));
model.source = `${model.source ?? 'current tree'} (viewer fixture: stage modules are demo data)`;
model.layout = await computeLayout(model);

const icons: Record<string, string> = {};
const PREFIX = '/images/item_textures/';
const addFromUrl = (item: string | undefined, url: string | undefined) => {
  if (!item || !url || icons[item]) return;
  const at = url.indexOf(PREFIX);
  if (at < 0) return;
  const rel = decodeURIComponent(url.slice(at + PREFIX.length));
  if (existsSync(join(texturesDir, rel))) icons[item] = rel;
};
for (const m of backup.milestones) {
  for (const u of m.unlocks ?? []) addFromUrl(u.item_id, u.image_url);
  for (const r of m.requirements ?? []) addFromUrl(r.item_id, r.image_url);
}
const titleCase = (s: string) => s.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
const wanted = new Set<string>([
  ...model.graphs.map((g) => g.icon_item ?? ''),
  ...model.stages.flatMap((s) => s.module.map((x) => x.item)),
  ...model.milestones.flatMap((m) => [...m.unlocks.map((u) => u.item), ...(m.validation?.items ?? []).map((i) => i.item)]),
]);
const ALIASES: Record<string, string> = { 'minecraft:redstone': 'minecraft/Redstone Dust_128x128.png' };
for (const item of wanted) {
  if (!item || icons[item] || item.startsWith('#')) continue;
  const [ns, path] = item.split(':');
  const candidates = [ALIASES[item], `${ns}/${titleCase(path)}_128x128.png`].filter(Boolean) as string[];
  const hit = candidates.find((c) => existsSync(join(texturesDir, c)));
  if (hit) icons[item] = hit;
}

// Every graph of the backup kept the old editor's default icon (grass block): show its first unlocked item instead.
for (const g of model.graphs) {
  if (g.icon_item && g.icon_item !== 'minecraft:grass_block') continue;
  const first = model.milestones
    .filter((m) => m.graph === g.key)
    .sort((a, b) => a.stage - b.stage || a.key.localeCompare(b.key))
    .flatMap((m) => m.unlocks.map((u) => u.item))
    .find((item) => icons[item]);
  if (first) g.icon_item = first;
}

const byKey = new Map(model.milestones.map((m) => [m.key, m]));
const done = new Set<string>();
const sorted = [...model.milestones].sort((a, b) => a.stage - b.stage || a.key.localeCompare(b.key));
for (let pass = 0; pass < 4; pass++) {
  for (const m of sorted) {
    if (m.stage > 3 || done.has(m.key)) continue;
    if (!m.requires.every((k) => !byKey.has(k) || done.has(k))) continue;
    if (m.stage === 3 && [...m.key].reduce((h, c) => (h * 31 + c.charCodeAt(0)) % 997, 7) % 2 === 0) continue;
    done.add(m.key);
  }
}
const state: PlayerState = {
  player: { name: 'Demo' },
  done: [...done].sort(),
  open_stage: 3,
  deliveries: [
    { item: 'minecraft:iron_ingot', delivered: 61500 },
    { item: 'minecraft:redstone', delivered: 36000 },
    { item: 'minecraft:copper_ingot', delivered: 9300 },
  ],
};

mkdirSync(outDir, { recursive: true });
writeFileSync(join(outDir, 'model.json'), JSON.stringify(model));
writeFileSync(join(outDir, 'icons.json'), JSON.stringify(icons, Object.keys(icons).sort(), 0));
writeFileSync(join(outDir, 'state-demo.json'), JSON.stringify(state, null, 1));
console.log(`${model.milestones.length} milestones, ${Object.keys(icons).length} icons, ${done.size} done in the demo state`);
