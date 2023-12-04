const {
	fieldSelector,
	containerSelector,
	differentProviderNoticeSelector,
	differentProviderNoticeTemplate,
} = window.TECFtEditorData.seriesRelationship;
const noticeSelector =
	containerSelector + ' ' + differentProviderNoticeSelector;

/**
 * Get the series data from the metabox dropdown element's value attribute.
 *
 * @since TBD
 *
 * @param {Element|null} element The metabox dropdown element.
 * @param {string}       key     The key of the series data to retrieve.
 *
 * @return {string|null} The series data read from the element's value attribute, `null` if not found.
 */
export function getSeriesDataFromElement(element, key) {
	if (!(element && element.value)) {
		return null;
	}

	const seriesJsonData = element.value;

	try {
		return JSON.parse(seriesJsonData)[key] || null;
	} catch (e) {
		return null;
	}
}

/**
 * Get the series data from the `change` event dispatched by Select2 when the series is changed
 *
 * @since TBD
 *
 * @param {Event}  event The `change` event dispatched by Select2.
 * @param {string} key   The key of the series data to retrieve.
 *
 * @return {string|null} The series data read from the selected option data, `null` if not found.
 */
export function getSeriesDataFromEvent(event, key) {
	if (!event.currentTarget) {
		return null;
	}

	return getSeriesDataFromElement(event.currentTarget, key);
}

/**
 * Get the series title from the `change` event dispatched by Select2 when the series is changed
 * by the user in the metabox dropdown.
 *
 * @since TBD
 *
 * @param {Event} event The `change` event dispatched by Select2.
 *
 * @return {string} The title of the series read from the selected option data.
 */
export function getSeriesTitleFromEvent(event) {
	return getSeriesDataFromEvent(event, 'title') || '';
}

/**
 * Get the series ticket provider from the `change` event dispatched by Select2 when the series is changed
 *
 * @since TBD
 *
 * @param {Event} event The `change` event dispatched by Select2.
 *
 * @return {string|null} The ticket provider of the series read from the selected option data, `null` if not found.
 */
export function getSeriesProviderFromEvent(event) {
	return getSeriesDataFromEvent(event, 'ticket_provider');
}

/**
 * Get the series ticket provider from the currently selected series in the metabox dropdown.
 *
 * @since TBD
 *
 * @return {string|null} The ticket provider of the series read from the selected option data, `null` if not found.
 */
export function getSeriesProviderFromSelection() {
	const seriesSelect = document.getElementById(fieldSelector.substring(1));
	return getSeriesDataFromElement(seriesSelect, 'ticket_provider');
}

/**
 * Get the series title from the currently selected series in the metabox dropdown.
 *
 * @since TBD
 *
 * @return {string|null} The title of the series read from the selected option data, `null` if not found.
 */
export function getSeriesTitleFromSelection() {
	const seriesSelect = document.getElementById(fieldSelector.substring(1));
	return getSeriesDataFromElement(seriesSelect, 'title');
}

/**
 * Get the series edit link from the metabox dropdown.
 *
 * @since TBD
 *
 * @param {string|null} append The string to append to the edit link.
 *
 * @return {string } The edit link of the series read from the selected option data, or an empty string if not found.
 */
export function getSeriesEditLinkFromMetaBox(append) {
	const editLinkElement = document.querySelector(
		containerSelector + ' a.tec-events-pro-series__edit-link'
	);
	const editLink = editLinkElement?.getAttribute('href') || '';

	return editLink + (append ? append : '');
}

/**
 * Subscribe to the series change event.
 *
 * @since TBD
 *
 * This is the event triggered by the user selecting a series in the metabox dropdown.
 *
 * @param {Function} onChange The callback function to be called when the series is changed.
 */
export function subscribeToSeriesChange(onChange) {
	jQuery(fieldSelector).on('change', onChange);
}

/**
 * Remove the notice that the event and series have different ticket providers.
 *
 * @since TBD
 */
export function removeDiscordantProviderNotice() {
	Array.from(document.querySelectorAll(noticeSelector)).map((el) =>
		el.remove(true)
	);
}

/**
 * Show a notice that the event and series have different ticket providers.
 *
 * @since TBD
 *
 * @param {string} eventTitle  The title of the event.
 * @param {string} seriesTitle The title of the series.
 */
export function showDiscordantProviderNotice(eventTitle, seriesTitle) {
	removeDiscordantProviderNotice();

	const noticeElement = document.createElement('div');
	noticeElement.classList.add(differentProviderNoticeSelector.substring(1));
	noticeElement.style['margin-top'] = 'var(--tec-spacer-1)';
	noticeElement.textContent = differentProviderNoticeTemplate
		.replace('%1$s', eventTitle)
		.replace('%2$s', seriesTitle);
	document.querySelector(containerSelector).append(noticeElement);
}
