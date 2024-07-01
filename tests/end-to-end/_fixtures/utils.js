export async function hideNoticesOn(page) {
	await page.addStyleTag({
		content: '.notice,.tribe-notice { display: none !important; }',
	});
}

export async function hideWpFooterOn(page) {
	await page.addStyleTag({
		content: '#wpfooter { display: none !important; }',
	});
}
