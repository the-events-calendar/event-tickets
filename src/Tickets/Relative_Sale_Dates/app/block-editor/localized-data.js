/**
 * Reads the data the server localizes the Ticket block script with.
 *
 * @since TBD
 */

/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */

/**
 * @typedef {Object} HelperTextFormats
 *
 * @property {string} dateWithYear The date format of a date in another year, in PHP date format.
 * @property {string} dateNoYear   The date format of a date in the current year, in PHP date format.
 * @property {string} time         The site time format, in PHP date format.
 */

/**
 * @typedef {Object} BlockEditorData
 *
 * @property {Object<string,SaleWindowRule>} windowDefaults The `start` and `end` a new window of each kind opens on,
 *                                                          with the relative values each offers when the rule has
 *                                                          none, keyed by the kind's ID.
 * @property {Object<string,string>}         timezones      The zone the server resolves each manual UTC offset option
 *                                                          to.
 * @property {Object}                        allDay         The times the server saves for an all-day event: `start`
 *                                                          and `end`, `HH:mm:ss`, and `endDays`.
 * @property {HelperTextFormats}             formats        The date and time formats of the helper text.
 */

/**
 * Gets the data the server localizes the Ticket block script with.
 *
 * @since TBD
 *
 * @return {BlockEditorData} The localized data.
 */
export function getLocalizedData() {
	return window.tec.tickets.relativeSaleDates.blockEditorData;
}
