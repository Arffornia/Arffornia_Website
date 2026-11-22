import { expect, test, type Page } from '@playwright/test';

// Admin progression editor (spec 019 item 1): one test per action of the old /stages editor, run in order
// on one draft. The seed has stage 1 and no graph; the tests build a small tree and take it apart again.

test.describe.configure({ mode: 'serial' });

let page: Page;

async function revision(): Promise<number> {
  return Number(await page.locator('[data-revision]').first().getAttribute('data-revision'));
}

/** Waits until the graph is drawn for the next revision of the draft. */
async function saved(before: number) {
  await expect(page.locator('.pe-canvas')).toHaveAttribute('data-revision', String(before + 1));
}

async function mode(name: string) {
  await page.locator(`button[data-mode="${name}"]`).click();
}

async function nodeCenter(key: string) {
  const box = (await page.locator(`.arf-pg__node[data-key="${key}"] .arf-pg__tile`).boundingBox())!;
  return { x: box.x + box.width / 2, y: box.y + box.height / 2 };
}

async function openMilestone(key: string) {
  await mode('view');
  await page.locator(`.arf-pg__node[data-key="${key}"]`).click();
  await expect(page.locator('.pe-panel')).toHaveAttribute('data-view', 'milestone');
}

test.beforeAll(async ({ browser }) => {
  page = await browser.newPage({ viewport: { width: 1600, height: 1000 } });
  page.on('dialog', (dialog) => dialog.accept());
  await page.goto('/_e2e/login/E2E_Admin');
  await page.goto('/admin/progression');
  await expect(page.locator('#progression-editor')).toHaveAttribute('data-ready', 'true');
});

test.afterAll(async () => {
  await page.close();
});

test('a player cannot open the editor', async ({ browser }) => {
  const other = await browser.newPage();
  await other.goto('/_e2e/login/E2E_Gold');
  const response = await other.goto('/admin/progression');
  expect(response?.status()).toBe(403);
  await other.close();
});

test('add a graph', async () => {
  const before = await revision();
  await page.locator('[data-action="add-graph"]').click();
  const form = page.locator('form[data-form="graph"]');
  await form.locator('[name="name_en"]').fill('Create Mod');
  await form.locator('[name="branch"]').selectOption('tech');
  await form.locator('button[type="submit"]').click();

  await saved(before);
  await expect(page.locator('[data-graph-row="create_mod"]')).toBeVisible();
});

test('edit a graph', async () => {
  const before = await revision();
  await page.locator('[data-edit-graph="create_mod"]').click();
  const form = page.locator('form[data-form="graph"]');
  await form.locator('[name="name_en"]').fill('Create');
  await form.locator('[name="mod_id"]').fill('create');
  await form.locator('button[type="submit"]').click();

  await saved(before);
  await expect(page.locator('[data-graph="create_mod"]')).toContainText('Create (0)');
});

test('add a stage', async () => {
  const before = await revision();
  await page.locator('[data-action="add-stage"]').click();
  const form = page.locator('form[data-form="stage"]');
  await form.locator('[name="name_en"]').fill('Steam age');
  await form.locator('[name="threshold"]').fill('0.75');
  await form.locator('button[type="submit"]').click();

  await saved(before);
  await expect(page.locator('[data-stage-row="2"]')).toContainText('Steam age');
});

test('edit a stage', async () => {
  const before = await revision();
  await page.locator('[data-edit-stage="2"]').click();
  await page.locator('form[data-form="stage"] [name="description_en"]').fill('Boilers and pipes');
  await page.locator('form[data-form="stage"] button[type="submit"]').click();

  await saved(before);
  await page.locator('[data-edit-stage="2"]').click();
  await expect(page.locator('form[data-form="stage"] [name="description_en"]')).toHaveValue('Boilers and pipes');
});

test('add milestones in add mode', async () => {
  for (const [title, stage] of [['Steel', '1'], ['Steel casing', '2']]) {
    const before = await revision();
    await mode('add');
    await page.locator('.arf-pg__canvas').click({ position: { x: 400, y: 14 } });
    const form = page.locator('form[data-form="new-milestone"]');
    await form.locator('[name="title_en"]').fill(title);
    await form.locator('[name="graph"]').selectOption('create_mod');
    await form.locator('[name="stage"]').selectOption(stage);
    await form.locator('button[type="submit"]').click();
    await saved(before);
  }

  await expect(page.locator('.arf-pg__node[data-key="create_mod/steel"]')).toBeVisible();
  await expect(page.locator('.arf-pg__node[data-key="create_mod/steel_casing"]')).toBeVisible();
});

