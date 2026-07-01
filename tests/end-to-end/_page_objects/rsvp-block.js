const { expect } = require('@playwright/test');

exports.RSVPBlock = class RSVPBlock {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor(page) {
		this.page = page;
	}

	// ---------------------------------------------------------------------------
	// Block root & state selectors
	// ---------------------------------------------------------------------------

	get block() {
		return this.page.locator('.tribe-editor__rsvp, .tribe-editor__inactive-block--rsvp');
	}

	get isSelected() {
		return this.page.locator('.tribe-editor__rsvp--selected');
	}

	get isEditing() {
		return this.page.locator('.tribe-editor__rsvp--add-edit-open');
	}

	get loadingSpinner() {
		return this.page.locator('.tribe-editor__rsvp--loading .components-spinner');
	}

	// ---------------------------------------------------------------------------
	// Inactive state
	// ---------------------------------------------------------------------------

	get addRsvpButton() {
		return this.page.locator('button:has-text("Add RSVP")');
	}

	get inactiveTemplate() {
		return this.page.locator('.tribe-editor__inactive-block--rsvp');
	}

	// ---------------------------------------------------------------------------
	// Create form (after clicking Add RSVP)
	// ---------------------------------------------------------------------------

	get createForm() {
		return this.page.locator('.tribe-editor__rsvp-v2-create-form');
	}

	get limitInput() {
		return this.createForm.locator('input[type="number"]');
	}

	get limitHelperText() {
		return this.page.getByText('Leave blank for unlimited');
	}

	get attendeeInfoLink() {
		return this.page.getByText('+ Collect attendee information');
	}

	// ---------------------------------------------------------------------------
	// Saved summary (RSVP enabled, block not in edit mode)
	// ---------------------------------------------------------------------------

	get savedSummary() {
		return this.page.locator('.tribe-editor__rsvp-saved-summary');
	}

	get frontendMirror() {
		return this.page.locator('.tribe-editor__rsvp-frontend-mirror');
	}

	get goingNumber() {
		return this.frontendMirror.locator('.tribe-tickets__rsvp-attendance-number, .tribe-tickets__rsvp-number-going');
	}

	get remainingText() {
		return this.frontendMirror.locator('.tribe-tickets__rsvp-availability');
	}

	get notGoingNumber() {
		return this.frontendMirror.locator('.tribe-tickets__rsvp-attendance-number-not-going, .tribe-tickets__rsvp-number-not-going');
	}

	get viewAttendeesLink() {
		return this.page.locator('.tribe-editor__rsvp-saved-summary a:has-text("View Attendees"), .tribe-editor__rsvp a:has-text("View Attendees")');
	}

	// ---------------------------------------------------------------------------
	// Edit affordances (pencil icons)
	// ---------------------------------------------------------------------------

	get editRemainingBtn() {
		return this.page.locator('button.tribe-editor__rsvp-inline-edit-button').first();
	}

	get editWindowBtn() {
		return this.page.locator('.tribe-editor__rsvp-window__title-edit');
	}

	get rsvpWindowDates() {
		return this.page.locator('.tribe-editor__rsvp-window__dates');
	}

	get attendeeInfoEdit() {
		return this.page.locator('.tribe-editor__rsvp-attendee-information__title-edit');
	}

	get attendeeInfoFields() {
		return this.page.locator('.tribe-editor__rsvp-attendee-information__fields');
	}

	// ---------------------------------------------------------------------------
	// Limit popover
	// ---------------------------------------------------------------------------

	get limitPopover() {
		return this.page.locator('.tribe-editor__rsvp-limit-popover');
	}

	get limitPopoverInput() {
		return this.limitPopover.locator('input');
	}

	get limitPopoverSaveBtn() {
		return this.limitPopover.locator('button:has-text("Save")');
	}

	get limitPopoverCancelBtn() {
		return this.limitPopover.locator('button:has-text("Cancel")');
	}

	// ---------------------------------------------------------------------------
	// Window popover
	// ---------------------------------------------------------------------------

	get windowPopover() {
		return this.page.locator('.tribe-editor__rsvp-window-popover');
	}

	get windowPopoverSaveBtn() {
		return this.windowPopover.locator('button:has-text("Save")');
	}

	get windowPopoverCancelBtn() {
		return this.windowPopover.locator('button:has-text("Cancel")');
	}

	// ---------------------------------------------------------------------------
	// Remove RSVP
	// ---------------------------------------------------------------------------

	get removeRsvpBtn() {
		return this.page.locator('.tribe-editor__rsvp-remove button');
	}

	get confirmRemoveDialog() {
		return this.page.locator('.components-confirm-dialog');
	}

	get confirmRemoveBtn() {
		return this.confirmRemoveDialog.locator('button:has-text("Remove")');
	}

	get confirmRemoveCancelBtn() {
		return this.confirmRemoveDialog.locator('button:has-text("Cancel")');
	}

	// ---------------------------------------------------------------------------
	// Actions
	// ---------------------------------------------------------------------------

	async clickAddRsvp() {
		await this.addRsvpButton.click();
		await this.createForm.waitFor({ state: 'visible', timeout: 10000 });
	}

	async clickEditRemaining() {
		await this.editRemainingBtn.click();
		await this.limitPopover.waitFor({ state: 'visible', timeout: 10000 });
	}

	async clickEditWindow() {
		await this.editWindowBtn.click();
		await this.windowPopover.waitFor({ state: 'visible', timeout: 10000 });
	}

	async saveLimit(value) {
		if (value !== undefined) {
			await this.limitPopoverInput.fill(String(value));
		}
		await this.limitPopoverSaveBtn.click();
		await this.limitPopover.waitFor({ state: 'hidden', timeout: 10000 });
	}

	async saveWindow() {
		await this.windowPopoverSaveBtn.click();
		await this.windowPopover.waitFor({ state: 'hidden', timeout: 10000 });
	}

	async cancelLimitPopover() {
		await this.limitPopoverCancelBtn.click();
		await this.limitPopover.waitFor({ state: 'hidden', timeout: 10000 });
	}

	async cancelWindowPopover() {
		await this.windowPopoverCancelBtn.click();
		await this.windowPopover.waitFor({ state: 'hidden', timeout: 10000 });
	}

	async clickRemoveRsvp() {
		// The Remove RSVP flow uses native window.confirm() — set up
		// a dialog handler before clicking.
		await this.removeRsvpBtn.click();
	}

	async confirmRemoveRsvp() {
		// Native window.confirm dialog — accept it.
		await this.page.waitForTimeout(500);
	}

	async cancelRemoveRsvp() {
		// Native window.confirm dialog — dismiss it.
		await this.page.waitForTimeout(500);
	}

	async selectBlock() {
		await this.block.click();
		await this.page.waitForTimeout(300);
	}

	async deselectBlock() {
		await this.page.evaluate(() => {
			window.wp.data.dispatch('core/block-editor').clearSelectedBlock();
		});
		await this.page.waitForTimeout(500);
	}

	async getRemainingInt() {
		const text = await this.remainingText.textContent();
		const match = text.match(/(\d+)\s*Remaining/);
		return match ? parseInt(match[1], 10) : null;
	}

	async getGoingInt() {
		const text = await this.goingNumber.textContent();
		return parseInt(text.replace(/[^0-9]/g, ''), 10) || 0;
	}

	async getNotGoingInt() {
		const text = await this.notGoingNumber.textContent();
		return parseInt(text.replace(/[^0-9]/g, ''), 10) || 0;
	}
};
