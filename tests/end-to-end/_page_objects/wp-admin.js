const { adminUser, adminPassword } = require('../_constants/env');
const { expect } = require('@playwright/test');
const { TicketsSettingsPage } = require('./tickets-settings-page');
const { Db } = require('./../_fixtures/db');
const fs = require('node:fs');

exports.WPAdmin = class WPAdmin {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor(page) {
		this.page = page;
		this.baseUrl = '/wp-admin';
	}

	async loginAsAdmin() {
		await this.page.goto('/wp-login.php');
		await this.page.fill('#user_login', adminUser);
		await this.page.fill('#user_pass', adminPassword);
		await this.page.click('#wp-submit');
		await this.page.goto('/wp-admin.php');
		// No need to show the welcome panel each time we access the block editor.
		const db = new Db();
		await db.updateUserMeta(1, 'show_welcome_panel', 0);
	}

	async gotoTicketsSettingsPage() {
		await this.page.goto('/wp-admin/admin.php?page=tec-tickets-settings');
		return new TicketsSettingsPage(this.page);
	}

	async gotoCreateNewPostTypePage(postType) {
		expect(postType).isPrototypeOf(String);
		expect(postType).not.toBe('');

		// Disable welcome guide and fullscreen mode.
		await this.page.evaluate(async () => {
			const wpData = window.wp.data || null;
			if (!wpData) {
				return;
			}
			wpData.select('core/edit-post').isFeatureActive('welcomeGuide') &&
				wpData.dispatch('core/edit-post').toggleFeature('welcomeGuide');
			wpData.select('core/edit-post').isFeatureActive('fullscreenMode') &&
				wpData
					.dispatch('core/edit-post')
					.toggleFeature('fullscreenMode');
		});

		await this.page.goto(`/wp-admin/post-new.php?post_type=${postType}`);
	}
};