test('edit a milestone in the side panel', async () => {
  await openMilestone('create_mod/steel');
  const before = await revision();
  const form = page.locator('form[data-form="milestone"]');
  await form.locator('[name="title_fr"]').fill('Acier');
  await form.locator('[name="description_en"]').fill('Make steel.');
  await form.locator('button[type="submit"]').click();

  await saved(before);
  await expect(page.locator('form[data-form="milestone"] [name="title_fr"]')).toHaveValue('Acier');
});

test('link two milestones in link mode', async () => {
  const before = await revision();
  await mode('link');
  await page.locator('.arf-pg__node[data-key="create_mod/steel"]').click();
  await page.locator('.arf-pg__node[data-key="create_mod/steel_casing"]').click();

  await saved(before);
  await expect(page.locator('.arf-pg__edge[data-from="create_mod/steel"][data-to="create_mod/steel_casing"]')).toHaveCount(1);
});

test('unlink by clicking the link in unlink mode', async () => {
  const before = await revision();
  await mode('unlink');
  const point = await page.locator('.arf-pg__edge-hit[data-from="create_mod/steel"]').evaluate((path: SVGPathElement) => {
    const p = path.getPointAtLength(path.getTotalLength() / 2);
    const m = path.getScreenCTM()!;
    return { x: p.x * m.a + p.y * m.c + m.e, y: p.x * m.b + p.y * m.d + m.f };
  });
  await page.mouse.click(point.x, point.y);

  await saved(before);
  await expect(page.locator('.arf-pg__edge[data-from="create_mod/steel"]')).toHaveCount(0);
});

test('move a milestone to another stage by dragging it', async () => {
  const before = await revision();
  await mode('move');
  const from = await nodeCenter('create_mod/steel');
  const column = (await page.locator('.arf-pg__col').nth(1).boundingBox())!;
  await page.mouse.move(from.x, from.y);
  await page.mouse.down();
  await page.mouse.move(from.x + 20, from.y, { steps: 2 });
  await page.mouse.move(column.x + column.width / 2, from.y, { steps: 8 });
  await page.mouse.up();

  await saved(before);
  await openMilestone('create_mod/steel');
  await expect(page.locator('form[data-form="milestone"] [name="stage"]')).toHaveValue('2');
});

test('add, edit and delete an unlock with its recipe', async () => {
  await openMilestone('create_mod/steel_casing');
  let before = await revision();
  await page.locator('[data-action="add-unlock"]').click();
  let form = page.locator('form[data-form="unlock"]');
  await form.locator('[name="item"]').fill('create:andesite_casing');
  await form.locator('[name="gate_mode"]').selectOption('machine');
  await form.locator('[name="recipes_to_ban"]').fill('create:andesite_casing');
  await form.locator('[name="has_recipe"]').check();
  await form.locator('[name="ingredients"]').fill('minecraft:andesite 2\n#c:planks 1');
  await form.locator('[name="result"]').fill('create:andesite_casing 1');
  await form.locator('[name="energy"]').fill('200');
  await form.locator('button[type="submit"]').click();
  await saved(before);
  await expect(page.locator('[data-list="unlocks"] [data-item="create:andesite_casing"]')).toContainText('machine');

  before = await revision();
  await page.locator('[data-edit-unlock="create:andesite_casing"]').click();
  form = page.locator('form[data-form="unlock"]');
  await expect(form.locator('[name="ingredients"]')).toHaveValue('minecraft:andesite 2\n#c:planks 1');
  await form.locator('[name="gate_mode"]').selectOption('crafting');
  await form.locator('[name="has_recipe"]').uncheck();
  await form.locator('button[type="submit"]').click();
  await saved(before);
  await expect(page.locator('[data-list="unlocks"] [data-item="create:andesite_casing"]')).toContainText('crafting');

  before = await revision();
  await page.locator('[data-delete-unlock="create:andesite_casing"]').click();
  await saved(before);
  await expect(page.locator('[data-list="unlocks"] [data-item]')).toHaveCount(0);
});

