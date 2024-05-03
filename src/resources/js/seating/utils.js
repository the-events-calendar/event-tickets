// @todo this file might not require building and the whole webpack show, review after project.

/**
 * Returns a link pulled from the scoped window object.
 *
 * @param {string} link The slug of the link to return.
 *
 * @returns {string} The link URL, or an empty string if it does not exist.
 */
function getLink(link) {
	return window?.tec?.seating?.links?.[link] || '';
}

/**
 * Returns a localized string pulled from the scoped window object.
 *
 * @param {string} slug The slug of the string to return.
 * @param {string|null} group Optional, the group of the string to return.
 *
 * @returns {string} The localized string, or an empty string if it does not exist.
 */
function getLocalizedString(slug, group) {
	if (group) {
		return window?.tec?.seating?.localizedStrings?.[group]?.[slug] || '';
	} else {
		return window?.tec?.seating?.localizedStrings?.[slug] || '';
	}
}

window.tec = window.tec || {};
window.tec.seating = window.tec.seating || {};
window.tec.seating.utils = {
	...(window.tec.seating.utils || {}),
	getLink,
	getLocalizedString,
};