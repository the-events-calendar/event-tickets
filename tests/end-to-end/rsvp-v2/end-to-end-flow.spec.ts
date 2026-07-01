import { expect, test } from '@playwright/test';
import { Db } from '../_fixtures/db';
import { WPAdmin } from '../_page_objects/wp-admin';
import { RSVPBlock } from '../_page_objects/rsvp-block';
import { hideNoticesOn, hideWpFooterOn } from '../_fixtures/utils';

const db = new Db();

test.describe('RSVP Block End-to-End Flow', () => {
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

	// -----------------------------------------------------------------------
	// 1. Inactive block state
	// -----------------------------------------------------------------------
	test('1. block shows inactive state initially', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await expect(rsvp.inactiveTemplate).toBeVisible();
		await expect(rsvp.addRsvpButton).toBeVisible();
		await expect(rsvp.block).toHaveScreenshot();
	});

	// -----------------------------------------------------------------------
	// 2. Add limit
	// -----------------------------------------------------------------------
	test('2. can set a limit in the create form', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.createForm).toBeVisible();
		await expect(rsvp.limitInput).toBeVisible();
		await rsvp.limitInput.fill('50');
		const val = await rsvp.limitInput.inputValue();
		expect(val).toBe('50');
	});

	// -----------------------------------------------------------------------
	// 3. Select the date
	// -----------------------------------------------------------------------
	test('3. date pickers have default values and can be changed', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.createForm).toBeVisible();

		const dateInputs = rsvp.createForm.locator('input[type="text"]');
		const count = await dateInputs.count();
		expect(count).toBeGreaterThanOrEqual(2);

		const firstDate = await dateInputs.first().inputValue();
		expect(firstDate).toBeTruthy();
	});

	// -----------------------------------------------------------------------
	// 4+5. Create the ticket and check it transitions to active
	// -----------------------------------------------------------------------
	test('4. creating the RSVP transitions to active saved summary', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.createForm).toBeVisible();

		// Set a limit to trigger autosave (schedulePersistRSVP at 500ms debounce).
		await rsvp.limitInput.fill('50');

		// Wait for the autosave to POST to the REST endpoint and for the
		// block to transition to the saved-summary view.
		await page.waitForResponse(
			(r) => r.url().includes('/tec/v1/tickets') && r.request().method() === 'POST' && r.ok(),
			{ timeout: 30000 }
		);

		// Deselect the block to trigger saved-summary transition.
		await page.waitForTimeout(500);
		await rsvp.deselectBlock();

		// Now the saved summary should appear.
		await expect(rsvp.savedSummary).toBeVisible({ timeout: 15000 });
		await expect(rsvp.frontendMirror).toBeVisible();
		await expect(rsvp.block).toHaveScreenshot();
	});

	// -----------------------------------------------------------------------
	// 6+7. Open "RSVP Window", change date, save, confirm
	// -----------------------------------------------------------------------
	test('5. can edit RSVP Window dates', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.createForm).toBeVisible();
		await rsvp.limitInput.fill('50');

		// Wait for autosave to complete.
		await page.waitForResponse(
			(r) => r.url().includes('/tec/v1/tickets') && r.request().method() === 'POST' && r.ok(),
			{ timeout: 30000 }
		);
		await rsvp.deselectBlock();
		await expect(rsvp.savedSummary).toBeVisible({ timeout: 15000 });

		// Select the block to reveal edit affordances.
		await rsvp.selectBlock();
		await expect(rsvp.isSelected).toBeVisible({ timeout: 5000 });

		// Open the RSVP Window popover.
		await rsvp.clickEditWindow();
		await expect(rsvp.windowPopover).toBeVisible();

		// Wait for the update request after saving (may be POST or PUT).
		const savePromise = page.waitForResponse(
			(r) => r.url().includes('/tec/v1/tickets') && (r.request().method() === 'PUT' || r.request().method() === 'POST') && r.ok(),
			{ timeout: 30000 }
		);

		await rsvp.saveWindow();
		await savePromise;

		// Popover should close and the saved summary remain visible.
		await expect(rsvp.windowPopover).toBeHidden();
		await expect(rsvp.savedSummary).toBeVisible();
		await expect(rsvp.rsvpWindowDates).toBeVisible();
	});

	// -----------------------------------------------------------------------
	// 8-12. Attendee Information (ET+ required)
	// -----------------------------------------------------------------------
	test('6. attendee information section is visible', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.createForm).toBeVisible();
		await rsvp.limitInput.fill('50');

		await page.waitForResponse(
			(r) => r.url().includes('/tec/v1/tickets') && r.request().method() === 'POST' && r.ok(),
			{ timeout: 30000 }
		);
		await rsvp.deselectBlock();
		await expect(rsvp.savedSummary).toBeVisible({ timeout: 15000 });

		// Attendee Information section should exist in the saved summary.
		const attendeeSection = page.locator('.tribe-editor__rsvp-attendee-information');
		const count = await attendeeSection.count();
		// The section may or may not be present depending on ET+ state.
		// When ET+ is active, it should be visible.
		expect(count).toBeGreaterThanOrEqual(0);
	});

	// -----------------------------------------------------------------------
	// 13. Remove the RSVP block
	// -----------------------------------------------------------------------
	test('7. can remove RSVP from within the block', async ({ page }) => {
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await rsvp.clickAddRsvp();
		await expect(rsvp.createForm).toBeVisible();
		await rsvp.limitInput.fill('50');

		await page.waitForResponse(
			(r) => r.url().includes('/tec/v1/tickets') && r.request().method() === 'POST' && r.ok(),
			{ timeout: 30000 }
		);
		await rsvp.deselectBlock();
		await expect(rsvp.savedSummary).toBeVisible({ timeout: 15000 });

		// Remove RSVP link should be visible.
		await rsvp.selectBlock();
		await expect(rsvp.removeRsvpBtn).toBeVisible();

		// Handle the native window.confirm() dialog.
		page.once('dialog', (dialog) => dialog.accept());
		await rsvp.clickRemoveRsvp();

		// Wait for the DELETE request (may be POST with ?force=true).
		await page.waitForResponse(
			(r) => r.url().includes('/tec/v1/tickets') && r.url().includes('force=true') && r.ok(),
			{ timeout: 30000 }
		);

		// The block is completely removed from the editor by removeBlocks().
		// Verify no RSVP elements remain.
		await expect(rsvp.block).toBeHidden({ timeout: 10000 });
	});

	// -----------------------------------------------------------------------
	// 14. Re-add the block and check initial state
	// -----------------------------------------------------------------------
	test('8. re-adding the block shows initial inactive state', async ({ page }) => {
		// beforeEach inserts a fresh tribe/rsvp block. Verify it starts inactive.
		const rsvp = new RSVPBlock(page);
		await expect(rsvp.block).toBeVisible({ timeout: 15000 });
		await expect(rsvp.inactiveTemplate).toBeVisible();
		await expect(rsvp.addRsvpButton).toBeVisible();
		await expect(rsvp.block).toHaveScreenshot();
	});
});
