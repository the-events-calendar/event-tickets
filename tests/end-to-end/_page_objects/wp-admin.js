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

		await this.page.goto(`/wp-admin/post-new.php?post_type=${postType}`);
		await this.disableWelcomeAndFullscreen();
	}

	async disableWelcomeAndFullscreen() {
		await this.page.waitForLoadState('domcontentloaded');
		await this.page.waitForTimeout(2000);
		try {
			await this.page.evaluate(() => {
				const wpData = window.wp?.data;
				if (!wpData) {
					return;
				}
				const editPost = wpData.select('core/edit-post');
				if (!editPost || typeof editPost.isFeatureActive !== 'function') {
					return;
				}
				if (editPost.isFeatureActive('welcomeGuide')) {
					wpData.dispatch('core/edit-post').toggleFeature('welcomeGuide');
				}
				if (editPost.isFeatureActive('fullscreenMode')) {
					wpData.dispatch('core/edit-post').toggleFeature('fullscreenMode');
				}
			});
		} catch (e) {
			// Silently ignore errors during disable
		}
	}

	async isBlockEditor() {
		try {
			return await this.page.waitForFunction(
				() => !!(window.wp?.blocks && window.wp?.data),
				{ timeout: 10000 }
			).then(() => true).catch(() => false);
		} catch {
			return false;
		}
	}

	async requireBlockEditor() {
		// Wait up to 15s for wp.blocks to become available before failing.
		const isBlock = await this.page.waitForFunction(
			() => !!(window.wp?.blocks && window.wp?.data),
			{ timeout: 15000 }
		).then(() => true).catch(() => false);

		if (!isBlock) {
			throw new Error(
				'Classic Editor is active — the block editor (wp.blocks) is not loaded. ' +
				'Set classic-editor-replace option to "block" or deactivate the Classic Editor plugin.'
			);
		}
	}

	async insertBlock(blockName) {
		await this.page.waitForFunction(() => {
			return window.wp && window.wp.blocks && window.wp.blocks.createBlock && window.wp.data;
		}, { timeout: 10000 });
		await this.page.evaluate((name) => {
			const block = window.wp.blocks.createBlock(name);
			window.wp.data.dispatch('core/block-editor').insertBlocks(block);
		}, blockName);
		await this.page.waitForTimeout(1000);
	}

	async gotoCreateNewEvent() {
		await this.gotoCreateNewPostTypePage('tribe_events');
	}

	async gotoEditEvent(postId) {
		await this.page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
		await this.disableWelcomeAndFullscreen();
	}

	async saveDraft() {
		await this.page.click('button:has-text("Save draft")');
		await this.page.waitForSelector('.components-snackbar, .notice-success, .editor-post-saved-state.is-saved', { timeout: 15000 });
	}

	async clickOutsideBlock() {
		await this.page.click('.edit-post-visual-editor__content-area, .editor-styles-wrapper');
	}

	async waitForRestResponse(endpointPattern, status = 200) {
		await this.page.waitForResponse(
			(r) => r.url().includes(endpointPattern) && r.status() === status,
			{ timeout: 15000 }
		);
	}
};
