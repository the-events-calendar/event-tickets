// @ts-check
const { test, expect } = require('@playwright/test');
const { Db } = require('./_fixtures/db');
const { WPConfig } = require('./_fixtures/wp-config');

test.beforeAll(async () => {
	const db = new Db();
	await db.reset();
	await db.loadDump('dump');
	const wpConfig = new WPConfig();
	await wpConfig.deleteOrContinue('TEC_FLEXIBLE_TICKETS_DISABLED');
});

test('has title', async ({ page }) => {
	await page.goto('http://wordpress.test/');

	// Expect a title "to contain" a substring.
	await expect(page).toHaveTitle(/Playwright/);
});
