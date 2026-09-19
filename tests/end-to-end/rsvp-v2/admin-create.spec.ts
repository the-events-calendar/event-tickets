import { expect, test } from '@playwright/test';
import { Db } from '../_fixtures/db';
import { WPAdmin } from '../_page_objects/wp-admin';
import { RSVPBlock } from '../_page_objects/rsvp-block';
import { hideNoticesOn, hideWpFooterOn } from '../_fixtures/utils';

const db = new Db();

test.describe('Admin Enables RSVP', () => {
	test.beforeAll(async () => {
		await db.reset();
		await db.loadDump('rsvp-v2-baseline');
	});

	test.beforeEach(async ({ page }) => {
		const wpAdmin = new WPAdmin(page);
		await wpAdmin.loginAsAdmin();
		await wpAdmin.gotoCreateNewPostTypePage('post');
		await hideNoticesOn(page);
		await hideWpFooterOn(page);
		await wpAdmin.requireBlockEditor();
		await wpAdmin.insertBlock('tribe/rsvp');
	});

	test('screenshot: inactive block state before enabling', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await expect(page.locator('.tribe-editor__inactive-block--rsvp')).toBeVisible();
		await expect(rsvp.block).toHaveScreenshot();
	});

	test('inactive block shows Add RSVP button', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await expect(rsvp.addRsvpButton).toBeVisible();
	});

	test('clicking Add RSVP opens create form', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.createForm).toBeVisible();
	});

	test('create form shows limit field with helper text', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.limitInput).toBeVisible();
		await expect(rsvp.limitHelperText).toBeVisible();
	});

	test('create form has date pickers with values', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		// Date/time inputs may use DayPickerInput or plain inputs
		const dateInputs = rsvp.createForm.locator('input[type="text"]');
		const count = await dateInputs.count();
		expect(count).toBeGreaterThanOrEqual(2);
		const firstVal = await dateInputs.first().inputValue();
		expect(firstVal).toBeTruthy();
	});

	test('can save RSVP with a numeric limit', async ({ page }) => {
		const wpAdmin = new WPAdmin(page);
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await rsvp.limitInput.fill('100');
		// The V2 block autosaves; wait for the saved summary to appear.
		// Uses a longer timeout to account for debounced autosave.
		await expect(rsvp.savedSummary).toBeVisible({ timeout: 30000 });
	});

	test('can save RSVP with no limit (unlimited)', async ({ page }) => {
		const wpAdmin = new WPAdmin(page);
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await rsvp.limitInput.fill('');
		await expect(rsvp.savedSummary).toBeVisible({ timeout: 30000 });
	});

	test('save without changes retains RSVP on reload', async ({ page }) => {
		const wpAdmin = new WPAdmin(page);
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.savedSummary).toBeVisible({ timeout: 30000 });
		await wpAdmin.saveDraft();
		await page.reload();
		await hideNoticesOn(page);
		await hideWpFooterOn(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
	});

	test('screenshot: create form open', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.createForm).toBeVisible();
		await expect(rsvp.block).toHaveScreenshot();
	});
});
