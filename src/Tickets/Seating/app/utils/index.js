/**
 * Returns a link pulled from the scoped window object.
 *
 * @param {string} link The slug of the link to return.
 *
 * @return {string} The link URL, or an empty string if it does not exist.
 */
export function getLink(link) {
	return window?.tec?.tickets?.seating?.utils?.links?.[link] || '';
}

/**
 * Returns a localized string pulled from the scoped window object.
 *
 * @param {string}      slug  The slug of the string to return.
 * @param {string|null} group Optional, the group of the string to return.
 *
 * @return {string} The localized string, or an empty string if it does not exist.
 */
export function getLocalizedString(slug, group) {
	if (group) {
		return (
			window?.tec?.tickets?.seating?.utils?.localizedStrings?.[group]?.[slug] || ''
		);
	}
	return window?.tec?.tickets?.seating?.utils?.localizedStrings?.[slug] || '';
}

/**
 * Creates an HTMl Element from an HTML template and replaces the placeholders with the props.
 *
 * Resist the temptation to add complex logic to the template.
 * If you need something more elaborate, use React.
 *
 * @since 5.16.0
 *
 * @param {string} htmlTemplate The HTML template to use. Properties placeholder have the form `{propertyName}`.
 * @param {Object} props        The props to replace in the template.
 *
 * @return {HTMLElement} The HTML Element.
 */
export function createHtmlComponentFromTemplateString(htmlTemplate, props) {
	const html = htmlTemplate.replace(/{(\w*)}/g, function (match, key) {
		return props?.[key] || '';
	});
	const template = document.createElement('template');
	template.innerHTML = html.trim();
	return template.content.children[0];
}

/**
 * Creates an HTMl Element from a template and replaces the placeholders with the props.
 *
 * The function will replace each occurrence of `{key}` with the value of the `key` property in the props object.
 * If the key is not found, it will be replaced with an empty string.
 *
 * @since 5.16.0
 *
 * @param {string} templateId The ID of the template to use.
 * @param {Object} props      The props to replace in the template.
 * @return {HTMLElement|null} The HTML Element, or `null` if the template is not found.
 */
export function createHtmlComponentFromTemplateElement(templateId, props) {
	const template = document.getElementById(templateId);

	if (!template) {
		return null;
	}

	return createHtmlComponentFromTemplateString(template.innerHTML, props);
}

/**
 * Calls a callback when the DOM is ready.
 *
 * @since 5.16.0
 *
 * @param {function} domReadyCallback The callback to call when the DOM is ready.
 *
 * @return {void}
 */
export const onReady = (domReadyCallback) => {
	if (document.readyState !== 'loading') {
		domReadyCallback();
	} else {
		document.addEventListener('DOMContentLoaded', domReadyCallback);
	}
};

/**
 * Redirects to a URL.
 *
 * @since 5.16.0
 *
 * @param {string} url The URL to relocate to.
 * @param {boolean} [newTab=false] Whether to open the URL in a new tab.
 *
 * @return {void}
 */
export function redirectTo(url, newTab = false) {
	if (newTab) {
		window.open( url, '_blank', 'noopener,noreferrer' );
	} else {
		window.location.href = url;
	}
}

window.tec = window.tec || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.utils = {
	...(window.tec.tickets.seating.utils || {}),
	getLink,
	getLocalizedString,
	createHtmlComponentFromTemplateString,
	createHtmlComponentFromTemplateElement,
	onReady,
	redirectTo,
};
