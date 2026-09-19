import { expect, test } from '@playwright/test';
import { Db } from '../_fixtures/db';
import { WPAdmin } from '../_page_objects/wp-admin';
import { RSVPBlock } from '../_page_objects/rsvp-block';
import { hideNoticesOn, hideWpFooterOn } from '../_fixtures/utils';

const db = new Db();

test.describe('Admin Removes RSVP', () => {
	test.beforeAll(async () => {
		await db.reset();
		await db.loadDump('rsvp-v2-baseline');
	});

	const EVENT_B_ID = 238;

	async function enterEditMode(page) {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 20000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.createForm).toBeVisible();
		return rsvp;
	}

	test.beforeEach(async ({ page }) => {
		const wpAdmin = new WPAdmin(page);
		await wpAdmin.loginAsAdmin();
		await wpAdmin.gotoEditEvent(EVENT_B_ID);
		await hideNoticesOn(page);
		await hideWpFooterOn(page);
		await wpAdmin.requireBlockEditor();
		await page.waitForTimeout(3000);
	});

	test('inactive block is visible on existing RSVP post', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 20000 });
		await expect(rsvp.addRsvpButton).toBeVisible();
	});

	test('block enters edit mode with create form', async ({ page }) => {
		const rsvp = await enterEditMode(page);
		await expect(rsvp.limitInput).toBeVisible();
		await expect(rsvp.limitHelperText).toBeVisible();
	});

	test('after entering edit mode, block shows the RSVP container', async ({ page }) => {
		const rsvp = await enterEditMode(page);
		// The block should render the edit container
		const container = page.locator('.tribe-editor__rsvp-container');
		await expect(container).toBeVisible();
	});

	test('screenshot: create form after entering edit mode', async ({ page }) => {
		const rsvp = await enterEditMode(page);
		await expect(rsvp.block).toHaveScreenshot();
	});
});
