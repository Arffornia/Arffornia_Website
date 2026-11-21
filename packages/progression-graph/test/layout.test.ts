import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { computeLayout } from '../src/layout.ts';
import type { GraphLayout, Point, Snapshot } from '../src/types.ts';
import { milestone, snapshot } from './fixtures.ts';

const fixture = JSON.parse(readFileSync(new URL('../../../public/progression-fixture/model.json', import.meta.url), 'utf8')) as Snapshot & { layout: GraphLayout };

function boxes(layout: GraphLayout, graph: string) {
  return Object.entries(layout.graphs[graph].nodes).map(([key, p]) => ({ key, x0: p.x, y0: p.y, x1: p.x + layout.node.width, y1: p.y + layout.node.height }));
}

function segmentHitsBox(a: Point, b: Point, box: { x0: number; y0: number; x1: number; y1: number }): boolean {
  const eps = 0.5;
  if (a.y === b.y) return a.y > box.y0 + eps && a.y < box.y1 - eps && Math.max(a.x, b.x) > box.x0 + eps && Math.min(a.x, b.x) < box.x1 - eps;
  if (a.x === b.x) return a.x > box.x0 + eps && a.x < box.x1 - eps && Math.max(a.y, b.y) > box.y0 + eps && Math.min(a.y, b.y) < box.y1 - eps;
  return false;
}

test('every milestone of the current tree sits inside its stage column', () => {
  const misplaced = fixture.milestones.filter((m) => {
    const p = fixture.layout.graphs[m.graph].nodes[m.key];
    const col = fixture.layout.columns.find((c) => c.stage === m.stage)!;
    return p.x < col.x || p.x + fixture.layout.node.width > col.x + col.width;
  });

  assert.deepEqual(misplaced.map((m) => m.key), []);
});

test('no link of the current tree runs through a milestone it does not connect', () => {
  const crossings: string[] = [];
  for (const graph of fixture.graphs) {
    const lg = fixture.layout.graphs[graph.key];
    const nodes = boxes(fixture.layout, graph.key);
    for (const e of lg.edges) {
      for (let i = 1; i < e.points.length; i++) {
        for (const box of nodes) {
          if (box.key === e.from || box.key === e.to) continue;
          if (segmentHitsBox(e.points[i - 1], e.points[i], box)) crossings.push(`${e.from} -> ${e.to} through ${box.key}`);
        }
      }
    }
  }

  assert.deepEqual(crossings, []);
});

test('links of the current tree are orthogonal', () => {
  const diagonal = Object.values(fixture.layout.graphs).flatMap((g) =>
    g.edges.filter((e) => e.points.some((p, i) => i > 0 && p.x !== e.points[i - 1].x && p.y !== e.points[i - 1].y)),
  );

  assert.equal(diagonal.length, 0);
});

test('milestones of one graph never overlap', () => {
  const overlaps: string[] = [];
  for (const graph of fixture.graphs) {
    const nodes = boxes(fixture.layout, graph.key);
    for (let i = 0; i < nodes.length; i++)
      for (let j = i + 1; j < nodes.length; j++) {
        const a = nodes[i], b = nodes[j];
        if (a.x0 < b.x1 && b.x0 < a.x1 && a.y0 < b.y1 && b.y0 < a.y1) overlaps.push(`${a.key} / ${b.key}`);
      }
  }

  assert.deepEqual(overlaps, []);
});

test('the committed fixture layout is what the layout code produces', async () => {
  const { layout, ...model } = fixture;

  const fresh = await computeLayout(model as Snapshot);

  assert.deepEqual(fresh, layout);
});

test('an isolated milestone of a late stage stays in its own column', async () => {
  const tree = snapshot([milestone('a', 1), milestone('b', 2, ['a']), milestone('lonely', 3)]);

  const layout = await computeLayout(tree);

  const col = layout.columns.find((c) => c.stage === 3)!;
  const x = layout.graphs.tech.nodes['tech/lonely'].x;
  assert.ok(x >= col.x && x + layout.node.width <= col.x + col.width, `x=${x} not in [${col.x}, ${col.x + col.width}]`);
});
