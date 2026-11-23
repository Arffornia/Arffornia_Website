import { expect, test, type Page } from '@playwright/test';
import { readFileSync } from 'node:fs';

// In-game viewer page (spec 018 D2 a) on the fixture tree. The mod's CEF bridge (window.arfforniaQuery) is
// stubbed, and the token-protected state route answers the fixture's demo state in the API shape.

const demo = JSON.parse(readFileSync(new URL('../../public/progression-fixture/state-demo.json', import.meta.url), 'utf8')) as {
  done: string[];
  open_stage: number;
  deliveries: { item: string; delivered: number }[];
};

function apiState(target: string | null) {
  return {
    open_stage: demo.open_stage,
    target,
    completed: demo.done.map((key) => ({ key })),
    stages: [{ number: demo.open_stage, module: demo.deliveries.map((d) => ({ item: d.item, delivered: d.delivered })) }],
  };
}

async function stubGame(page: Page, initialTarget: string | null = null) {
  let target: string | null = initialTarget;
  const bearers: (string | undefined)[] = [];
  await page.route('**/api/embed/progression/state', async (route) => {
    bearers.push(route.request().headers()['authorization']);
    await route.fulfill({ json: apiState(target) });
  });
  await page.exposeFunction('__arfTarget', (milestone: string | null) => {
    target = milestone;
  });
  await page.addInitScript(() => {
    const w = window as unknown as Record<string, unknown>;
    w.__arfRequests = [];
    w.arfforniaQuery = (q: { request: string; onSuccess: (r: string) => void }) => {
      const request = JSON.parse(q.request) as { type: string; milestone?: string | null };
      (w.__arfRequests as unknown[]).push(request);
      if (request.type === 'session') q.onSuccess(JSON.stringify({ token: 'pe1.test.token', expires_at: Date.now() / 1000 + 900 }));
      if (request.type === 'target') {
        void (w.__arfTarget as (m: string | null) => Promise<void>)(request.milestone ?? null).then(() => q.onSuccess('{"ok":true}'));
      }
      return 1;
    };
  });
  return { bearers };
}

async function openMilestone(page: Page, key: string) {
  await page.locator('.arf-pg__chip', { hasText: /^(All|Tout)$/ }).click();
  await page.locator(`.arf-pg__chip[data-graph="${key.split('/')[0]}"]`).click();
  await page.locator(`.arf-pg__node[data-key="${key}"]`).click();
}

// Distance in px between the milestone tile and the centre of the free part of the map (the detail panel takes 380 px).
async function offCentre(page: Page, key: string): Promise<number> {
  const canvas = (await page.locator('.arf-pg__canvas').boundingBox())!;
  const tile = (await page.locator(`.arf-pg__node[data-key="${key}"] .arf-pg__tile`).boundingBox())!;
  const panelSpace = canvas.width > 900 ? 380 : 0;
  return Math.hypot(tile.x + tile.width / 2 - (canvas.x + (canvas.width - panelSpace) / 2), tile.y + tile.height / 2 - (canvas.y + canvas.height / 2));
}

test.beforeEach(async ({ page }) => {
  await page.setViewportSize({ width: 1600, height: 1000 });
});

test('the page reads the state with the token from the game and targets an available milestone', async ({ page }, testInfo) => {
  const { bearers } = await stubGame(page);
  await page.goto('/embed/progression?locale=en');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');
  await expect(page.locator('body > header')).toHaveCount(0);
  expect(bearers[0]).toBe('Bearer pe1.test.token');

  const available = await page.locator('.arf-pg__node.is-available').first().getAttribute('data-key');
  await openMilestone(page, available!);
  await page.locator('.embed-target', { hasText: 'Target this milestone' }).click();

  await expect(page.locator(`.arf-pg__node[data-key="${available}"]`)).toHaveClass(/is-mark-target/);
  await expect(page.locator('.embed-target')).toHaveText('Targeted: click to clear');
  expect(await page.evaluate(() => (window as unknown as { __arfRequests: unknown[] }).__arfRequests)).toContainEqual({ type: 'target', milestone: available });
  await testInfo.attach('targeted', { body: await page.screenshot(), contentType: 'image/png' });
});

test('a locked or done milestone cannot be targeted', async ({ page }) => {
  await stubGame(page);
  await page.goto('/embed/progression?locale=en');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  const locked = await page.locator('.arf-pg__node.is-locked').first().getAttribute('data-key');
  await openMilestone(page, locked!);
  await expect(page.locator('.embed-target')).toBeDisabled();
  await expect(page.locator('.embed-target')).toHaveText('Not available yet');

  await page.locator('.arf-pg__node.is-done').first().click();
  await expect(page.locator('.embed-target')).toHaveText('Already done');
});

test('outside the game the page shows the catalog and says where to open it', async ({ page }) => {
  await page.goto('/embed/progression?locale=en');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  await expect(page.locator('.arf-pg__notice')).toContainText('Open this page from the game');
  await expect(page.locator('.arf-pg__node.is-available')).toHaveCount(0);
});

test('the centre tool and the T key fly to the targeted milestone', async ({ page }) => {
  const targeted = 'mekanism/mekanism_advanced_tank';
  await stubGame(page, targeted);
  await page.goto('/embed/progression?locale=en');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');
  const node = page.locator(`.arf-pg__node[data-key="${targeted}"]`);
  await expect(node).toHaveClass(/is-mark-target/);
  await page.locator('.arf-pg__tool[aria-label="Fit to screen"]').click();

  await page.locator('.arf-pg__tool--target').click();

  await expect(page.locator('.arf-pg__panel h3')).toHaveText('Mekanism: Advanced Tank');
  await expect.poll(() => offCentre(page, targeted)).toBeLessThan(40);

  await page.keyboard.press('Escape');
  await page.locator('.arf-pg__tool[aria-label="Fit to screen"]').click();
  await page.keyboard.press('t');
  await expect(page.locator('.arf-pg__panel h3')).toHaveText('Mekanism: Advanced Tank');
  await expect.poll(() => offCentre(page, targeted)).toBeLessThan(40);
});

test('without a target there is no centre tool and T does nothing', async ({ page }) => {
  await stubGame(page);
  await page.goto('/embed/progression?locale=en');
  await expect(page.locator('#progression-root')).toHaveAttribute('data-ready', 'true');

  await page.keyboard.press('t');

  await expect(page.locator('.arf-pg__tool--target')).toHaveCount(0);
  await expect(page.locator('.arf-pg__panel')).toBeHidden();
});
