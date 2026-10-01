import { expect, test } from '@playwright/test';
import { Db } from '../_fixtures/db';
import { WPAdmin } from '../_page_objects/wp-admin';
import { RSVPBlock } from '../_page_objects/rsvp-block';
import { hideNoticesOn, hideWpFooterOn } from '../_fixtures/utils';

const db = new Db();

test.describe('Inactive Block State Display', () => {
	test.beforeAll(async () => {
		await db.reset();
		await db.loadDump('rsvp-v2-baseline');
	});

	const EVENT_B_ID = 238;

	test.beforeEach(async ({ page }) => {
		const wpAdmin = new WPAdmin(page);
		await wpAdmin.loginAsAdmin();
		await wpAdmin.gotoEditEvent(EVENT_B_ID);
		await hideNoticesOn(page);
		await hideWpFooterOn(page);
		await wpAdmin.requireBlockEditor();
		await page.waitForTimeout(3000);
	});

	test('RSVP block is visible', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 20000 });
	});

	test('Add RSVP button is visible in the block', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 20000 });
		await expect(rsvp.addRsvpButton).toBeVisible();
	});

	test('inactive block card has expected structure', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 20000 });
		await expect(rsvp.inactiveTemplate).toBeVisible();
	});

	test('clicking Add RSVP enters edit mode', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 20000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.createForm).toBeVisible();
	});

	test('create form has limit input with helper text', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 20000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.limitInput).toBeVisible();
		await expect(rsvp.limitHelperText).toBeVisible();
	});

	test('screenshot: inactive block state with RSVP ticket', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 20000 });
		await expect(rsvp.inactiveTemplate).toBeVisible();
		await expect(rsvp.block).toHaveScreenshot();
	});
});
