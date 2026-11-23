import { test } from 'node:test';
import assert from 'node:assert/strict';
import { lineage, moduleProgress, nodeStates, pathTo, splitLinks, stageProgress, targetKey } from '../src/state.ts';
import { milestone, snapshot } from './fixtures.ts';

const tree = snapshot([milestone('a', 1), milestone('b', 1, ['a']), milestone('c', 2, ['b']), milestone('d', 1, ['c'])]);

test('a milestone whose requirements are done and whose stage is open is available', () => {
  const states = nodeStates(tree, { done: ['tech/a'], open_stage: 1 });

  assert.equal(states.get('tech/b'), 'available');
});

test('a milestone of a stage that is not open yet stays locked even with its requirements done', () => {
  const states = nodeStates(tree, { done: ['tech/a', 'tech/b'], open_stage: 1 });

  assert.equal(states.get('tech/c'), 'locked');
});

test('without a player every milestone is neutral', () => {
  const states = nodeStates(tree, null);

  assert.deepEqual([...new Set(states.values())], ['neutral']);
});

test('a requirement at a later stage is reported as backward, not laid out', () => {
  const links = splitLinks(tree);

  assert.deepEqual(links.backward, [{ from: 'tech/c', to: 'tech/d' }]);
});

test('a requirement in another graph is reported as cross-graph', () => {
  const two = snapshot([milestone('a', 1), milestone('x', 1, ['tech/a'], 'magic')], ['tech', 'magic']);

  assert.deepEqual(splitLinks(two).crossGraph, [{ from: 'tech/a', to: 'magic/x' }]);
});

test('the stage threshold rounds the needed milestones up', () => {
  const progress = stageProgress(tree, { done: ['tech/a', 'tech/b'], open_stage: 1 });

  assert.deepEqual({ done: progress?.done, total: progress?.total, needed: progress?.needed, met: progress?.thresholdMet }, { done: 2, total: 3, needed: 3, met: false });
});

test('module deliveries are capped at what the module asks', () => {
  const stage = { number: 1, name: { en: 'S1' }, threshold: 0.8, module: [{ item: 'minecraft:iron_ingot', count: 100 }] };

  const rows = moduleProgress(stage, { done: [], open_stage: 1, deliveries: [{ item: 'minecraft:iron_ingot', delivered: 250 }] });

  assert.deepEqual(rows, [{ item: 'minecraft:iron_ingot', need: 100, have: 100 }]);
});

test('lineage follows requirements upstream and dependents downstream, transitively', () => {
  const chain = snapshot([milestone('a', 1), milestone('b', 1, ['a']), milestone('c', 2, ['b']), milestone('x', 1)]);
  const l = lineage(chain);

  assert.deepEqual([[...l.upstream('tech/c')].sort(), [...l.downstream('tech/a')].sort()], [['tech/a', 'tech/b'], ['tech/b', 'tech/c']]);
});

test('the path to a milestone lists only what is not done, earliest first', () => {
  const chain = snapshot([milestone('a', 1), milestone('b', 1, ['a']), milestone('c', 2, ['b'])]);

  assert.deepEqual(pathTo(chain, { done: ['tech/a'], open_stage: 1 }, 'tech/c').map((m) => m.key), ['tech/b']);
});

test('a target that is a milestone of the snapshot is kept', () => {
  assert.equal(targetKey(tree, 'tech/b'), 'tech/b');
});

test('a target the snapshot no longer has is dropped, so nothing offers to fly to it', () => {
  assert.equal(targetKey(tree, 'tech/removed'), null);
});

test('no target gives no target', () => {
  assert.equal(targetKey(tree, null), null);
});
