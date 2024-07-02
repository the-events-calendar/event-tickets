/**
 * @typedef {Object} ExternalTimerData
 * @property {string} ajaxUrl          The URL to the service iframe.
 * @property {string} ajaxNonce        The AJAX nonce.
 * @property {string} ACTION_START     The action to start the timer.
 * @property {string} ACTION_TIME_LEFT The action to get the time left in the timer.
 * @property {string} ACTION_REDIRECT  The action to redirect the user to when the timer expires.
 */

/**
 * @type {ExternalTimerData}
 */
export const localizedData = tec.tickets.seating.frontend.timer;
