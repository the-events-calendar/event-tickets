/**
 * @typedef {Object} Externals
 * @property {string} baseUrl        The servicer base URL without the trailing slash.
 * @property {string} mapsHomeUrl    The URL to the Maps home page.
 * @property {string} layoutsHomeUrl The URL to the Layouts home page.
 * @property {string} ajaxUrl        The URL to the AJAX endpoint.
 * @property {string} ajaxNonce      The AJAX nonce.
 */

/**
 * @type {Externals}
 */
const externals = window.tec.tickets.seating.service;

export const baseUrl = externals.baseUrl.replace(/\/$/, '');
export const mapsHomeUrl = externals.mapsHomeUrl.replace(/\/$/, '');
export const layoutsHomeUrl = externals.layoutsHomeUrl.replace(/\/$/, '');
export const ajaxUrl = externals.ajaxUrl.replace(/\/$/, '');
export const ajaxNonce = externals.ajaxNonce;

export function getBaseUrl() {
	return baseUrl;
}
