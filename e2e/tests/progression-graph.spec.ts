import { expect, test, type Page } from '@playwright/test';

// Progression graph viewer (spec 018 item 3) on the committed fixture: the 31 Jul 2026 tree and a demo state.
// Screenshots are attached to the report for a visual review; the assertions check behaviour only.

async function onlyMekanism(page: Page) {
  await page.locator('.arf-pg__chip', { hasText: /^(All|Tout)$/ }).click();
  await page.locator('.arf-pg__chip[data-graph="mekanism"]').click();
}

test.beforeEach(async ({ page }) => {
  await page.setViewportSize({ width: 1600, height: 1000 });
});

test('player view draws the whole tree with the stage header', async ({ page }, testInfo) => {
  await page.goto('/progression/E2E_Gold');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  await expect(page.locator('.arf-pg__node')).toHaveCount(194);
  await expect(page.locator('.arf-pg__widget').first()).toContainText('20 / 40');
  await expect(page.locator('.arf-pg__node.is-done').first()).toBeVisible();
  await testInfo.attach('player-view', { body: await page.screenshot(), contentType: 'image/png' });
});

test('clicking a milestone opens its detail panel and colours its links', async ({ page }, testInfo) => {
  await page.goto('/progression/E2E_Gold');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  await page.locator('.arf-pg__chip', { hasText: /^(All|Tout)$/ }).click();
  await page.locator('.arf-pg__chip[data-graph="mekanism"]').click();
  await page.locator('.arf-pg__node[data-key="mekanism/mekanism_advanced_tank"]').click();

  const panel = page.locator('.arf-pg__panel');
  await expect(panel).toBeVisible();
  await expect(panel.locator('h3')).toHaveText('Mekanism: Advanced Tank');
  await expect(page.locator('.arf-pg__edge.is-in[data-from="mekanism/mekanism_basic_tank"][data-to="mekanism/mekanism_advanced_tank"]')).toHaveCount(1);
  await expect(page.locator('.arf-pg__edge.is-out[data-from="mekanism/mekanism_advanced_tank"]')).toHaveCount(1);
  await testInfo.attach('selected', { body: await page.screenshot(), contentType: 'image/png' });
});

test('a graph chip hides that graph', async ({ page }) => {
  await page.goto('/progression');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  await page.locator('.arf-pg__chip[data-graph="mekanism"]').click();

  await expect(page.locator('.arf-pg__node[data-key^="mekanism/"]')).toHaveCount(0);
});

test('Ctrl+K opens the search palette and Enter opens the best milestone', async ({ page }) => {
  await page.goto('/progression');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  await page.keyboard.press('Control+k');
  await page.keyboard.type('digital miner');
  await page.keyboard.press('Enter');

  await expect(page.locator('.arf-pg__panel h3')).toHaveText('Mekanism: Do the mining for me !');
});

test('the palette ranks a milestone above the items it unlocks', async ({ page }) => {
  await page.goto('/progression');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  await page.locator('[data-progression-search]').click();
  await page.keyboard.type('adv tank');

  await expect(page.locator('.arf-pg__result').first()).toContainText('Mekanism: Advanced Tank');
});

test('hovering a milestone highlights where it comes from and where it leads', async ({ page }) => {
  await page.goto('/progression/E2E_Gold');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');
  await onlyMekanism(page);

  await page.locator('.arf-pg__node[data-key="mekanism/mekanism_advanced_tank"]').hover();

  await expect(page.locator('.arf-pg__node.is-upstream[data-key="mekanism/mekanism_basic_tank"]')).toHaveCount(1);
  await expect(page.locator('.arf-pg__node.is-downstream[data-key="mekanism/mekanism_elite_tank"]')).toHaveCount(1);
});

test('a later-stage requirement shows as a badge and its link only on hover', async ({ page }) => {
  await page.goto('/progression');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');
  await expect(page.locator('.arf-pg__edge--back')).toHaveCount(0);

  await page.locator('.arf-pg__node:has(.arf-pg__warn)').first().hover();

  await expect(page.locator('.arf-pg__edge--back').first()).toBeVisible();
});

test('the selected milestone is kept in the URL and reopened from it', async ({ page }) => {
  await page.goto('/progression');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');
  await onlyMekanism(page);
  await page.locator('.arf-pg__node[data-key="mekanism/mekanism_advanced_tank"]').click();
  await expect(page).toHaveURL(/#m=mekanism%2Fmekanism_advanced_tank$/);

  await page.reload();

  await expect(page.locator('.arf-pg__panel h3')).toHaveText('Mekanism: Advanced Tank');
});

test('the graph follows the site theme and its switch drives the site', async ({ browser }) => {
  const context = await browser.newContext({ colorScheme: 'light' });
  const page = await context.newPage();
  await page.goto('/progression');
  const root = page.locator('.arf-pg');
  await expect(root).toHaveAttribute('data-theme', 'light');

  await page.locator('.arf-pg__tool--theme').click();

  await expect(root).toHaveAttribute('data-theme', 'dark');
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await context.close();
});

test('player page lists the next milestones and the owner switches it to private', async ({ page }, testInfo) => {
  await page.goto('/_e2e/login/E2E_Gold');
  await page.goto('/progression/E2E_Gold');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  const next = page.locator('[data-progression-side] [data-next] li button');
  await expect(next.first()).toBeVisible();
  await next.first().click();
  await expect(page.locator('.arf-pg__panel')).toBeVisible();
  const privacy = page.locator('[data-privacy-switch]');
  await expect(privacy).toBeChecked();
  await testInfo.attach('player-progress-page', { body: await page.screenshot(), contentType: 'image/png' });

  const saved = page.waitForResponse((r) => r.url().includes('/visibility') && r.request().method() === 'PUT');
  await privacy.uncheck();

  expect((await saved).status()).toBe(200);
  await page.reload();
  await expect(page.locator('[data-privacy-switch]')).not.toBeChecked();

  const restored = page.waitForResponse((r) => r.url().includes('/visibility'));
  await page.locator('[data-privacy-switch]').check();
  expect((await restored).status()).toBe(200);
});

test('a visitor sees no privacy switch on another player page', async ({ page }) => {
  await page.goto('/progression/E2E_Silver');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  await expect(page.locator('[data-privacy-switch]')).toHaveCount(0);
});
