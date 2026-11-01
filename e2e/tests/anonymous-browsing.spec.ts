import { expect, test } from '@playwright/test';

// Journey 1 of the audit: anonymous browsing. Data comes from Database\Seeders\E2ESeeder.

test('home page shows the podium with the best players', async ({ page }) => {
  const response = await page.goto('/');
  expect(response?.status()).toBe(200);

  const podium = page.locator('.podium');
  await expect(podium.locator('.p1 .playerName')).toHaveText('E2E_Gold');
  await expect(podium.locator('.p2 .playerName')).toHaveText('E2E_Silver');
  await expect(podium.locator('.p3 .playerName')).toHaveText('E2E_Bronze');
});

test('podium links to the player profile', async ({ page }) => {
  await page.goto('/');
  await page.locator('.podium .p1 a').first().click();
  await expect(page).toHaveURL(/\/profile\/E2E_Gold$/);
});

test('news list shows the latest news', async ({ page }) => {
  await page.goto('/news');
  await expect(page.getByText('E2E news title').first()).toBeVisible();
});

test('rules, shop and stages pages render', async ({ page }) => {
  for (const path of ['/reglement', '/shop', '/stages', '/vote']) {
    const response = await page.goto(path);
    expect(response?.status(), path).toBe(200);
  }
});

test('profile by name and by uuid', async ({ page }) => {
  expect((await page.goto('/profile/E2E_Silver'))?.status()).toBe(200);
  expect((await page.goto('/profile/uuid/00000000000000000000000000000002'))?.status()).toBe(200);
});

test('login redirects to Microsoft with a state parameter', async ({ request }) => {
  const response = await request.get('/login', { maxRedirects: 0 });
  expect(response.status()).toBe(302);
  const location = new URL(response.headers()['location']);
  expect(location.host).toBe('login.live.com');
  expect(location.searchParams.get('client_id')).not.toBeNull();
  expect(location.searchParams.get('redirect_uri')).not.toBeNull();
  // A real per-session state is added by the OAuth hardening PR; until then it is a constant.
  expect(location.searchParams.get('state')).toBeTruthy();
});

test('admin panel is not reachable anonymously', async ({ request }) => {
  const response = await request.get('/admin', { maxRedirects: 0 });
  expect([302, 401, 403]).toContain(response.status());
});
