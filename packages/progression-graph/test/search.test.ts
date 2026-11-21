import { test } from 'node:test';
import assert from 'node:assert/strict';
import { fuzzyScore, rank } from '../src/search.ts';

test('letters typed in order match even with gaps', () => {
  assert.notEqual(fuzzyScore('advtank', 'Mekanism: Advanced Tank'), null);
});

test('letters out of order do not match', () => {
  assert.equal(fuzzyScore('knat', 'Tank'), null);
});

test('accents are ignored', () => {
  assert.notEqual(fuzzyScore('energie', 'Mekanism: Énergie avancée'), null);
});

test('a direct substring ranks above a scattered match', () => {
  const ranked = rank('tank', ['Steel tower and knife', 'Advanced Tank'], (s) => s);

  assert.equal(ranked[0].entry, 'Advanced Tank');
});

test('a match at a word start ranks above one inside a word', () => {
  const ranked = rank('ore', ['Explore the core', 'Ore processing'], (s) => s);

  assert.equal(ranked[0].entry, 'Ore processing');
});
