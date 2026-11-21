# Progression graph viewer

The one renderer of the progression graph (spec 018 item 3, decision D2 a): the website page, the
in-game WebGUI screen and the launcher all mount this component. No framework, about 19 kB of JS
and 12 kB of CSS once built.

## Input

- **Snapshot**: the spec 017 model (`progression-model.schema.json` in Arffornia/docs) plus a
  `layout` block (`arffornia.progression-layout/1`). The layout is computed once per snapshot by
  `src/layout.ts` (ELK layered, one partition per stage, Brandes-Koepf balanced, one input port per
  incoming link) and never by the viewer.
- **Player state** (optional): `{ done, open_stage, deliveries }`, the part of
  `GET /api/progressions/{id}/state` (spec 017) the viewer reads. Without it the viewer shows the
  catalog, with no done, available or locked state.
- **Icons**: a function from an item id to the URL of its 3D inventory render.

```ts
import { mountProgressionGraph } from './src/index.ts';

const handle = mountProgressionGraph(element, { snapshot, state, locale: 'fr', icon: (item) => urls[item] ?? null });
handle.focus('mekanism/mekanism_advanced_tank');
```

## What it draws

- One row per graph, stages as shared columns, the open stage highlighted.
- Done, available and locked milestones; links done in teal. Selecting a milestone colours its
  incoming links blue and its outgoing links orange, with a legend.
- Requirements pointing to a later stage are data errors: they stay out of the layout and are
  drawn as red dashed overlays.
- Header: progress of the open stage against its threshold, and the Space Elevator module deliveries.
- Graph filter chips, search (Enter focuses the first match), pan, wheel zoom, fit, sun/moon theme
  switch bottom right, detail panel on click, Escape to close.

## Commands

```bash
npm run typecheck                 # tsc on the package
npm test                          # node --test: state rules and layout invariants on the current tree
npm run build:progression-graph   # dist/progression-graph.js (IIFE, global ArfforniaProgressionGraph) + .css
node packages/progression-graph/scripts/build-fixture.ts <model-v1.json> <backup.json> <item_textures dir> public/progression-fixture
```

The fixture in `public/progression-fixture` is the 31 Jul 2026 tree converted by
`scripts/progression.py convert` in Arffornia/docs, with demo stage modules and a demo player
state. The website serves it at `/progression` when `PROGRESSION_VIEWER=fixture`, until the spec 017
snapshot and state endpoints exist.
