/**
 * @typedef {Object} Externals
 * @property {string}      baseUrl                              The servicer base URL without the trailing slash.
 * @property {string}      mapsHomeUrl                          The URL to the Maps home page.
 * @property {string}      layoutsHomeUrl                       The URL to the Layouts home page.
 * @property {Object}      localizedStrings                              The URL to the AJAX endpoint, without the trailing sla
 */

/**
 * @type {Externals}
 */
const localizedData = window.tec.tickets.seating.service;

export const baseUrl = localizedData.baseUrl.replace(/\/$/, '');
export const mapsHomeUrl = localizedData.mapsHomeUrl.replace(/\/$/, '');
export const layoutsHomeUrl = localizedData.layoutsHomeUrl.replace(/\/$/, '');

export function getBaseUrl() {
	return baseUrl;
}
