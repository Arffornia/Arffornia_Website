import { expect, test } from '@playwright/test';

// Progression graph viewer (spec 018 item 3) on the committed fixture: the 31 Jul 2026 tree and a demo state.
// Screenshots are attached to the report for a visual review; the assertions check behaviour only.

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
  await expect(page.locator('.arf-pg__edge.is-in')).toHaveCount(1);
  await expect(page.locator('.arf-pg__edge.is-out')).toHaveCount(1);
  await testInfo.attach('selected', { body: await page.screenshot(), contentType: 'image/png' });
});

test('a graph chip hides that graph', async ({ page }) => {
  await page.goto('/progression');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  await page.locator('.arf-pg__chip[data-graph="mekanism"]').click();

  await expect(page.locator('.arf-pg__node[data-key^="mekanism/"]')).toHaveCount(0);
});

test('search focuses the first matching milestone', async ({ page }) => {
  await page.goto('/progression');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  await page.locator('.arf-pg__search').fill('digital miner');
  await page.locator('.arf-pg__search').press('Enter');

  await expect(page.locator('.arf-pg__panel h3')).toHaveText('Mekanism: Do the mining for me !');
});

test('the theme switch toggles between dark and light', async ({ page }, testInfo) => {
  await page.goto('/progression');
  const root = page.locator('.arf-pg');
  await expect(root).toHaveAttribute('data-theme', 'dark');

  await page.locator('.arf-pg__tool--theme').click();

  await expect(root).toHaveAttribute('data-theme', 'light');
  await testInfo.attach('light', { body: await page.screenshot(), contentType: 'image/png' });
});
