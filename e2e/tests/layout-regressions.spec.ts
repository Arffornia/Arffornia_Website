import { expect, test } from '@playwright/test';

// Layout regressions seen on staging on 21 Nov 2026, after the dark mode and the Montserrat web font.

for (const scheme of ['light', 'dark'] as const) {
  for (const path of ['/shop', '/vote']) {
    test(`${path} background image covers the whole viewport in ${scheme}`, async ({ browser }) => {
      const context = await browser.newContext({ colorScheme: scheme, viewport: { width: 1920, height: 1080 } });
      const page = await context.newPage();
      await page.goto(path);

      // The page image sits on <body>; it only fills the canvas while <html> has no background of its own.
      const html = await page.evaluate(() => {
        const style = getComputedStyle(document.documentElement);
        return { color: style.backgroundColor, image: style.backgroundImage };
      });
      expect(html).toEqual({ color: 'rgba(0, 0, 0, 0)', image: 'none' });
      await expect(page.locator('body')).not.toHaveCSS('background-image', 'none');
      await context.close();
    });
  }
}

test('shop item titles stay inside their cards', async ({ page }) => {
  await page.setViewportSize({ width: 1366, height: 768 });
  await page.goto('/shop');

  const overflowing = await page.locator('.item-container').evaluateAll((cards) =>
    cards
      .map((card) => {
        const title = card.querySelector('.item-title');
        const box = card.getBoundingClientRect();
        const text = title?.getBoundingClientRect();
        return text && (text.left < box.left - 1 || text.right > box.right + 1) ? title?.textContent : null;
      })
      .filter(Boolean),
  );
  expect(overflowing).toEqual([]);
});
