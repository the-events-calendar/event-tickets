const { adminUser, adminPassword } = require('../_constants/env');

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
	}

	async amOnTicketsSettingsPage() {
		await this.page.goto('/wp-admin/admin.php?page=tec-tickets-settings');
	}
};
