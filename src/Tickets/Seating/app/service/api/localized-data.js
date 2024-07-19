/**
 * @typedef {Object} AjaxActions
 * @property {string}      ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE The action to invalidate the maps and layouts cache.
 * @property {string}      ACTION_INVALIDATE_LAYOUTS_CACHE      The action to invalidate the layouts cache.
 * @property {string}      ACTION_DELETE_MAP                    The action to delete a map.
 * @property {string}      ACTION_DELETE_LAYOUT                 The action to delete a layout.
 * @property {string}      ACTION_POST_RESERVATIONS             The action to post the reservations to the backend from the seat-selection frontend.
 * @property {string}      ACTION_CLEAR_RESERVATIONS            The action to clear the reservations from the backend from the seat-selection frontend.
 * @property {string}      ACTION_FETCH_ATTENDEES               The action to fetch attendees by event or post ID.
 * @property {string}      ACTION_DELETE_RESERVATIONS           The action to delete reservations.
 *
 * @typedef {Object} Externals
 * @property {string}      baseUrl                              The servicer base URL without the trailing slash.
 * @property {string}      mapsHomeUrl                          The URL to the Maps home page.
 * @property {string}      layoutsHomeUrl                       The URL to the Layouts home page.
 * @property {string}      ajaxUrl                              The URL to the AJAX endpoint.
 * @property {string}      ajaxNonce                            The AJAX nonce.
 * @property {AjaxActions} ajax                                 The AJAX actions.
 */

/**
 * @type {Externals}
 */
const localizedData = window.tec.tickets.seating.service;

export const baseUrl = localizedData.baseUrl.replace(/\/$/, '');
export const mapsHomeUrl = localizedData.mapsHomeUrl.replace(/\/$/, '');
export const layoutsHomeUrl = localizedData.layoutsHomeUrl.replace(/\/$/, '');
export const ajaxUrl = localizedData.ajaxUrl.replace(/\/$/, '');
export const ajaxNonce = localizedData.ajaxNonce;

export const ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE = localizedData.ajax.ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE;
export const ACTION_INVALIDATE_LAYOUTS_CACHE = localizedData.ajax.ACTION_INVALIDATE_LAYOUTS_CACHE;
export const ACTION_DELETE_MAP = localizedData.ajax.ACTION_DELETE_MAP;
export const ACTION_DELETE_LAYOUT = localizedData.ajax.ACTION_DELETE_LAYOUT;
export const ACTION_POST_RESERVATIONS = localizedData.ajax.ACTION_POST_RESERVATIONS;
export const ACTION_CLEAR_RESERVATIONS = localizedData.ajax.ACTION_CLEAR_RESERVATIONS;
export const ACTION_FETCH_ATTENDEES = localizedData.ajax.ACTION_FETCH_ATTENDEES;
export const ACTION_DELETE_RESERVATIONS = localizedData.ajax.ACTION_DELETE_RESERVATIONS;

export function getBaseUrl() {
	return baseUrl;
}