test('add, edit and delete a requirement', async () => {
  await openMilestone('create_mod/steel_casing');
  let before = await revision();
  await page.locator('[data-action="add-requirement"]').click();
  let form = page.locator('form[data-form="requirement"]');
  await form.locator('[name="item"]').fill('minecraft:iron_ingot');
  await form.locator('[name="count"]').fill('8');
  await form.locator('button[type="submit"]').click();
  await saved(before);
  await expect(page.locator('[data-list="requirements"] [data-item="minecraft:iron_ingot"]')).toContainText('× 8');

  before = await revision();
  await page.locator('[data-edit-requirement="minecraft:iron_ingot"]').click();
  form = page.locator('form[data-form="requirement"]');
  await form.locator('[name="count"]').fill('16');
  await form.locator('button[type="submit"]').click();
  await saved(before);
  await expect(page.locator('[data-list="requirements"] [data-item="minecraft:iron_ingot"]')).toContainText('× 16');

  before = await revision();
  await page.locator('[data-delete-requirement="minecraft:iron_ingot"]').click();
  await saved(before);
  await expect(page.locator('[data-list="requirements"] [data-item]')).toHaveCount(0);
});

test('pan, zoom and fit the view', async () => {
  await mode('view');
  const world = page.locator('.arf-pg__world');
  const start = await world.getAttribute('transform');
  const canvas = (await page.locator('.arf-pg__canvas').boundingBox())!;
  await page.mouse.move(canvas.x + 40, canvas.y + 40);
  await page.mouse.down();
  await page.mouse.move(canvas.x + 140, canvas.y + 90, { steps: 5 });
  await page.mouse.up();
  const panned = await world.getAttribute('transform');
  expect(panned).not.toBe(start);

  await page.mouse.wheel(0, -400);
  await expect(world).not.toHaveAttribute('transform', panned!);
  await page.locator('[data-action="fit"]').click();
  await expect(world).toHaveAttribute('transform', /scale/);
});

test('export the draft as JSON and import it back', async () => {
  const download = page.waitForEvent('download');
  await page.locator('[data-action="export"]').click();
  const file = await download;
  const document = JSON.parse(await (await file.createReadStream()).toArray().then((chunks) => Buffer.concat(chunks).toString('utf8')));
  expect(document.graphs.map((g: { key: string }) => g.key)).toContain('create_mod');

  const before = await revision();
  document.milestones.find((m: { key: string }) => m.key === 'create_mod/steel').title.en = 'Imported steel';
  await page.locator('[data-import]').setInputFiles({ name: 'draft.json', mimeType: 'application/json', buffer: Buffer.from(JSON.stringify(document)) });
  await saved(before);
  await expect(page.locator('.arf-pg__node[data-key="create_mod/steel"]')).toHaveAttribute('aria-label', 'Imported steel');
});

test('delete milestones in delete mode', async () => {
  for (const key of ['create_mod/steel', 'create_mod/steel_casing']) {
    const before = await revision();
    await mode('delete');
    await page.locator(`.arf-pg__node[data-key="${key}"]`).click();
    await saved(before);
  }
  await expect(page.locator('.arf-pg__node[data-key^="create_mod/"]')).toHaveCount(0);
});

test('delete a stage and a graph', async () => {
  let before = await revision();
  await page.locator('[data-delete-stage="2"]').click();
  await saved(before);
  await expect(page.locator('[data-stage-row="2"]')).toHaveCount(0);

  before = await revision();
  await page.locator('[data-delete-graph="create_mod"]').click();
  await saved(before);
  await expect(page.locator('[data-graph-row="create_mod"]')).toHaveCount(0);
});

test('undo and redo with the buttons and the keyboard, then read the history', async () => {
  let before = await revision();
  await page.locator('[data-action="undo"]').click();
  await saved(before);
  await expect(page.locator('[data-graph-row="create_mod"]')).toBeVisible();

  before = await revision();
  await page.locator('[data-action="redo"]').click();
  await saved(before);
  await expect(page.locator('[data-graph-row="create_mod"]')).toHaveCount(0);
  await expect(page.locator('[data-action="redo"]')).toBeDisabled();

  before = await revision();
  await page.locator('body').click({ position: { x: 5, y: 5 } });
  await page.keyboard.press('Control+z');
  await saved(before);
  await expect(page.locator('[data-graph-row="create_mod"]')).toBeVisible();
  before = await revision();
  await page.keyboard.press('Control+Shift+z');
  await saved(before);
  await expect(page.locator('[data-graph-row="create_mod"]')).toHaveCount(0);

  await page.locator('[data-action="history"]').click();
  const log = page.locator('[data-list="history"]');
  await expect(log.locator('li[data-type="redo"]')).toHaveCount(2);
  await expect(log.locator('li[data-type="edit"]').first()).toContainText('Graph deleted');
  await expect(log.locator('li[data-type="open"]')).toContainText('E2E_Admin');
});
