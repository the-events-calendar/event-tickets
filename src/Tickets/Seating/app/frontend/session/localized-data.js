/**
 * @typedef {Object} LocalizedTimerData
 * @property {string} ajaxUrl                   The URL to the service iframe.
 * @property {string} ajaxNonce                 The AJAX nonce.
 * @property {number} checkoutGraceTime         The grace time, in seconds, given to a user to complete the checkout.
 * @property {string} ACTION_START              The action to start the timer.
 * @property {string} ACTION_SYNC               The action to sync the timer with the backend.
 * @property {string} ACTION_INTERRUPT_GET_DATA The action to get the data required to render the redirection modal.
 * @property {string} ACTION_PAUSE_TO_CHECKOUT  The action to signal the backend we're pausing the timer to checkout.
 * @property {string} TICKETS_BLOCK_DIALOG_NAME The name of the dialog element used to render the seat selection modal.
 */

/**
 * @type {LocalizedTimerData}
 */
export const localizedData = tec.tickets.seating.frontend.session;
