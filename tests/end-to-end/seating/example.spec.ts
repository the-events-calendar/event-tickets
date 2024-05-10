import { test, expect } from '@playwright/test';

test('visit wordpress site page', async ({ page }) => {
	await page.goto('/');
	await expect(page).toHaveScreenshot('some.png');
	await expect(page).toHaveURL('http://wordpress.test/foo');
});
