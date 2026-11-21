import { expect, test } from '@playwright/test';

// Site theme: follows the system until the visitor picks one, then the choice sticks across pages.

test('the site follows a dark system theme by default', async ({ browser }) => {
  const context = await browser.newContext({ colorScheme: 'dark' });
  const page = await context.newPage();
  await page.goto('/');

  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await context.close();
});

test('the toggle switches the theme and the choice is kept on the next page', async ({ browser }) => {
  const context = await browser.newContext({ colorScheme: 'light' });
  const page = await context.newPage();
  await page.goto('/');
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');

  await page.locator('[data-theme-toggle]').click();
  await page.goto('/news');

  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(9, 7, 10)');
  await context.close();
});
