// @ts-check
const { test, expect } = require('@playwright/test');
const { Db } = require('../_fixtures/db');
const { WPConfig } = require('../_fixtures/wp-config');
const { WPAdmin } = require('../_page_objects/wp-admin');
const { seriesPostType } = require('../_constants/post-types');

test.beforeAll(async () => {
	const db = new Db();
	await db.reset();
	await db.loadDump('ft_blocks');
	const wpConfig = new WPConfig();
	await wpConfig.deleteOrContinue('TEC_FLEXIBLE_TICKETS_DISABLED');
});

test('series are ticketable', async ({ page }) => {
	const wpAdmin = new WPAdmin(page);
	await wpAdmin.loginAsAdmin();
	await wpAdmin.gotoTicketsSettingsPage();

	await expect(
		page.locator(
			`#tribe-field-ticket-enabled-post-types input[value="${seriesPostType}"]`
		)
	).toBeDefined();
});

test('tickets are not available on non-ticketable type', async ({ page }) => {
	const wpAdmin = new WPAdmin(page);
	await wpAdmin.loginAsAdmin();

	const ticketSettingsPage = await wpAdmin.gotoTicketsSettingsPage();
	await ticketSettingsPage.setPostTypeTicketable('post', false);
	await ticketSettingsPage.save();

	await wpAdmin.gotoCreateNewPostTypePage('post');

	await page.screenshot({
		path: 'tests/_output/screenshot.png',
		fullPage: true,
	});

	await page.isVisible('input.components-search-control__input');
	await page.fill('input.components-search-control__input', 'Tickets');

	await expect(
		page.locator('.block-editor-inserter__quick-inserter-results')
	).toHaveText('No results found.');
});
