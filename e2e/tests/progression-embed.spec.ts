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

async function stubGame(page: Page) {
  let target: string | null = null;
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
