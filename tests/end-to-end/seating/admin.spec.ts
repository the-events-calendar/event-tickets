import { expect, test } from '@playwright/test';
import { Db } from '../_fixtures/db';
import { WPAdmin } from '../_page_objects/wp-admin';
import { hideNoticesOn, hideWpFooterOn } from '../_fixtures/utils';
import fs from 'node:fs';

const db = new Db();

test.beforeAll(async () => {
	await db.reset();
	await db.loadDump('seating');
});

test('default page, no maps, no layouts', async ({ page }) => {
	const wpAdmin = new WPAdmin(page);
	await wpAdmin.loginAsAdmin();
	await page.goto('/wp-admin/admin.php?page=tec-tickets-seating');
	await hideNoticesOn(page);
	await hideWpFooterOn(page);
	await expect(page).toHaveScreenshot();
});

test('maps seating page, no maps', async ({ page }) => {
	const wpAdmin = new WPAdmin(page);
	await wpAdmin.loginAsAdmin();
	await page.goto('/wp-admin/admin.php?page=tec-tickets-seating&tab=maps');
	await hideNoticesOn(page);
	await hideWpFooterOn(page);
	await expect(page).toHaveScreenshot();
});

test('layouts seating page, no layouts', async ({ page }) => {
	const wpAdmin = new WPAdmin(page);
	await wpAdmin.loginAsAdmin();
	await page.goto('/wp-admin/admin.php?page=tec-tickets-seating&tab=layouts');
	await hideNoticesOn(page);
	await hideWpFooterOn(page);
	await expect(page).toHaveScreenshot();
});
