/**
 * @typedef {Object} AjaxLocalizedData
 * @property {string} ajaxUrl                              The URL to the AJAX endpoint.
 * @property {string} ajaxNonce                            The AJAX nonce.
 * @property {string} ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE The action to invalidate the maps and layouts cache.
 * @property {string} ACTION_INVALIDATE_LAYOUTS_CACHE      The action to invalidate the layouts cache.
 * @property {string} ACTION_DELETE_MAP                    The action to delete a map.
 * @property {string} ACTION_DELETE_LAYOUT                 The action to delete a layout.
 * @property {string} ACTION_ADD_NEW_LAYOUT                The action to add a layout.
 * @property {string} ACTION_POST_RESERVATIONS             The action to post the reservations to the backend from the seat-selection frontend.
 * @property {string} ACTION_CLEAR_RESERVATIONS            The action to clear the reservations from the backend from the seat-selection frontend.
 * @property {string} ACTION_FETCH_ATTENDEES               The action to fetch attendees by event or post ID.
 * @property {string} ACTION_DELETE_RESERVATIONS           The action to delete reservations.
 * @property {string} ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID   The action to get the seat types for a given layout ID.
 * @property {string} ACTION_SEAT_TYPES_UPDATED            The action to update the seat types.
 * @property {string} ACTION_SEAT_TYPE_DELETED             The action to handle the deletion of a seat type.
 * @property {string} ACTION_EVENT_LAYOUT_UPDATED          The action to handle the update of layout type.
 */

/**
 * @type {AjaxLocalizedData}
 */
export const localizedData = window?.tec?.tickets?.seating?.ajax;
