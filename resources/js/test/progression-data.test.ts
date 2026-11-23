import { test } from 'node:test';
import assert from 'node:assert/strict';
import { fromApi } from '../progression-data.ts';

const state = { open_stage: 1, completed: [{ key: 'tech/a' }], stages: [{ number: 1, module: [] }] };

test('the embed state names the token holder, so the viewer shows the player and not the catalog', () => {
  const player = fromApi({ ...state, player: { name: 'Alex' } });

  assert.deepEqual(player.player, { name: 'Alex' });
});

test('a state without a player keeps the viewer anonymous', () => {
  assert.equal(fromApi(state).player, undefined);
});
