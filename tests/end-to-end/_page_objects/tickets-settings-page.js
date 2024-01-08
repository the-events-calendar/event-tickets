const { expect } = require('@playwright/test');

exports.TicketsSettingsPage = class TicketsSettingsPage {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor(page) {
		this.page = page;
	}

	async setPostTypeTicketable(postType) {
		expect(postType).isPrototypeOf(String);
		expect(postType).not.toBe('');

		await this.page.check(
			`[name='ticket-enabled-post-types[]'][value='${postType}']`
		);
	}

	async save() {
		await this.page.click('[name="tribeSaveSettings"]');
	}
};
