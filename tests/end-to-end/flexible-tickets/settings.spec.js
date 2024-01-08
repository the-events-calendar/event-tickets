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
	await wpAdmin.amOnTicketsSettingsPage();

	await expect(
		page.locator(
			`#tribe-field-ticket-enabled-post-types input[value="${seriesPostType}"]`
		)
	).toBeDefined();
});
